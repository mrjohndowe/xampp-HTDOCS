#!/usr/bin/env python3
"""Motorlog Windows GUI. A client of the authenticated Motorlog PHP workspace."""
from __future__ import annotations

import base64
from concurrent.futures import ThreadPoolExecutor
import ctypes
from datetime import date, datetime, timedelta
from decimal import Decimal, ROUND_HALF_UP, InvalidOperation
import hashlib
import io
import json
import os
from pathlib import Path
import queue
import re
import sys
import tempfile
import tkinter as tk
from tkinter import ttk, messagebox, filedialog
from urllib.error import HTTPError, URLError
from urllib.parse import urlencode, urlsplit, urlunsplit
from urllib.request import HTTPRedirectHandler, Request, build_opener
import uuid
import webbrowser

try:
    from PIL import Image, ImageTk
except ImportError:
    Image = ImageTk = None

VERSION_FILE = Path(__file__).with_name('VERSION')
VERSION = VERSION_FILE.read_text(encoding='utf-8').strip() if VERSION_FILE.is_file() else '1.0.0'
PREF_DIR = Path(os.environ.get('LOCALAPPDATA', str(Path.home() / '.config'))) / 'Motorlog'
PREF_FILE = PREF_DIR / 'preferences.json'
LIGHT = dict(bg='#f6f7fb', surface='#ffffff', subtle='#eef0f7', ink='#20243a', muted='#666d85', primary='#6247dc', soft='#ece8ff', warn='#a2631b', bad='#ae3648', good='#087b5f', line='#e2e4ee')
DARK = dict(bg='#151624', surface='#1c2032', subtle='#252a40', ink='#f1f1fc', muted='#aab1c8', primary='#8261f1', soft='#32274d', warn='#f0bb77', bad='#ffa5ad', good='#82d0b2', line='#333a52')
SECTIONS = [('dashboard','Overview'), ('vehicles','Vehicles'), ('trips','Trip log'), ('expenses','Fuel & expenses'), ('mechanics','Mechanics'), ('drivers','Drivers'), ('inspection','Weekly inspections'), ('sale','Bills of sale'), ('settings','Settings')]
FLAGS = {'draft':'Draft', 'submitted':'Completed', 'finalized':'Finalized', 'signed':'Signed'}


class ApiError(Exception):
    def __init__(self, message, code='', status=0):
        super().__init__(message)
        self.code, self.status = code, status


class NoRedirect(HTTPRedirectHandler):
    # Do not send any Authorization header to a redirect destination.
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def normalize_url(url):
    try:
        parts = urlsplit(url.strip())
        if parts.scheme not in ('http', 'https') or not parts.hostname or parts.username or parts.password or parts.query or parts.fragment:
            raise ValueError()
        _ = parts.port
    except ValueError:
        raise ApiError('Enter a website URL such as http://localhost/motorlog/ or https://your-server/motorlog/.')
    return urlunsplit((parts.scheme, parts.netloc, parts.path or '/', '', ''))


class Client:
    def __init__(self, url, token):
        self.url, self.token = normalize_url(url), token.strip()
        self.opener = build_opener(NoRedirect())

    def request(self, route, params=None, *, data=None, key='', raw=False, authenticate=True):
        headers = {'Accept':'*/*' if raw else 'application/json','User-Agent':f'MotorlogDesktop/{VERSION}'}
        if authenticate:
            headers['Authorization'] = 'Bearer ' + self.token
        out = None
        if data is not None:
            out = json.dumps(data, ensure_ascii=False, sort_keys=True, separators=(',', ':')).encode('utf-8')
            headers['Content-Type'] = 'application/json'
        if key:
            headers['Idempotency-Key'] = key
        request = Request(self.url + '?' + urlencode({'r':route, **{k:v for k,v in (params or {}).items() if v not in (None,'')}}), data=out, headers=headers)
        try:
            with self.opener.open(request, timeout=16) as response:
                blob = response.read(40_000_001)
        except HTTPError as e:
            try:
                result = json.loads(e.read(256_000))
                description = result.get('message', '')
                code = result.get('code', '')
            except Exception:
                description, code = '', ''
            if 300 <= e.code < 400:
                description = 'Use the exact website URL including https and the final slash if required. Redirects are not followed with a token.'
            raise ApiError(description or f'The website returned HTTP {e.code}. Your form is still open.', code, e.code) from None
        except (URLError, TimeoutError, OSError) as e:
            raise ApiError('Could not connect to Motorlog. Check the URL or network and retry. Your form has not been discarded.', 'offline') from None
        if len(blob) > 40_000_000:
            raise ApiError('The server response is too large.')
        if raw:
            return blob
        try:
            result = json.loads(blob)
            if not isinstance(result, dict) or not result.get('ok'):
                raise ValueError()
            return result
        except (ValueError, UnicodeError):
            raise ApiError('The server did not return a Motorlog API response. Check the website address.') from None

    def connect(self):
        ping = self.request('ping', authenticate=False)
        if ping.get('app') != 'Motorlog':
            raise ApiError('The address does not point to a Motorlog server.')
        if not ping.get('installed'):
            raise ApiError('Website onboarding is incomplete. Finish the wizard in a browser before connecting the desktop app.', 'setup_required')
        return self.request('state')

    def save(self, entity, action, data, key):
        return self.request('save', data={'entity':entity,'action':action,'data':data}, key=key)


def windows_secret(value, decrypt=False):
    if sys.platform != 'win32' or not value:
        return ''
    from ctypes import wintypes
    class Blob(ctypes.Structure):
        _fields_ = [('size', wintypes.DWORD), ('data', ctypes.POINTER(ctypes.c_ubyte))]
    crypt = ctypes.WinDLL('crypt32', use_last_error=True)
    kernel = ctypes.WinDLL('kernel32', use_last_error=True)
    fn = crypt.CryptUnprotectData if decrypt else crypt.CryptProtectData
    fn.restype = wintypes.BOOL
    fn.argtypes = [ctypes.POINTER(Blob), ctypes.c_void_p, ctypes.c_void_p, ctypes.c_void_p, ctypes.c_void_p, wintypes.DWORD, ctypes.POINTER(Blob)]
    kernel.LocalFree.argtypes = [ctypes.c_void_p]
    kernel.LocalFree.restype = ctypes.c_void_p
    raw = base64.b64decode(value) if decrypt else value.encode('utf-8')
    in_buf = ctypes.create_string_buffer(raw)
    original = Blob(len(raw), ctypes.cast(in_buf, ctypes.POINTER(ctypes.c_ubyte)))
    target = Blob()
    if not fn(ctypes.byref(original), None, None, None, None, 1, ctypes.byref(target)):
        return ''
    try:
        content = ctypes.string_at(target.data, target.size)
        return content.decode('utf-8') if decrypt else base64.b64encode(content).decode('ascii')
    finally:
        kernel.LocalFree(ctypes.cast(target.data, ctypes.c_void_p))


def prefs_read():
    try:
        obj = json.loads(PREF_FILE.read_text(encoding='utf-8'))
        return obj if isinstance(obj, dict) else {}
    except (OSError, ValueError):
        return {}


def prefs_save(obj):
    try:
        PREF_DIR.mkdir(mode=0o700, parents=True, exist_ok=True)
        fd, path = tempfile.mkstemp(prefix='.prefs-', dir=PREF_DIR)
        with os.fdopen(fd, 'w', encoding='utf-8') as f:
            json.dump(obj, f, indent=2)
        os.replace(path, PREF_FILE)
    except OSError:
        pass


def semver(v):
    match = re.fullmatch(r'v?((?:0|[1-9]\d{0,8}))\.((?:0|[1-9]\d{0,8}))\.((?:0|[1-9]\d{0,8}))(?:\+[\w-]+(?:\.[\w-]+)*)?', str(v), re.ASCII)
    return tuple(map(int, match.groups())) if match else None


def fmt_num(value, decimals=3):
    if value is None or value == '':
        return '—'
    try:
        return f'{float(value):,.{decimals}f}'.rstrip('0').rstrip('.') if decimals else f'{int(value):,}'
    except (ValueError, TypeError):
        return '—'


def fmt_money(v, currency='USD', precision=None):
    if v is None or v == '':
        return '—'
    if precision is None:
        precision = 0 if currency in ('JPY','KRW') else 3 if currency in ('KWD','BHD') else 2
    symbols = {'USD':'$', 'EUR':'€','GBP':'£','JPY':'¥'}
    try:
        value=Decimal(str(v))
        return f'{symbols.get(currency, currency+" ")}{value:,.{precision}f}' if value.is_finite() else '—'
    except (TypeError, InvalidOperation):
        return '—'


def date_text(): return date.today().isoformat()
def moment_text(): return datetime.now().strftime('%H:%M')
def as_id(value): return str(value) if value not in (None,'') else ''
def active(v): return v in (True, 1, '1', 'true')
def compact(v): return '' if v is None else str(v)
def safe_opener(path):
    if sys.platform == 'win32':
        os.startfile(str(path))
    else:
        webbrowser.open(Path(path).resolve().as_uri())


def spec(key,label,value='',kind='text',choices=(),hint=''):
    return (key,label,value,kind,choices,hint)


class ScrollPane(ttk.Frame):
    def __init__(self,parent,app):
        super().__init__(parent,style='Page.TFrame')
        self.canvas=tk.Canvas(self,background=app.colors['bg'],highlightthickness=0)
        bar=ttk.Scrollbar(self,orient='vertical',command=self.canvas.yview)
        self.canvas.configure(yscrollcommand=bar.set)
        bar.pack(side='right',fill='y'); self.canvas.pack(side='left',fill='both',expand=True)
        self.inner=ttk.Frame(self.canvas,style='Page.TFrame',padding=(12,8,14,12))
        tag=self.canvas.create_window((0,0),window=self.inner,anchor='nw')
        self.canvas.bind('<Configure>',lambda e:self.canvas.itemconfigure(tag,width=e.width))
        self.inner.bind('<Configure>',lambda e:self.canvas.configure(scrollregion=self.canvas.bbox('all')))
        self._motorlog_scroll=self.canvas


class Retry:
    def __init__(self):
        self.items={}
    def key(self, entity, action, data):
        k=(entity,action)
        digest=hashlib.sha256(json.dumps(data,ensure_ascii=False,sort_keys=True).encode('utf-8')).hexdigest()
        if k not in self.items or self.items[k][0]!=digest:
            self.items[k]=(digest,uuid.uuid4().hex)
        return self.items[k][1]
    def success(self,entity,action): self.items.pop((entity,action),None)


class Window:
    def __init__(self,app,title,description='',size='720x650'):
        self.app=app; self.win=tk.Toplevel(app.root); self.win.title(f'{title} · Motorlog'); self.win.geometry(size); self.win.minsize(470,420)
        self.win.configure(background=app.colors['bg']);self.win.transient(app.root)
        self.retry=Retry();self.pending=False;self.origin_url=app.client.url if app.client else None
        head=ttk.Frame(self.win,style='Panel.TFrame',padding=(16,12));head.pack(fill='x')
        ttk.Label(head,text=title,style='Title.TLabel').pack(anchor='w')
        if description: ttk.Label(head,text=description,style='PanelMuted.TLabel',wraplength=640).pack(anchor='w',pady=(3,0))
        self.status_var=tk.StringVar(value='')
        self.status=ttk.Label(self.win,textvariable=self.status_var,style='PageMuted.TLabel',wraplength=620,padding=(13,4))
        self.footer=ttk.Frame(self.win,style='Panel.TFrame',padding=(13,9));self.footer.pack(side='bottom',fill='x')
        self.status.pack(side='bottom',fill='x')
        self.pane=ScrollPane(self.win,app);self.pane.pack(fill='both',expand=True)
        self.body=self.pane.inner
        self.win.protocol('WM_DELETE_WINDOW',self.close)
        self.win.bind('<Escape>',lambda _:self.close())
    def message(self,value,error=False):
        if self.alive():
            self.status_var.set(str(value));self.status.configure(style='Error.TLabel' if error else 'PageMuted.TLabel')
    def alive(self): return bool(self.win.winfo_exists())
    def close(self):
        if not self.pending or messagebox.askyesno('Request running','This request is still running. Close this window anyway?',parent=self.win):self.win.destroy()
    def buttons(self,*buttons):
        for label,cmd,primary in buttons:
            if label: ttk.Button(self.footer,text=label,command=cmd,style='Primary.TButton' if primary else 'TButton').pack(side='right',padx=(5,0))
    def run(self,fn,success,error=None):
        if self.pending: return
        self.pending=True
        def good(result):
            if self.alive():self.pending=False;success(result)
        def bad(e):
            if self.alive():
                self.pending=False;self.message(str(e),True)
                if error:error(e)
        self.app.jobs(fn,good,bad)


class Form(Window):
    def __init__(self,app,title,desc,fields=(),size='680x640'):
        super().__init__(app,title,desc,size)
        self.variables={};self.widgets={};self.containers={};self.choice_map={};self.listeners=[]
        if fields: self.group('',fields)
    def group(self,title,fields):
        panel=ttk.Frame(self.body,style='Panel.TFrame',padding=(12,10));panel.pack(fill='x',pady=(0,8));panel.columnconfigure(0,weight=1);panel.columnconfigure(1,weight=1)
        if title: ttk.Label(panel,text=title,style='Section.TLabel').grid(row=0,column=0,columnspan=2,sticky='w',pady=(0,7))
        row,col=1,0
        for key,label,val,kind,choices,hint in fields:
            double=kind in ('area','full','checkbox')
            if double and col: row+=1; col=0
            cell=ttk.Frame(panel,style='Panel.TFrame',padding=(0,2,10,6));cell.grid(row=row,column=col,columnspan=2 if double else 1,sticky='nsew')
            self.containers[key]=cell
            if kind=='checkbox':
                var=tk.BooleanVar(value=active(val));w=ttk.Checkbutton(cell,text=label,variable=var,style='Panel.TCheckbutton');w.pack(anchor='w');self.variables[key]=var
            else:
                ttk.Label(cell,text=label,style='Field.TLabel').pack(anchor='w',pady=(0,3))
                if kind=='area':
                    w=tk.Text(cell,height=3,width=26,wrap='word',font=('Segoe UI',10),relief='flat',borderwidth=1,highlightthickness=1,highlightbackground=self.app.colors['line'],insertbackground=self.app.colors['ink'],fg=self.app.colors['ink'],bg=self.app.colors['bg']);w.insert('1.0',compact(val));w.pack(fill='x');w.edit_modified(False)
                    w.bind('<<Modified>>',lambda e,k=key:self._text_updated(k,e.widget));self.variables[key]=w
                else:
                    var=tk.StringVar(value=compact(val));self.variables[key]=var
                    if kind in ('select','combo'):
                        w=ttk.Combobox(cell,textvariable=var,state='readonly' if kind=='select' else 'normal',width=18);w.pack(fill='x');self.set_choices(key,choices,w);var.set(self._label(key,compact(val)))
                    else:
                        w=ttk.Entry(cell,textvariable=var,show='•' if kind=='password' else '',width=18);w.pack(fill='x')
                        if kind=='readonly':w.configure(state='readonly')
            self.widgets[key]=w
            if isinstance(self.variables[key],tk.Variable):self.variables[key].trace_add('write',lambda *_args,k=key:self._changed(k))
            if hint:ttk.Label(cell,text=hint,style='PanelMuted.TLabel',wraplength=262).pack(anchor='w',pady=(3,0))
            if double or col:row+=1;col=0
            else:col=1
        return panel
    def set_choices(self,key,choices,widget=None):
        pairs=[(compact(x[0]),compact(x[1])) if isinstance(x,(tuple,list)) else (compact(x),compact(x)) for x in choices]
        seen={}; index={}
        for value,label in pairs:
            text=label if label not in seen else f'{label} [{value}]';seen[text]=value;index[value]=text
        self.choice_map[key]=(seen,index)
        (widget or self.widgets[key]).configure(values=list(seen))
    def _label(self,key,value):return self.choice_map.get(key,({},{}))[1].get(value,value)
    def _text_updated(self,key,w):
        if w.edit_modified():w.edit_modified(False);self._changed(key)
    def _changed(self,key):
        for listener in list(self.listeners):listener(key)
    def get(self,key):
        val=self.variables.get(key)
        if val is None:return ''
        if isinstance(val,tk.Text):return val.get('1.0','end-1c')
        value=val.get()
        return self.choice_map.get(key,({},{}))[0].get(value,value) if isinstance(value,str) else value
    def set(self,key,value):
        v=self.variables.get(key)
        if isinstance(v,tk.Text):v.delete('1.0','end');v.insert('1.0',compact(value))
        elif isinstance(v,tk.BooleanVar):v.set(active(value))
        elif v is not None:v.set(self._label(key,compact(value)))
    def show(self,key,value=True):
        c=self.containers.get(key)
        if c is not None:
            if value:c.grid()
            else:c.grid_remove()
    def values(self):return {key:self.get(key) for key in self.variables}
    def simple(self,entity,action='create',id=None,label='Save changes',extra=None,after=None):
        def submit():
            if self.pending:return
            data=self.values()
            try:
                if extra:data=extra(data)
            except (ValueError,ApiError) as e:
                self.message(e,True);return
            if id is not None:data['id']=id
            self.send(entity,action,data,after)
        self.buttons((label,submit,True),('Cancel',self.close,False))
        self.win.bind('<Control-Return>',lambda _:submit())
        return self
    def send(self,entity,action,data,after=None):
        if not self.app.client or (self.origin_url and self.origin_url!=self.app.client.url):self.message('Reconnect to the original workspace before submitting this form.',True);return
        key=self.retry.key(entity,action,data);client=self.app.client;self.message('Saving…')
        def done(result):
            self.retry.success(entity,action);self.win.destroy();self.app.note(result.get('message','Saved.'));self.app.refresh(after=(lambda:after(result)) if after else None)
        self.run(lambda:client.save(entity,action,data,key),done)


class Application:
    def __init__(self,root):
        self.root=root;self.pref=prefs_read();self.theme=self.pref.get('theme','light');self.colors=DARK if self.theme=='dark' else LIGHT
        self.style=ttk.Style(root);self.style.theme_use('clam');self.pool=ThreadPoolExecutor(max_workers=5);self.queue=queue.Queue();self.closed=False
        self.client=None;self.data=None;self.page='dashboard';self.filters={};self.epoch=0;self.inflight=False;self.notice=tk.StringVar(value='Not connected · connect a Motorlog workspace')
        self.temp=tempfile.TemporaryDirectory(prefix='motorlog-print-');self.load_style()
        self.root.title('Motorlog · Vehicle workspace');self.root.geometry('1200x790');self.root.minsize(790,580)
        self.root.protocol('WM_DELETE_WINDOW',self.close);self.root.after(60,self.pump)
        self.root.bind_all('<MouseWheel>',self.wheel,add='+');self.root.bind_all('<Button-4>',self.wheel,add='+');self.root.bind_all('<Button-5>',self.wheel,add='+')
        self.root.bind('<F5>',lambda _:self.refresh(manual=True));self.shell()
        token=''
        try:token=windows_secret(self.pref.get('token',''),True)
        except (ValueError,OSError):pass
        if self.pref.get('url') and token:
            self.root.after(150,lambda:self.connection(self.pref['url'],token,auto=True))
        else:self.root.after(130,self.connection)
    def load_style(self):
        p=self.colors;s=self.style
        self.root.configure(bg=p['bg']);self.root.option_add('*Font',('Segoe UI',10));self.root.option_add('*TCombobox*Listbox.background',p['surface']);self.root.option_add('*TCombobox*Listbox.foreground',p['ink'])
        s.configure('.',font=('Segoe UI',10),foreground=p['ink'],background=p['surface'],bordercolor=p['line'],fieldbackground=p['bg'],arrowcolor=p['muted'],focuscolor=p['primary'])
        s.configure('Page.TFrame',background=p['bg']);s.configure('Panel.TFrame',background=p['surface']);s.configure('Side.TFrame',background=p['surface'])
        for n,bg,fg,font in [('Page',p['bg'],p['ink'],10),('PageMuted',p['bg'],p['muted'],9),('Error',p['bg'],p['bad'],10),('Success',p['bg'],p['good'],10),('PanelMuted',p['surface'],p['muted'],9),('Field',p['surface'],p['ink'],10),('Section',p['surface'],p['ink'],12),('Title',p['surface'],p['ink'],20),('Dashboard',p['bg'],p['ink'],25),('Mark',p['surface'],p['primary'],22)]:
            s.configure(f'{n}.TLabel',background=bg,foreground=fg,font=('Segoe UI',font,'bold' if n in ('Field','Section','Title','Dashboard','Mark') else 'normal'))
        s.configure('Metric.TLabel',background=p['surface'],foreground=p['ink'],font=('Segoe UI',21,'bold'))
        s.configure('TButton',padding=(9,6),background=p['surface'],foreground=p['ink'],bordercolor=p['line']);s.map('TButton',background=[('active',p['soft'])],foreground=[('disabled',p['muted'])])
        s.configure('Primary.TButton',background=p['primary'],foreground='#ffffff',padding=(11,6),bordercolor=p['primary']);s.map('Primary.TButton',background=[('active','#7357eb'),('disabled',p['muted'])],foreground=[('disabled','#ffffff')])
        s.configure('Nav.TButton',anchor='w',padding=(12,8),borderwidth=0,background=p['surface'],foreground=p['muted']);s.map('Nav.TButton',background=[('active',p['soft'])])
        s.configure('Active.Nav.TButton',anchor='w',padding=(12,8),borderwidth=0,background=p['soft'],foreground=p['primary'],font=('Segoe UI',10,'bold'));s.map('Active.Nav.TButton',background=[('active',p['soft'])])
        s.configure('Panel.TCheckbutton',background=p['surface'],foreground=p['ink']);s.configure('Panel.TRadiobutton',padding=(3,3),background=p['surface'],foreground=p['ink']);s.map('Panel.TRadiobutton',background=[('active',p['soft'])])
        s.configure('TEntry',padding=5);s.configure('TCombobox',padding=5);s.map('TCombobox',fieldbackground=[('readonly',p['bg'])],selectforeground=[('readonly',p['ink'])],selectbackground=[('readonly',p['bg'])])
        s.configure('Treeview',rowheight=29,background=p['surface'],fieldbackground=p['surface'],foreground=p['ink']);s.configure('Treeview.Heading',font=('Segoe UI',9,'bold'),background=p['subtle'],foreground=p['muted'],padding=(6,6));s.map('Treeview',background=[('selected',p['soft'])],foreground=[('selected',p['ink'])]);s.configure('TProgressbar',background=p['primary'],troughcolor=p['subtle'])
    def wheel(self,e):
        try: w=self.root.winfo_containing(e.x_root,e.y_root)
        except tk.TclError:return
        while w is not None:
            if isinstance(w,(tk.Text,ttk.Treeview,ttk.Combobox)):return
            pane=getattr(w,'_motorlog_scroll',None)
            if pane is not None:
                pane.yview_scroll(-1 if (e.num==4 or (e.num!=5 and e.delta>0)) else 1,'units');return 'break'
            w=getattr(w,'master',None)
    def jobs(self,fn,ok,bad=None):
        if self.closed:return
        f=self.pool.submit(fn)
        f.add_done_callback(lambda fut:self.queue.put((fut,ok,bad)))
    def pump(self):
        if self.closed:return
        for _ in range(35):
            try: fut,good,bad=self.queue.get_nowait()
            except queue.Empty:break
            try: result=fut.result()
            except Exception as e:
                if bad:bad(e)
                else:self.note(str(e),True)
            else:
                try:good(result)
                except tk.TclError:pass
                except Exception as e:self.note('The action could not finish: '+str(e),True)
        self.root.after(60,self.pump)
    def close(self):
        if any(isinstance(w,tk.Toplevel) for w in self.root.winfo_children()) and not messagebox.askyesno('Close Motorlog?','Any fields not saved to the website will be lost.',parent=self.root):return
        self.closed=True;self.pool.shutdown(wait=False,cancel_futures=True)
        try:self.temp.cleanup()
        except OSError:pass
        self.root.destroy()
    def note(self,text,error=False):
        self.notice.set(str(text));self.notice_label.configure(style='Error.TLabel' if error else 'PageMuted.TLabel')
    def toggle(self):
        self.theme='light' if self.theme=='dark' else 'dark';self.pref['theme']=self.theme;prefs_save(self.pref);self.colors=DARK if self.theme=='dark' else LIGHT;self.load_style();self.shell()
    def shell(self):
        for widget in self.root.winfo_children():
            if not isinstance(widget,tk.Toplevel):widget.destroy()
        side=ttk.Frame(self.root,style='Side.TFrame',width=205,padding=(12,12));side.pack(side='left',fill='y');side.pack_propagate(False)
        ttk.Label(side,text='Motorlog.',style='Mark.TLabel').pack(anchor='w',padx=8)
        ttk.Label(side,text='VEHICLE WORKSPACE',style='PanelMuted.TLabel').pack(anchor='w',padx=8,pady=(1,13))
        self.nav={}
        for k,name in SECTIONS:
            b=ttk.Button(side,text=name,command=lambda p=k:self.navigate(p),style='Nav.TButton');b.pack(fill='x',pady=1);self.nav[k]=b
        ttk.Button(side,text='Connection settings',command=self.connection,style='TButton').pack(side='bottom',fill='x',pady=(4,0))
        ttk.Label(side,text=f'Windows desktop · v{VERSION}',style='PanelMuted.TLabel').pack(side='bottom',anchor='w',padx=3,pady=(0,4))
        main=ttk.Frame(self.root,style='Page.TFrame');main.pack(fill='both',expand=True)
        bar=ttk.Frame(main,style='Panel.TFrame',padding=(13,8));bar.pack(fill='x')
        self.top_title=ttk.Label(bar,text='Workspace',style='Section.TLabel');self.top_title.pack(side='left')
        ttk.Button(bar,text='Light mode' if self.theme=='dark' else 'Dark mode',command=self.toggle).pack(side='right',padx=3)
        ttk.Button(bar,text='Refresh  ·  F5',command=lambda:self.refresh(manual=True)).pack(side='right',padx=3)
        self.notice_label=ttk.Label(main,textvariable=self.notice,style='PageMuted.TLabel',padding=(13,4),wraplength=720);self.notice_label.pack(side='bottom',fill='x')
        self.pane=ScrollPane(main,self);self.pane.pack(fill='both',expand=True);self.body=self.pane.inner
        self.render()
    def navigate(self,p):
        self.page=p;self.render();self.pane.canvas.yview_moveto(0)
    def render(self):
        for w in self.body.winfo_children():w.destroy()
        for key,b in self.nav.items():b.configure(style='Active.Nav.TButton' if key==self.page else 'Nav.TButton')
        self.top_title.configure(text=(self.data['settings']['site_name'] if self.data else 'Workspace')+'  /  '+dict(SECTIONS).get(self.page,''))
        if not self.data:
            self.head('Connect your workspace','Use the website URL and a desktop API token created in the website Settings.',('Connect',self.connection,True))
            ttk.Label(self.body,text='The desktop app does not keep a separate database. Log in to the website first and create a token.',style='PageMuted.TLabel',wraplength=530).pack(anchor='w',pady=12)
            return
        methods={'dashboard':self.dashboard,'vehicles':self.vehicles_page,'trips':self.trips_page,'expenses':self.expenses_page,'mechanics':self.mechanics_page,'drivers':self.drivers_page,'inspection':lambda:self.documents_page('inspection'),'sale':lambda:self.documents_page('sale'),'settings':self.settings_page}
        methods[self.page]()
    def refresh(self,manual=False,after=None):
        if not self.client:
            if manual:self.connection()
            return
        client=self.client;epoch=self.epoch
        if manual:self.note('Refreshing workspace…')
        def ok(data):
            if epoch!=self.epoch:return
            self.data=data;self.render()
            if manual:self.note('Workspace refreshed · connected to '+client.url)
            if after:after()
        def err(e):
            if epoch==self.epoch:self.note(str(e),True)
        self.jobs(lambda:client.request('state'),ok,err)
    def connection(self,url='',token='',auto=False):
        found=getattr(self,'connect_form',None)
        if found and found.alive():found.win.lift();return
        f=Form(self,'Connection settings','Test website availability and token authentication. Create a token in the authenticated website Settings.',[
            spec('url','Website URL',url or (self.client.url if self.client else self.pref.get('url','http://localhost/motorlog/')),'full'),
            spec('token','API token',token or (self.client.token if self.client else ''),'password'),
            spec('remember','Remember token with Windows account encryption',bool(self.pref.get('token')),'checkbox')],size='595x485')
        self.connect_form=f
        if sys.platform!='win32':f.show('remember',False)
        ttk.Label(f.body,text='Use HTTPS for a remote server. Local XAMPP can use http://localhost. A remembered token is encrypted for the current Windows account.',style='PageMuted.TLabel',wraplength=520).pack(anchor='w',pady=4)
        def connect():
            if f.pending:return
            try: client=Client(f.get('url'),f.get('token'))
            except ApiError as e:f.message(e,True);return
            if not client.token:f.message('Enter the API token generated in website Settings.',True);return
            remember=f.get('remember');f.message('Checking server and authenticating…')
            previous=self.client.url if self.client else None
            def ok(data):
                self.epoch+=1;self.client=client;self.data=data
                if previous and previous!=client.url:self.filters={};self.page='dashboard'
                self.pref['url']=client.url;self.pref.pop('token',None)
                if remember:
                    try:
                        secret=windows_secret(client.token)
                        if secret:self.pref['token']=secret
                    except (OSError,ValueError):pass
                prefs_save(self.pref);f.win.destroy();self.render();self.note('Connected to '+client.url);self.check_updates(False)
            f.run(client.connect,ok)
        f.buttons(('Test and connect',connect,True),('Cancel',f.close,False));f.win.bind('<Return>',lambda _:connect())
        if auto:self.root.after(90,connect)
    def getrow(self,key,id):return next((r for r in self.data[key] if as_id(r['id'])==as_id(id)),None)
    def vchoices(self,first='Select vehicle'):return [('',first),*((v['id'],v['name']) for v in self.data['vehicles'])]
    def dchoices(self,d='',all=False,first='Unassigned'):return [('',first),*((x['id'],x['name']+(' (inactive)' if not active(x['active']) else '')) for x in self.data['drivers'] if all or active(x['active']) or as_id(x['id'])==as_id(d))]
    @property
    def unit(self):return self.data['settings']['unit']
    @property
    def currency(self):return self.data['settings']['currency']
    def amount(self,v,c=None):return fmt_money(v,c or self.currency)
    def head(self,title,description,*buttons):
        f=ttk.Frame(self.body,style='Page.TFrame');f.pack(fill='x',pady=(0,14));left=ttk.Frame(f,style='Page.TFrame');left.pack(side='left',fill='x',expand=True)
        ttk.Label(left,text=title,style='Dashboard.TLabel').pack(anchor='w');ttk.Label(left,text=description,style='PageMuted.TLabel',wraplength=500).pack(anchor='w',pady=(2,0))
        for label,fn,primary in reversed(buttons):ttk.Button(f,text=label,command=fn,style='Primary.TButton' if primary else 'TButton').pack(side='right',padx=3)
    def panel(self,parent,title,description=''):
        box=ttk.Frame(parent,style='Panel.TFrame',padding=(11,9));box.pack(fill='x',pady=(0,9))
        if title:ttk.Label(box,text=title,style='Section.TLabel').pack(anchor='w',pady=(0,5))
        if description:ttk.Label(box,text=description,style='PanelMuted.TLabel',wraplength=640).pack(anchor='w',pady=(0,5))
        return box
    def table(self,parent,cols,records,actions=(),open_row=None,height=7,empty='No records yet.'):
        rows=list(records);holder=ttk.Frame(parent,style='Panel.TFrame');holder.pack(fill='x')
        ids=[f'col{i}' for i in range(len(cols))]; t=ttk.Treeview(holder,columns=ids,show='headings',height=min(height,max(1,len(rows))),selectmode='browse')
        y=ttk.Scrollbar(holder,orient='vertical',command=t.yview);x=ttk.Scrollbar(holder,orient='horizontal',command=t.xview);t.configure(yscrollcommand=y.set,xscrollcommand=x.set)
        t.grid(row=0,column=0,sticky='nsew');y.grid(row=0,column=1,sticky='ns');x.grid(row=1,column=0,sticky='ew');holder.columnconfigure(0,weight=1)
        for cid,col in zip(ids,cols):
            label,width=(col if isinstance(col,tuple) else (col,120));t.heading(cid,text=label,anchor='w');t.column(cid,width=width,minwidth=62,stretch=True,anchor='w')
        for key,vals in rows:t.insert('','end',iid=str(key),values=[compact(v) for v in vals])
        if not rows:ttk.Label(parent,text=empty,style='PanelMuted.TLabel',wraplength=720).pack(anchor='w',pady=(6,2))
        def perform(fn):
            pick=t.selection()
            if pick:fn(pick[0])
            else:self.note('Select a row first.',True)
        if actions:
            toolbar=ttk.Frame(parent,style='Panel.TFrame');toolbar.pack(fill='x',pady=(7,0))
            for label,fn in actions:ttk.Button(toolbar,text=label,command=lambda f=fn:perform(f)).pack(side='left',padx=(0,5))
        if open_row:t.bind('<Double-1>',lambda _:perform(open_row));t.bind('<Return>',lambda _:perform(open_row))
        return t
    def lines(self,parent,mapping,style='PanelMuted.TLabel'):
        for label,val in mapping.items():ttk.Label(parent,text=f'{label}: {compact(val) or "—"}',style=style,wraplength=690,justify='left').pack(anchor='w',pady=2)
    def confirm_write(self,title,question,entity,action,data,after=None):
        if not messagebox.askyesno(title,question,parent=self.root):return
        if not hasattr(self,'confirm_retry'):self.confirm_retry=Retry()
        client=self.client;key=self.confirm_retry.key(entity,action,data)
        def good(r):self.confirm_retry.success(entity,action);self.note(r.get('message','Saved.'));self.refresh(after=(lambda:after(r)) if after else None)
        self.jobs(lambda:client.save(entity,action,data,key),good,lambda e:self.note(str(e),True))
    def binary(self,route,params,filename,open_file=False,parent=None):
        if not self.client:return
        if open_file:path=Path(self.temp.name)/f'{uuid.uuid4().hex[:6]}-{filename}'
        else:
            dest=filedialog.asksaveasfilename(parent=parent or self.root,initialfile=filename,defaultextension=Path(filename).suffix)
            if not dest:return
            path=Path(dest)
        client=self.client;self.note('Preparing '+filename+'…')
        def work():
            content=client.request(route,params,raw=True)
            if route=='pdf' and not content.startswith(b'%PDF'):raise ApiError('The server did not return a PDF.')
            path.write_bytes(content);return path
        def ok(p):
            self.note('Exported '+filename)
            if open_file:
                try:safe_opener(p)
                except OSError as e:self.note('Could not open the PDF viewer: '+str(e),True)
        self.jobs(work,ok,lambda e:self.note(e,True))
    def snapshot(self,v):
        if not v:return {}
        return {k:v[k] for k in ('model_year','make','model','vin','plate','color')}|{'vehicle_id':v['id'],'odometer':v['current_odometer'],'unit':self.unit}

    def dashboard(self):
        d=self.data;tot=d['totals'];n=len(d['vehicles']);done=sum(v['inspection']['state'] in ('clear','findings') for v in d['vehicles'])
        self.head('Fleet overview','A quick look at your vehicles, trips, and maintenance.',('Log trip',self.trip_form,False),('Add vehicle',self.vehicle_form,True))
        metrics=ttk.Frame(self.body,style='Page.TFrame');metrics.pack(fill='x',pady=(0,10))
        for i,(name,value) in enumerate([('Total vehicles',n),('Distance logged',f'{fmt_num(tot["distance"])} {self.unit}'),('Expenses',self.amount(tot['expenses'])),('Weekly inspections',f'{done} / {n} complete')]):
            metrics.columnconfigure(i,weight=1);card=ttk.Frame(metrics,style='Panel.TFrame',padding=(10,8));card.grid(row=0,column=i,sticky='nsew',padx=(0,5));ttk.Label(card,text=name,style='PanelMuted.TLabel').pack(anchor='w');ttk.Label(card,text=str(value),style='Metric.TLabel').pack(anchor='w')
        p=self.panel(self.body,'Recent trips')
        self.table(p,['Vehicle','Purpose','Date','Distance','Driver'],[(r['id'],[r['vehicle_name'],r['purpose'],r['trip_date'],f'{fmt_num(r["distance"])} {self.unit}',r['driver_name'] or 'Unassigned']) for r in d['trips'][:5]],open_row=lambda i:self.trip_form(self.getrow('trips',i)),height=5)
        p=self.panel(self.body,'Vehicle mileage & weekly status',f'{self.amount(tot["debt"])} mechanic debt · {tot["active_drivers"]} active drivers · reporting US MPG')
        self.table(p,[('Vehicle',166),'Odometer','Engine','US MPG',('This week',186),'Issues','Missing'],[(v['id'],[v['name'],f'{fmt_num(v["current_odometer"])} {self.unit}',f'{fmt_num(v["engine_odometer"])} {self.unit}',fmt_num(v['mpg'],2),v['inspection']['status'],v['inspection']['unresolved'],'Overdue' if v['inspection']['overdue'] else '—']) for v in d['vehicles']],open_row=self.vehicle_view,height=5)
        p=self.panel(self.body,'Driver distance')
        self.table(p,['Driver','Trips','Distance','Status'],[(r['id'],[r['name'],r['trip_count'],f'{fmt_num(r["distance"])} {self.unit}','Active' if active(r['active']) else 'Inactive']) for r in d['drivers']],open_row=self.driver_view,height=3)

    def vehicles_page(self):
        self.head('Vehicles','Manage vehicle profiles, protected photos, and engine histories.',('Add vehicle',self.vehicle_form,True))
        p=self.panel(self.body,'Vehicle directory','Highest validated odometers are retained even if a trip or fuel entry is edited or deleted.')
        rows=[(v['id'],[v['name'],v['plate'] or '—',v['color'] or '—',fmt_num(v['current_odometer']),fmt_num(v['engine_odometer']),fmt_num(v['mpg'],2)]) for v in self.data['vehicles']]
        self.table(p,[('Vehicle',195),'Plate','Color',f'Odometer ({self.unit})','Engine','US MPG'],rows,actions=[('View vehicle',self.vehicle_view),('Edit profile',lambda i:self.vehicle_form(self.getrow('vehicles',i))),('Upload cover / photo',lambda i:self.photo_upload('vehicle',int(i)))],open_row=self.vehicle_view)
    def vehicle_form(self,v=None):
        v=v or {};f=Form(self,'Edit vehicle' if v else 'Add vehicle','The make/model catalog is supplied locally by the website. Other allows manual entry.',size='760x710')
        f.group('Vehicle identification',[spec('kind','Vehicle type',v.get('kind','car'),'select',[('car','Passenger automobile'),('motorcycle','Motorcycle')]),spec('model_year','Year',v.get('model_year')),spec('_make_choice','Catalog make','','combo'),spec('_model_choice','Catalog model','','combo'),spec('make','Manual make',v.get('make')),spec('model','Manual model',v.get('model')),spec('vin','VIN / chassis number',v.get('vin')),spec('plate','Plate',v.get('plate')),spec('color','Color',v.get('color')),spec('notes','Notes',v.get('notes'),'area')])
        if not v:f.group('Vehicle and engine baselines',[spec('initial_odometer',f'Vehicle baseline ({self.unit})'),spec('at_odometer','Vehicle odometer at installation',hint='Blank uses the vehicle baseline.'),spec('at_engine','Engine baseline','0'),spec('installed_date','Installation date',hint='YYYY-MM-DD, optional'),spec('serial_number','Engine serial number'),spec('engine_notes','Engine notes','','area')])
        self.vehicle_catalog(f,bool(v))
        f.simple('vehicle','update' if v else 'create',v.get('id'),'Save vehicle',extra=lambda d:{k:x for k,x in d.items() if not k.startswith('_')})
    def vehicle_catalog(self,f,keep):
        version=[0];makes=[[]];models=[[]];client=self.client; custom=('_manual','Other / enter manually')
        cm=f.widgets['_make_choice'];cs=f.widgets['_model_choice']
        def display_choices(key,entries):
            f.set_choices(key,[('','Select…'),*((x['id'],x['name']) for x in entries),custom])
        def model_load(preserve=False):
            version[0]+=1;seq=version[0];selected=f.get('_make_choice');kind=f.get('kind');old=f.get('model') if preserve else ''
            f.set('model',old);models[0]=[];display_choices('_model_choice',[]);f.set('_model_choice','');cs.configure(state='disabled')
            f.show('model',selected=='_manual' or bool(old))
            if not str(selected).isdigit():
                selected_match=next((x for x in makes[0] if x['name'].casefold()==str(selected).casefold()),None)
                if selected_match:f.set('_make_choice',selected_match['id']);return model_load(preserve)
                if selected=='_manual':f.show('model');return
                if selected:f.set('make',selected);f.set('_make_choice','_manual');f.show('make');f.show('model')
                return
            m=next((r for r in makes[0] if as_id(r['id'])==as_id(selected)),None)
            if m and not preserve:f.set('make',m['name'])
            def loaded(r):
                if not f.alive() or seq!=version[0] or kind!=f.get('kind') or selected!=f.get('_make_choice'):return
                models[0]=r['models'];display_choices('_model_choice',models[0]);cs.configure(state='normal')
                match=next((x for x in models[0] if x['name'].casefold()==old.casefold()),None)
                f.set('_model_choice',match['id'] if match else '_manual' if old or not models[0] else '')
                f.show('model',not match and (bool(old) or not models[0]));f.message('Local model list loaded.' if models[0] else 'There are no catalog models for this make and type. Enter a model manually.')
            def failed(e):
                if f.alive() and seq==version[0]:cs.configure(state='normal');f.set('_model_choice','_manual');f.show('model');f.message(e,True)
            self.jobs(lambda:client.request('catalog',{'type':kind,'make':selected}),loaded,failed)
        def reload(preserve=False):
            version[0]+=1;seq=version[0];kind=f.get('kind');old=f.get('make') if preserve else ''
            if not preserve:f.set('make','');f.set('model','')
            cm.configure(state='disabled');cs.configure(state='disabled');f.set('_make_choice','');f.set('_model_choice','');f.message('Loading the local catalog…')
            def ok(r):
                if not f.alive() or seq!=version[0] or f.get('kind')!=kind:return
                makes[0]=r['makes'];display_choices('_make_choice',makes[0]);cm.configure(state='normal')
                match=next((x for x in makes[0] if x['name'].casefold()==old.casefold()),None)
                f.set('_make_choice',match['id'] if match else '_manual' if old else '');f.show('make',bool(old) and not match);f.show('model',bool(old) and not match)
                f.message(f'{len(makes[0]):,} local makes · type to search or choose Other.')
                if match:model_load(True)
            def bad(e):
                if f.alive() and seq==version[0]:display_choices('_make_choice',[]);cm.configure(state='normal');f.set('_make_choice','_manual');f.show('make');f.show('model');f.message(str(e)+' Manual entry remains available.',True)
            self.jobs(lambda:client.request('catalog',{'type':kind}),ok,bad)
        def chosen_make(_=None):
            selected=f.get('_make_choice');f.set('make','');f.show('make',selected=='_manual');model_load()
        def chosen_model(_=None):
            val=f.get('_model_choice');m=next((m for m in models[0] if str(m['id'])==str(val) or m['name'].casefold()==str(val).casefold()),None)
            f.set('model',m['name'] if m else '');f.show('model',val=='_manual' or bool(val and not m))
            if val and not m and val!='_manual':f.set('model',val);f.set('_model_choice','_manual')
        def filter_values(e,which,entries):
            if e.keysym in ('Return','Tab','Up','Down','Escape'):return
            text=e.widget.get().casefold()
            if which=='_make_choice':
                version[0]+=1;f.set('make','');f.set('model','');f.set('_model_choice','');cs.configure(state='disabled')
            filtered=[item for item in entries if text in item['name'].casefold()]
            display_choices(which,filtered)
        cm.bind('<<ComboboxSelected>>',chosen_make);cm.bind('<Return>',chosen_make);cs.bind('<<ComboboxSelected>>',chosen_model);cs.bind('<Return>',chosen_model)
        cm.bind('<KeyRelease>',lambda e:filter_values(e,'_make_choice',makes[0]));cs.bind('<KeyRelease>',lambda e:filter_values(e,'_model_choice',models[0]));f.widgets['kind'].bind('<<ComboboxSelected>>',lambda _:reload())
        reload(keep)
    def vehicle_view(self,id):
        v=self.getrow('vehicles',id)
        if not v:return
        w=Window(self,v['name'],'Vehicle profile · independent engine mileage',size='780x680')
        p=self.panel(w.body,'Identification and mileage')
        self.lines(p,{'VIN':v['vin'],'License plate':v['plate'],'Color':v['color'],'Notes':v['notes'],'Odometer':f'{fmt_num(v["current_odometer"])} {self.unit}','Engine mileage':f'{fmt_num(v["engine_odometer"])} {self.unit}','Fuel economy':fmt_num(v['mpg'],2)+' US MPG' if v['mpg'] is not None else '—'})
        p=self.panel(w.body,'Engine history','Retired engines remain at their mileage at replacement.')
        self.table(p,[('Installation',126),'Status','Vehicle odo','Baseline','Mileage',('Serial / reason',190)],[(e['id'],[e['installed_date'] or 'Baseline','Retired '+e['retired_date'] if e['retired_date'] else 'Installed',fmt_num(e['at_odometer']),fmt_num(e['at_engine']),fmt_num(e['current_mileage']),f'{e["serial_number"]} {e["reason"]}']) for e in v['engines']],height=4,
            open_row=lambda eid:self.engine_details(next(e for e in v['engines'] if as_id(e['id'])==as_id(eid))))
        p=self.panel(w.body,'Protected photo gallery')
        def open_pic(pid):self.photo_view(pid,next((x['caption'] for x in v['photos'] if as_id(x['id'])==as_id(pid)),''))
        self.table(p,['View',('Caption',260),'Cover'],[(pic['id'],[pic['view_label'],pic['caption'],'Yes' if as_id(pic['id'])==as_id(v['cover_id']) else '']) for pic in v['photos']],actions=[('Preview photo',open_pic),('Set cover',lambda pid:self.confirm_write('Set cover','Use this photo as the vehicle cover?','vehicle','cover',{'id':int(id),'photo_id':int(pid)},lambda _:(w.win.destroy(),self.vehicle_view(id))))],open_row=open_pic,height=4)
        if v['cover_id']:ttk.Button(p,text='Preview current cover',command=lambda:open_pic(v['cover_id'])).pack(anchor='w',pady=(5,0))
        w.buttons(('Upload photo',lambda:self.photo_upload('vehicle',int(id),after=lambda _:(w.win.destroy(),self.vehicle_view(id))),True),('Replace engine',lambda:self.engine_form(v,w),False),('Edit profile',lambda:self.vehicle_form(v),False))
    def engine_details(self,e):
        w=Window(self,'Engine details');p=self.panel(w.body,'Installation and replacement');self.lines(p,{'Installed':e['installed_date'],'Installation odometer':e['at_odometer'],'Engine baseline':e['at_engine'],'Serial number':e['serial_number'],'Reason':e['reason'],'Notes':e['notes'],'Retired':e['retired_date'],'Retired odometer':e['retired_odometer'],'Calculated mileage':e['current_mileage']})
    def engine_form(self,v,w=None):
        f=Form(self,'Record engine replacement',v['name'],[spec('installed_date','Replacement date',date_text()),spec('at_odometer',f'Vehicle odometer ({self.unit})',v['current_odometer']),spec('at_engine','Replacement engine baseline','0'),spec('serial_number','Serial number'),spec('reason','Replacement reason'),spec('notes','Notes','','area')]);f.simple('vehicle','replace_engine',v['id'],'Record replacement',after=(lambda _:(w.win.destroy(),self.vehicle_view(v['id']))) if w else None)

    def filter_form(self,key,fields):
        f=Form(self,'Filter records','Use any combination of fields. Leave a field blank to include all values.',fields,size='575x485')
        def apply():
            data=f.values()
            try:
                for k in ('from','to','week'):
                    if data.get(k):date.fromisoformat(data[k])
                if data.get('from') and data.get('to') and data['from']>data['to']:raise ValueError()
            except ValueError:f.message('Use real dates (YYYY-MM-DD) and a start date before the end date.',True);return
            self.filters[key]=data;f.win.destroy();self.render()
        f.buttons(('Apply filters',apply,True),('Clear filters',lambda:(self.filters.pop(key,None),f.win.destroy(),self.render()),False))
    def fleet_filter(self,key):
        f=self.filters.get(key,{});fields=[spec('vehicle_id','Vehicle',f.get('vehicle_id'),'select',self.vchoices('All vehicles'))]
        if key=='trips':fields+=[spec('driver_id','Driver',f.get('driver_id'),'select',self.dchoices(all=True,first='All drivers')+[('unassigned','Unassigned')])]
        else:fields+=[spec('category','Category',f.get('category'),'select',[('','All categories'),*self.data['options']['categories']])]
        fields += [spec('from','From date',f.get('from'),hint='YYYY-MM-DD'),spec('to','To date',f.get('to'),hint='YYYY-MM-DD')];self.filter_form(key,fields)
    def filtered_rows(self,key,day_key):
        f=self.filters.get(key,{})
        return [r for r in self.data[key] if (not f.get('vehicle_id') or as_id(r['vehicle_id'])==as_id(f['vehicle_id'])) and (not f.get('category') or r.get('category')==f['category']) and (not f.get('driver_id') or (not r.get('driver_id') if f['driver_id']=='unassigned' else as_id(r.get('driver_id'))==f['driver_id'])) and (not f.get('from') or r[day_key]>=f['from']) and (not f.get('to') or r[day_key]<=f['to'])]
    def trips_page(self):
        self.head('Trip log','Review trips, driver attribution, and validated odometer readings.',('Export CSV',lambda:self.binary('trip-csv',self.filters.get('trips',{}),'motorlog-trips.csv'),False),('Log trip',self.trip_form,True))
        found=self.filtered_rows('trips','trip_date');p=self.panel(self.body,'Trip history',f'{len(found)} trips · {fmt_num(sum(float(x["distance"]) for x in found))} {self.unit} total distance')
        self.table(p,['Date',('Vehicle',163),('Purpose',150),'Driver','Start','End','Distance'],[(r['id'],[r['trip_date'],r['vehicle_name'],r['purpose'],r['driver_name'] or 'Unassigned',fmt_num(r['start_odometer']),fmt_num(r['end_odometer']),f'{fmt_num(r["distance"])} {self.unit}']) for r in found],actions=[('Edit selected',lambda id:self.trip_form(self.getrow('trips',id))),('Delete selected',lambda id:self.confirm_write('Delete trip?','The highest validated odometer is retained.','trip','delete',{'id':int(id)}))],open_row=lambda id:self.trip_form(self.getrow('trips',id)),height=12)
        ttk.Button(p,text='Filter by vehicle, driver or date',command=lambda:self.fleet_filter('trips')).pack(anchor='w',pady=(7,0))
    def trip_form(self,r=None):
        r=r or {};v=self.getrow('vehicles',r.get('vehicle_id')) or next(iter(self.data['vehicles']),{})
        f=Form(self,'Edit trip' if r else 'Log trip','Distance is the end reading minus the start reading.',[spec('vehicle_id','Vehicle',v.get('id'),'select',self.vchoices()),spec('driver_id','Driver',r.get('driver_id'),'select',self.dchoices(r.get('driver_id'))),spec('trip_date','Trip date',r.get('trip_date',date_text())),spec('purpose','Purpose',r.get('purpose')),spec('start_odometer',f'Starting odometer ({self.unit})',r.get('start_odometer',v.get('current_odometer'))),spec('end_odometer',f'Ending odometer ({self.unit})',r.get('end_odometer')),spec('notes','Notes',r.get('notes'),'area')]);vlabel=tk.StringVar()
        ttk.Label(f.body,textvariable=vlabel,style='PageMuted.TLabel').pack(anchor='w',pady=4)
        def change(k):
            if k=='vehicle_id' and not r:
                v2=self.getrow('vehicles',f.get('vehicle_id')) or {};f.set('start_odometer',v2.get('current_odometer'))
            try:
                result=Decimal(f.get('end_odometer'))-Decimal(f.get('start_odometer'));vlabel.set(f'Distance: {fmt_num(result)} {self.unit}' if result.is_finite() and result>=0 else 'Distance: —')
            except InvalidOperation:vlabel.set('Distance: —')
        f.listeners.append(change);change('load');f.simple('trip','update' if r else 'create',r.get('id'),'Save trip')
    def expenses_page(self):
        def fuel_filter():
            d=self.filters.setdefault('expenses',{});d['category']='' if d.get('category')=='Fuel' else 'Fuel';self.render()
        found=self.filtered_rows('expenses','expense_date');fuel=self.filters.get('expenses',{}).get('category')=='Fuel'
        self.head('Fuel & expenses','Review running costs and fuel intervals separately for each vehicle.',('Export CSV',lambda:self.binary('expense-csv',self.filters.get('expenses',{}),'motorlog-expenses.csv'),False),('Log fuel',lambda:self.expense_form(category='Fuel'),False),('Add expense',self.expense_form,True))
        total=sum((Decimal(r['amount']) for r in found),Decimal('0'));p=self.panel(self.body,'Fuel history (US MPG)' if fuel else 'Expense history',f'{len(found)} entries · {self.amount(total)} filtered total')
        if fuel:
            cols=['Date',('Vehicle',145),'Odometer','Gallons (US)','Per gallon','Total','US MPG',('Interval',225)]
            records=[(r['id'],[r['expense_date'],r['vehicle_name'],fmt_num(r['odometer']),fmt_num(r['gallons']),fmt_money(r['price_per_gallon'],self.currency,4),self.amount(r['amount']),fmt_num(r['mpg'],2),r['fuel_interval']]) for r in found]
        else:
            cols=['Date',('Vehicle',159),'Category',('Vendor',132),('Description',180),'Amount']
            records=[(r['id'],[r['expense_date'],r['vehicle_name'],r['category'],r['vendor'],r['description'],self.amount(r['amount'])]) for r in found]
        def edit(id):
            r=self.getrow('expenses',id)
            if r.get('invoice_id'):self.invoice_view(r['invoice_id'])
            else:self.expense_form(r)
        def delete(id):
            if self.getrow('expenses',id).get('invoice_id'):self.note('Edit this invoice-linked expense in Mechanics.',True)
            else:self.confirm_write('Delete expense?','Affected full-tank MPG intervals will recalculate.','expense','delete',{'id':int(id)})
        self.table(p,cols,records,actions=[('Open / edit',edit),('Delete selected',delete)],open_row=edit,height=11)
        actions=ttk.Frame(p,style='Panel.TFrame');actions.pack(fill='x',pady=7)
        ttk.Button(actions,text='Filter expenses',command=lambda:self.fleet_filter('expenses')).pack(side='left',padx=(0,6));ttk.Button(actions,text='All expenses' if fuel else 'Fuel history only',command=fuel_filter).pack(side='left')
    def expense_form(self,r=None,category='Maintenance'):
        r=r or {};v=self.getrow('vehicles',r.get('vehicle_id')) or next(iter(self.data['vehicles']),{});category=r.get('category',category)
        f=Form(self,'Edit expense' if r else 'Log fuel fill' if category=='Fuel' else 'Add expense','Fuel expense totals are validated and recalculated by the website.',[
            spec('vehicle_id','Vehicle',v.get('id'),'select',self.vchoices()),spec('expense_date','Date',r.get('expense_date',date_text())),spec('category','Category',category,'select',self.data['options']['categories']),spec('vendor','Vendor',r.get('vendor')),spec('amount',f'Amount ({self.currency})',r.get('amount') if category!='Fuel' else ''),spec('odometer',f'Odometer ({self.unit})',r.get('odometer') or v.get('current_odometer')),spec('gallons','Gallons (US)',r.get('gallons')),spec('price_per_gallon','Price per gallon',r.get('price_per_gallon')),spec('full_tank','Reached a full tank',r.get('full_tank'),'checkbox'),spec('description','Description or fuel notes',r.get('description'),'area')]);msg=tk.StringVar();last=[category]
        ttk.Label(f.body,textvariable=msg,style='PageMuted.TLabel').pack(anchor='w',pady=3)
        def change(key):
            cat=f.get('category');fuel=cat=='Fuel'
            if cat!=last[0]:
                last[0]=cat
                if not fuel:
                    for k in ('odometer','gallons','price_per_gallon','full_tank'):f.set(k,'')
                else:
                    vv=self.getrow('vehicles',f.get('vehicle_id')) or {};f.set('odometer',vv.get('current_odometer'));f.set('amount','')
            for k in ('odometer','gallons','price_per_gallon','full_tank'):f.show(k,fuel)
            f.show('amount',not fuel)
            if key=='vehicle_id' and not r and fuel:
                vv=self.getrow('vehicles',f.get('vehicle_id')) or {};f.set('odometer',vv.get('current_odometer'))
            if not fuel:msg.set('');return
            try:
                g=Decimal(f.get('gallons'));price=Decimal(f.get('price_per_gallon'))
                if not g.is_finite() or not price.is_finite() or g<=0 or price<0:raise InvalidOperation()
                total=(g*price).quantize(Decimal(1).scaleb(-self.data['options']['currencies'][self.currency]),rounding=ROUND_HALF_UP)
                msg.set('Calculated total: '+self.amount(total))
            except (InvalidOperation,KeyError):msg.set('Calculated total: —')
        f.listeners.append(change);change('load');f.simple('expense','update' if r else 'create',r.get('id'),'Save expense')
    def mechanics_page(self):
        self.head('Mechanics','Track invoices and partial payments without counting a repair twice.',('Add mechanic',self.mechanic_form,False),('New invoice',self.invoice_form,True))
        p=self.panel(self.body,'Repair invoices',f'{self.amount(self.data["totals"]["debt"])} outstanding across all invoices')
        self.table(p,['Number','Mechanic',('Vehicle',156),'Due','Amount','Balance','Status'],[(r['id'],[r['invoice_number'],r['mechanic_name'],r['vehicle_name'],r['due_date'] or '—',self.amount(r['amount']),self.amount(r['balance']),r['status']]) for r in self.data['invoices']],actions=[('Open invoice',self.invoice_view),('Record payment',lambda id:self.payment_form(self.getrow('invoices',id)))],open_row=self.invoice_view,height=7)
        p=self.panel(self.body,'Mechanic directory')
        self.table(p,[('Name',156),'Phone','Email',('Address',200)],[(m['id'],[m['name'],m['phone'],m['email'],m['address']]) for m in self.data['mechanics']],actions=[('Edit / details',lambda id:self.mechanic_form(self.getrow('mechanics',id)))],open_row=lambda id:self.mechanic_form(self.getrow('mechanics',id)),height=5)
    def mechanic_form(self,m=None):
        m=m or {};f=Form(self,'Edit mechanic' if m else 'Add mechanic','Shop and contact information.',[spec('name','Mechanic / shop name',m.get('name')),spec('phone','Phone',m.get('phone')),spec('email','Email',m.get('email')),spec('address','Address',m.get('address'),'area'),spec('notes','Notes',m.get('notes'),'area')]);f.simple('mechanic','update' if m else 'create',m.get('id'),'Save mechanic')
    def invoice_form(self,r=None):
        if not self.data['mechanics']:
            self.note('Add a mechanic before creating a repair invoice.',True);self.mechanic_form();return
        r=r or {};f=Form(self,'Edit repair invoice' if r else 'New repair invoice','The invoice creates or updates one linked repair expense.',[spec('vehicle_id','Vehicle',r.get('vehicle_id'),'select',self.vchoices()),spec('mechanic_id','Mechanic',r.get('mechanic_id'),'select',[('','Select mechanic'),*((m['id'],m['name']) for m in self.data['mechanics'])]),spec('invoice_number','Invoice number',r.get('invoice_number')),spec('invoice_date','Invoice date',r.get('invoice_date',date_text())),spec('due_date','Due date',r.get('due_date')),spec('amount',f'Amount ({self.currency})',r.get('amount')),spec('description','Repair description',r.get('description'),'area')]);f.simple('invoice','update' if r else 'create',r.get('id'),'Save invoice')
    def invoice_view(self,id):
        r=self.getrow('invoices',id)
        if not r:return
        w=Window(self,'Invoice '+r['invoice_number'],r['mechanic_name']+' · '+r['vehicle_name'],size='760x635');p=self.panel(w.body,r['status']);self.lines(p,{'Invoice date':r['invoice_date'],'Due date':r['due_date'],'Repair amount':self.amount(r['amount']),'Payments':self.amount(r['paid']),'Outstanding balance':self.amount(r['balance']),'Repair description':r['description']})
        p=self.panel(w.body,'Payment history')
        self.table(p,['Date','Amount','Method',('Notes',220)],[(a['id'],[a['payment_date'],self.amount(a['amount']),a['method'],a['notes']]) for a in r['payments']],height=6)
        w.buttons(('Record payment' if Decimal(r['balance'])>0 else '',lambda:self.payment_form(r,w),True),('Edit invoice',lambda:self.invoice_form(r),False),('Close',w.close,False))
    def payment_form(self,r,w=None):
        if not r:return
        if Decimal(r['balance'])<=0:self.note('This repair invoice is already fully paid.');return
        f=Form(self,'Record payment',f'{r["invoice_number"]} · {self.amount(r["balance"])} remaining. No extra expense is created.',[spec('amount','Payment amount'),spec('payment_date','Payment date',date_text()),spec('method','Payment method'),spec('notes','Notes','','area')]);f.simple('invoice','pay',r['id'],'Record payment',after=(lambda _:(w.win.destroy(),self.invoice_view(r['id']))) if w else None)
    def drivers_page(self):
        self.head('Drivers','Private profiles, training and preserved driver attribution.',('Add driver',self.driver_form,True))
        p=self.panel(self.body,'Driver directory','Inactive profiles are preserved on historic trips and inspections.')
        self.table(p,[('Driver',170),'Jurisdiction','License expiry','Trips','Distance','Status'],[(r['id'],[r['name'],r['jurisdiction'],('EXPIRED · ' if r['license_expired'] else '')+(r['expiration_date'] or '—'),r['trip_count'],f'{fmt_num(r["distance"])} {self.unit}','Active' if active(r['active']) else 'Inactive']) for r in self.data['drivers']],actions=[('View history',self.driver_view),('Edit profile',lambda id:self.driver_form(self.getrow('drivers',id))),('Add dated record',self.driver_record)],open_row=self.driver_view,height=12)
    def driver_form(self,r=None):
        r=r or {};f=Form(self,'Edit driver' if r else 'Add driver','Driver information is only available to authorized users.',[spec('name','Name',r.get('name')),spec('phone','Phone',r.get('phone')),spec('email','Email',r.get('email')),spec('license_number','License number',r.get('license_number')),spec('jurisdiction','Licensing jurisdiction',r.get('jurisdiction')),spec('expiration_date','License expiration',r.get('expiration_date'),hint='YYYY-MM-DD'),spec('emergency_contact','Emergency contact',r.get('emergency_contact'),'full'),spec('notes','Notes',r.get('notes'),'area'),spec('active','Active driver',r.get('active',True),'checkbox')]);f.simple('driver','update' if r else 'create',r.get('id'),'Save profile')
    def driver_view(self,id):
        r=self.getrow('drivers',id)
        if not r:return
        w=Window(self,r['name'],'Driver history and statistics · Private workspace data',size='805x725');p=self.panel(w.body,'Driver profile')
        self.lines(p,{'Status':'Active' if active(r['active']) else 'Inactive','Phone':r['phone'],'Email':r['email'],'License':r['license_number'],'Jurisdiction':r['jurisdiction'],'Expiration':('EXPIRED · ' if r['license_expired'] else '')+(r['expiration_date'] or '—'),'Emergency contact':r['emergency_contact'],'Notes':r['notes'],'Trips':r['trip_count'],'Total distance':f'{fmt_num(r["distance"])} {self.unit}'})
        p=self.panel(w.body,'Dated records')
        self.table(p,['Date','Record type',('Title',170),('Notes',190)],[(x['id'],[x['record_date'],x['kind'],x['title'],x['notes']]) for x in r['records']],open_row=lambda xid:self.record_view(next(x for x in r['records'] if as_id(x['id'])==as_id(xid))),height=6)
        p=self.panel(w.body,'Assigned trips')
        self.table(p,['Date',('Purpose',140),('Vehicle',180),'Distance'],[(x['id'],[x['trip_date'],x['purpose'],x['vehicle_name'],f'{fmt_num(x["distance"])} {self.unit}']) for x in self.data['trips'] if as_id(x['driver_id'])==as_id(id)],height=6)
        def jump():w.win.destroy();self.filters['trips']={'driver_id':as_id(id)};self.navigate('trips')
        w.buttons(('Add record',lambda:self.driver_record(id,w),True),('Open filtered trips',jump,False),('Deactivate' if active(r['active']) else 'Reactivate',lambda:self.confirm_write('Change driver activity?','Historical trip and inspection attribution will remain.','driver','archive' if active(r['active']) else 'activate',{'id':int(id)},lambda _:(w.win.destroy(),self.driver_view(id))),False))
    def record_view(self,r):
        w=Window(self,r['title'],r['record_date']+' · '+r['kind']);p=self.panel(w.body,'Record notes');self.lines(p,{'Notes':r['notes']})
    def driver_record(self,id,w=None):
        d=self.getrow('drivers',id);f=Form(self,'Add driver record',d['name'],[spec('record_date','Record date',date_text()),spec('kind','Record type','Training','select',self.data['options']['record_kinds']),spec('title','Record title','','full'),spec('notes','Notes','','area')]);f.simple('driver','record',int(id),'Save record',after=(lambda _:(w.win.destroy(),self.driver_view(id))) if w else None)

    def display_image(self,parent,blob):
        p=self.panel(parent,'Preview')
        try:
            if Image is not None:
                image=Image.open(io.BytesIO(blob));image.thumbnail((515,320));pic=ImageTk.PhotoImage(image)
            else:pic=tk.PhotoImage(data=base64.b64encode(blob).decode('ascii'))
            lab=ttk.Label(p,image=pic);lab.image=pic;lab.pack(anchor='center')
        except Exception:
            ttk.Label(p,text='Preview unavailable. Install Pillow for JPEG and PNG previews: py -m pip install Pillow',style='PanelMuted.TLabel',wraplength=550).pack(anchor='w')
        return p
    def photo_view(self,id,caption=''):
        w=Window(self,'Private photo',caption,size='670x580');client=self.client;w.message('Loading photo…')
        def good(blob):w.message('');self.display_image(w.body,blob)
        w.run(lambda:client.request('image',{'id':id},raw=True),good)
    def photo_upload(self,kind,id,item='',after=None):
        fields=[spec('caption','Photo caption','','full')]
        if kind=='vehicle':fields += [spec('view_label','Labeled view','Front','select',self.data['options']['views']),spec('cover','Set as vehicle cover',False,'checkbox')]
        f=Form(self,'Upload protected photo','Choose a JPEG or PNG up to 5 MB. The server validates image bytes.',fields)
        path=[None];image_panel=[None]
        def choose():
            picked=filedialog.askopenfilename(parent=f.win,filetypes=[('JPEG and PNG images','*.jpg *.jpeg *.png')])
            if not picked:return
            try:
                choice=Path(picked)
                if choice.stat().st_size>5_242_880:raise ApiError('The file is larger than 5 MB.')
            except (OSError,ApiError) as e:f.message(e,True);return
            path[0]=choice;f.message(choice.name)
            def show(blob):
                if f.alive() and path[0]==choice:
                    if image_panel[0]:image_panel[0].destroy()
                    image_panel[0]=self.display_image(f.body,blob)
            self.jobs(choice.read_bytes,show,lambda e:f.message(e,True))
        ttk.Button(f.body,text='Choose JPEG or PNG…',command=choose).pack(anchor='w',pady=(0,7))
        def upload():
            if f.pending:return
            if f.origin_url!=self.client.url:f.message('Reconnect to the original workspace before uploading.',True);return
            choice=path[0]
            if not choice:f.message('Choose an image first.',True);return
            d=f.values()|{'owner_kind':kind,'owner_id':int(id),'item_key':item};client=self.client
            try: st=choice.stat()
            except OSError as e:f.message(e,True);return
            key=f.retry.key('photo','upload',d|{'local_filepath':str(choice),'mtime':st.st_mtime_ns,'size':st.st_size});f.message('Uploading…')
            def send():
                with choice.open('rb') as fp:blob=fp.read(5_242_881)
                if len(blob)>5_242_880:raise ApiError('The file is larger than 5 MB.')
                return client.save('photo','upload',d|{'image':base64.b64encode(blob).decode('ascii')},key)
            def good(r):
                f.win.destroy();self.note(r.get('message','Photo uploaded.'));self.refresh(after=(lambda:after(r)) if after else None)
            f.run(send,good)
        f.buttons(('Upload photo',upload,True),('Cancel',f.close,False))

    def documents_page(self,doc_type):
        ins=doc_type=='inspection';key='inspections' if ins else 'sales';f=self.filters.get(doc_type,{})
        title='Weekly inspections' if ins else 'Bills of sale'
        self.head(title,'Resume synchronized drafts and review recorded issues.' if ins else 'Prepare a sale agreement and preserve finalized snapshots.',('Blank PDF',lambda:self.document_pdf(doc_type),False),('New inspection' if ins else 'New agreement',lambda:DocEditor(self,doc_type),True))
        if ins:
            p=self.panel(self.body,'Current week · '+self.data['week_start'],'A completed inspection records observations, not a safety clearance.')
            self.table(p,[('Vehicle',173),('Status',212),'Unresolved','Missing','Next due'],[(v['id'],[v['name'],v['inspection']['status'],v['inspection']['unresolved'],'Overdue / missing' if v['inspection']['overdue'] else '—',v['inspection']['due_next']]) for v in self.data['vehicles']],height=4,open_row=self.start_for_vehicle,actions=[('Open or start weekly sheet',self.start_for_vehicle)])
        else:
            b=self.panel(self.body,'General vehicle bill of sale','Confirm the applicable jurisdiction’s requirements. This form does not substitute for official title-transfer documents.')
        word=f.get('query','').casefold();week=f.get('week','')
        if week:
            dt=date.fromisoformat(week);week=(dt-timedelta(days=dt.weekday())).isoformat()
        result=[]
        for r in self.data[key]:
            if f.get('vehicle_id') and as_id(r['vehicle_id'])!=f['vehicle_id']:continue
            if f.get('driver_id') and as_id(r.get('driver_id'))!=f['driver_id']:continue
            if f.get('status') and r['status']!=f['status']:continue
            if f.get('unresolved') and (r.get('unresolved',0)>0)!=(f['unresolved']=='open'):continue
            if f.get('result') and (r['status']!='submitted' or (r.get('issue_count',0)>0)!=(f['result']=='findings')):continue
            if week and r['week_start']!=week:continue
            if word and word not in ' '.join(compact(x) for x in (r['reference'],r['vehicle_name'],r.get('driver_name'),r['data'].get('buyer_name'),r['data'].get('seller_name'))).casefold():continue
            result.append(r)
        p=self.panel(self.body,'Inspection history' if ins else 'Agreement history',f'{len(result)} matching records')
        if ins:
            cols=[('Reference',160),('Vehicle',155),'Inspector','Week',('Result',178),'Unresolved']
            rows=[(r['id'],[r['reference'],r['vehicle_name'],r['driver_name'],r['week_start'],('Draft' if r['status']=='draft' else 'Completed · findings' if r['issue_count'] else 'Completed · no findings'),r.get('unresolved',0)]) for r in result]
        else:
            cols=[('Reference',158),('Vehicle',145),'Buyer','Status','Price','Balance']
            rows=[(r['id'],[r['reference'],r['vehicle_name'],r['data'].get('buyer_name'),FLAGS[r['status']],self.amount(r['data'].get('price'),r['data'].get('currency')),self.amount(r['data'].get('balance'),r['data'].get('currency'))]) for r in result]
        self.table(p,cols,rows,actions=[('Open / resume draft',self.open_document)],open_row=self.open_document,height=9)
        def filtering():
            fields=[spec('query','Search by reference or name',f.get('query')),spec('vehicle_id','Vehicle',f.get('vehicle_id'),'select',self.vchoices('All vehicles'))]
            if ins:fields += [spec('driver_id','Inspector',f.get('driver_id'),'select',self.dchoices(all=True,first='All drivers')),spec('week','Week of',f.get('week'),hint='YYYY-MM-DD'),spec('status','Sheet status',f.get('status'),'select',[('','Any sheet status'),('draft','Draft'),('submitted','Completed')]),spec('result','Result',f.get('result'),'select',[('','All results'),('findings','Has findings'),('none','No findings')]),spec('unresolved','Follow-up',f.get('unresolved'),'select',[('','All findings'),('open','Unresolved'),('closed','None unresolved')])]
            else:fields += [spec('status','Status',f.get('status'),'select',[('','All statuses'),('draft','Draft'),('finalized','Finalized'),('signed','Signed')])]
            self.filter_form(doc_type,fields)
        ttk.Button(p,text='Search and filter history',command=filtering).pack(anchor='w',pady=(7,0))
    def start_for_vehicle(self,id):
        v=self.getrow('vehicles',id)
        if v and v['inspection']['id']:self.open_document(v['inspection']['id'])
        else:DocEditor(self,'inspection',vehicle_id=id)
    def open_document(self,id):
        client=self.client;epoch=self.epoch;self.note('Loading document…')
        def ready(response):
            if epoch!=self.epoch:return
            self.note('Document loaded.');d=response['document']
            if d['status']=='draft':DocEditor(self,d['doc_type'],d)
            else:self.document_detail(d)
        self.jobs(lambda:client.request('document',{'id':id}),ready,lambda e:self.note(e,True))
    def document_pdf(self,type,id=None,ref=None,open_file=False,parent=None):
        self.binary('pdf',{'type':type,'id':id},(ref or ('blank-weekly-inspection' if type=='inspection' else 'blank-bill-of-sale'))+'.pdf',open_file=bool(open_file),parent=parent)
    def document_detail(self,r):
        ins=r['doc_type']=='inspection';d=r['data'];w=Window(self,'Weekly inspection' if ins else 'Vehicle bill of sale',r['reference']+' · '+FLAGS[r['status']],size='865x740')
        p=self.panel(w.body,'Inspection details' if ins else 'Agreement snapshot','Completed records are locked. Corrections create separate identified drafts.')
        if ins:
            self.lines(p,{'Vehicle':r['vehicle_name'],'Inspector':r['driver_name'],'Week beginning Monday':r['week_start'],'Next week due':r['due_next'],'Date and time':f'{r["document_day"]} {d["inspection_time"]}','Odometer':f'{d["odometer"]} {d["unit"]}','Findings':r['issue_count'],'Unresolved':r['unresolved'],'Comments':d['comments'],'Acknowledgment':f'{d["ack_name"]} · {d["ack_at"]}'})
            p=self.panel(w.body,'Checklist results')
            labels=self.data['options']['checklist'];result_labels=self.data['options']['results'];items=d['items']
            def item_review(key):
                win=Window(self,labels[key][1],result_labels.get(items[key].get('result'),'Incomplete'));pan=self.panel(win.body,'Item notes');self.lines(pan,{'Notes':items[key].get('notes')})
            self.table(p,[('Item',260),'Result',('Notes',320)],[(key,[labels[key][1],result_labels.get(val.get('result'),'Incomplete'),val.get('notes')]) for key,val in items.items()],height=8,open_row=item_review)
            p=self.panel(w.body,'Findings and follow-up','An issue’s original result and evidence are never removed by resolution.')
            issues=r['issues']
            for i in issues:
                line=ttk.Frame(p,style='Panel.TFrame');line.pack(fill='x',pady=4)
                ttk.Button(line,text='Update follow-up',command=lambda issue=i:self.issue_form(issue,r,w)).pack(side='right')
                ttk.Label(line,text=f'{labels[i["item_key"]][1]} · {self.data["options"]["issue_states"][i["status"]]}',style='Field.TLabel',wraplength=550).pack(anchor='w');self.lines(line,{'Action':i['action_taken'],'Responsible':i['responsible'],'Resolution date':i['resolved_date'],'Notes':i['notes']})
            if not issues:ttk.Label(p,text='No findings were recorded.',style='PanelMuted.TLabel').pack(anchor='w')
            p=self.panel(w.body,'Evidence photos')
            def photo(pid):self.photo_view(pid,next((a['caption'] for a in r['photos'] if as_id(a['id'])==as_id(pid)),''))
            self.table(p,[('Item',222),('Caption',300)],[(x['id'],[labels.get(x['item_key'],('', 'General inspection'))[1],x['caption']]) for x in r['photos']],height=5,actions=[('View photo',photo)],open_row=photo)
        else:
            self.lines(p,{'Vehicle':r['vehicle_name'],'VIN':d.get('vin'),'Odometer':f'{d.get("odometer")} {d.get("unit")}','Disclosure':d.get('disclosure'),'Buyer':d.get('buyer_name'),'Buyer address':d.get('buyer_address'),'Seller':d.get('seller_name'),'Seller address':d.get('seller_address'),'Sale date and location':f'{d.get("sale_date")} · {d.get("sale_location")}','Template':f'{d.get("template_name")} · {d.get("template_jurisdiction")}'})
            p=self.panel(w.body,'Financial terms and disclosures')
            self.lines(p,{'Price':self.amount(d.get('price'),d.get('currency')),'Deposit':self.amount(d.get('deposit'),d.get('currency')),'Remaining balance':self.amount(d.get('balance'),d.get('currency')),'Payment status':d.get('payment_status'),'Payment method':d.get('payment_method'),'Agreed terms':d.get('payment_terms'),'Title / lien':d.get('title_status'),'Title details':d.get('title_notes'),'Warranty':d.get('warranty'),'Warranty details':d.get('warranty_notes'),'Condition':d.get('condition'),'Defects':d.get('defects'),'Accessories':d.get('accessories'),'Additional terms':d.get('additional_terms')})
            if d.get('signed'):
                p=self.panel(w.body,'Handwritten signing log','This status records a manual action; no electronic signature was inserted.');self.lines(p,{'Buyer':d['signed']['buyer_printed'],'Date (buyer)':d['signed']['buyer_sign_date'],'Seller':d['signed']['seller_printed'],'Date (seller)':d['signed']['seller_sign_date'],'Recorded UTC':d['signed']['logged_at']})
        if r.get('parent_id') or r['revisions']:
            p=self.panel(w.body,'Originals and revisions')
            if r.get('parent_id'):ttk.Button(p,text='Original: '+r.get('parent_reference',''),command=lambda:self.open_document(r['parent_id'])).pack(anchor='w',pady=2)
            for rev in r['revisions']:ttk.Button(p,text=rev['reference']+' · '+FLAGS[rev['status']],command=lambda chosen=rev:self.open_document(chosen['id'])).pack(anchor='w',pady=2)
        p=self.panel(w.body,'Audit history')
        def audit_view(id):
            record=next((x for x in r['audit'] if as_id(x['id'])==as_id(id)),None)
            if not record:return
            view=Window(self,'Audit details',record['logged_at']);out=self.panel(view.body,'Recorded snapshot')
            to=tk.Text(out,wrap='word',height=18,background=self.colors['bg'],foreground=self.colors['ink'],relief='flat');to.insert('1.0',json.dumps(record['snapshot'],indent=2,ensure_ascii=False));to.configure(state='disabled');to.pack(fill='both',expand=True)
        self.table(p,[('Action',185),'User',('Timestamp (UTC)',208)],[(x['id'],[x['action'].replace('_',' '),x['actor'],x['logged_at']]) for x in r['audit']],height=5,open_row=audit_view)
        def revise():self.confirm_write('Create correction?', 'The existing record and its history are kept. A new correction draft will be opened.',r['doc_type'],'revise',{'id':r['id']},lambda result:(w.win.destroy(),self.open_document(result['id'])))
        w.buttons(('Export PDF',lambda:self.document_pdf(r['doc_type'],r['id'],r['reference'],parent=w.win),True),('Print preview',lambda:self.document_pdf(r['doc_type'],r['id'],r['reference'],True,w.win),False),('Create correction' if not r['revisions'] else '',revise,False),('Record signing' if r['status']=='finalized' else '',lambda:self.signing_form(r,w),False))
    def issue_form(self,i,r,view):
        f=Form(self,'Update finding follow-up',self.data['options']['checklist'][i['item_key']][1],[spec('status','Follow-up status',i['status'],'select',list(self.data['options']['issue_states'].items())),spec('responsible','Responsible person',i['responsible']),spec('resolved_date','Resolution date',i['resolved_date'],hint='YYYY-MM-DD; required when resolved'),spec('action_taken','Action taken',i['action_taken'],'area'),spec('notes','Notes',i['notes'],'area')]);f.simple('issue','update',i['id'],'Save follow-up',after=lambda _:(view.win.destroy(),self.open_document(r['id'])))
    def signing_form(self,r,view):
        d=r['data'];f=Form(self,'Record handwritten signatures','Confirm only after both parties have signed the printed agreement. This does not create a digital signature.',[spec('seller_printed','Seller printed name',d.get('seller_printed')),spec('buyer_printed','Buyer printed name',d.get('buyer_printed')),spec('seller_sign_date','Seller actually signed on',d.get('seller_sign_date')),spec('buyer_sign_date','Buyer actually signed on',d.get('buyer_sign_date')),spec('confirmed','I confirm that the parties signed the printed agreement.',False,'checkbox')]);f.simple('sale','signed',r['id'],'Record signed status',extra=lambda v:v|{'revision':r['revision']},after=lambda _:(view.win.destroy(),self.open_document(r['id'])))
    def settings_page(self):
        s=self.data['settings'];self.head('Settings','Manage connection details, update preferences, and bill-of-sale templates.',('Check for updates',lambda:self.check_updates(True),True))
        p=self.panel(self.body,'Connection and version');self.lines(p,{'Website URL':self.client.url,'User':self.data['actor']['display_name'],'Desktop version':VERSION,'Website version':self.data['version'],'GitHub repository':s['github_repo'] or 'Not configured','Currency and unit':f'{s["currency"]} · {s["unit"]}'})
        ttk.Button(p,text='Edit connection settings',command=self.connection).pack(anchor='w',pady=4)
        ttk.Button(p,text='Set site name or GitHub repository',command=self.repo_form).pack(anchor='w',pady=4)
        p=self.panel(self.body,'Jurisdiction templates','Built-in general template and your editable jurisdiction-specific forms.')
        self.table(p,[('Template',220),'Jurisdiction','Witness','Notary'],[(t['id'],[t['name'],t['jurisdiction'],'Included' if active(t['witness']) else '—','Included' if active(t['notary']) else '—']) for t in self.data['templates']],actions=[('Edit selected template',lambda id:self.template_form(next(t for t in self.data['templates'] if as_id(t['id'])==id)))])
        ttk.Button(p,text='Add jurisdiction template',command=self.template_form).pack(anchor='w',pady=(7,0))
    def repo_form(self):
        s=self.data['settings'];f=Form(self,'Workspace and release settings','Leave the repository empty to disable online release checks.',[spec('site_name','Site name',s['site_name'],'full'),spec('github_repo','Public GitHub owner/repository',s['github_repo'],'full',hint='Enter owner/repository, not a URL.')]);f.simple('settings','update',label='Save settings')
    def template_form(self,t=None):
        if t and not t['id']:self.note('The built-in general template is not editable. Create a jurisdiction template instead.',True);return
        t=t or {};f=Form(self,'Edit template' if t else 'New jurisdiction template','Suggested terms are editable on every new draft.',[spec('name','Template name',t.get('name')),spec('jurisdiction','Jurisdiction name',t.get('jurisdiction')),spec('terms','Suggested terms',t.get('terms'),'area'),spec('witness','Include witness fields',t.get('witness'),'checkbox'),spec('notary','Include notary fields',t.get('notary'),'checkbox')]);f.simple('template','update' if t else 'create',t.get('id'))
    def check_updates(self,manual):
        if not self.client:return
        client=self.client;epoch=self.epoch
        if manual:self.note('Checking for published GitHub releases…')
        def done(info):
            if epoch!=self.epoch:return
            latest=info.get('latest') or {};new=semver(latest.get('version'));current=semver(VERSION)
            if not new or not current or new<=current:
                if manual:self.note('You’re on the latest available stable desktop version.' if new else info['message'],info['state']=='offline')
                return
            key=f'{client.url}|{info.get("repo", "").casefold()}';dismissed=self.pref.setdefault('dismissed',{})
            if not manual and dismissed.get(key)==latest['tag']:return
            prompt=Window(self,'A newer version is available',f'Installed {VERSION} · Latest {latest["version"]}',size='560x430')
            box=self.panel(prompt.body,'Would you like to download the latest version?',f'{latest["download_label"]}. Motorlog will not install or run anything automatically.')
            def later():dismissed[key]=latest['tag'];prefs_save(self.pref);prompt.win.destroy()
            def open_link(target):
                part=urlsplit(target)
                if part.scheme=='https' and part.hostname=='github.com':webbrowser.open(target)
            def accept():open_link(latest['download']);later()
            ttk.Button(box,text='Release notes',command=lambda:open_link(latest['notes'])).pack(anchor='w',pady=5)
            prompt.buttons(('Download',accept,True),('Later',later,False));prompt.win.protocol('WM_DELETE_WINDOW',later);prompt.win.bind('<Escape>',lambda _:later())
        self.jobs(lambda:client.request('updates',{'manual':1} if manual else {}),done,lambda e:self.note(e,True) if manual else None)


class DocEditor(Form):
    def __init__(self,app,kind,document=None,vehicle_id=''):
        doc=document or {};ins=kind=='inspection';self.kind=kind;self.options=app.data['options'];self.ins=ins
        self.id=doc.get('id');self.revision=doc.get('revision');self.reference=doc.get('reference');self.photos=doc.get('photos',[])
        self.items={};self.items_ui={};self.evidence_ui={};self.generation=0;self.dirty=False;self.timer=None;self.queued=[];self.finishing=False;self.online_error='';self.session_url=app.client.url
        data=(doc.get('data',{})|{'vehicle_id':doc.get('vehicle_id'),'driver_id':doc.get('driver_id')}) if doc else app.snapshot(app.getrow('vehicles',vehicle_id))
        self.data_start=data
        super().__init__(app,('Resume draft' if doc else 'New')+' · '+('Weekly inspection' if ins else 'Bill of sale'),self.reference or 'Drafts are saved to the website and can be reopened in either interface.',size='870x800')
        if ins:self.inspection_form(data)
        else:self.sale_form(data)
        self.listeners.append(self.changed)
        self.buttons(('Submit inspection' if ins else 'Finalize agreement',self.finalize,True),('Preview',self.preview,False),('Save draft',lambda:self.persist('draft',True),False))
        self.win.bind('<Control-s>',lambda _:self.persist('draft',True));self.update_totals()
        self.message('Draft saved · '+self.reference if self.reference else 'Not saved yet · change a field or choose Save draft')
    def vehicle_fields(self,d):
        return [spec('model_year','Year',d.get('model_year')),spec('make','Make',d.get('make')),spec('model','Model',d.get('model')),spec('vin','VIN / chassis number',d.get('vin')),spec('plate','Plate',d.get('plate')),spec('color','Color',d.get('color')),spec('odometer','Odometer',d.get('odometer')),spec('unit','Odometer unit',d.get('unit',self.app.unit),'select',[('mi','Miles (mi)'),('km','Kilometers (km)')])]
    def inspection_form(self,d):
        self.group('Inspection identity',[spec('vehicle_id','Vehicle',d.get('vehicle_id'),'select',self.app.vchoices()),spec('driver_id','Driver / inspector',d.get('driver_id'),'select',self.app.dchoices(d.get('driver_id'),first='Manual inspector / choose a driver')),spec('inspector_name','Inspector name',d.get('inspector_name')),spec('inspection_date','Inspection date',d.get('inspection_date') or date_text(),hint='YYYY-MM-DD'),spec('inspection_time','Inspection time',d.get('inspection_time') or moment_text(),hint='HH:MM, 24-hour time'),spec('week','Week beginning Monday','','readonly')])
        self.group('Vehicle identification · review all prefilled values',self.vehicle_fields(d))
        p=self.app.panel(self.body,'Checklist progress','No result is selected on a new sheet. Add notes for Needs attention or Fail.')
        self.progress_var=tk.StringVar();ttk.Label(p,textvariable=self.progress_var,style='Field.TLabel').pack(anchor='w',pady=(0,4));self.progress=ttk.Progressbar(p,maximum=len(self.options['checklist']),mode='determinate');self.progress.pack(fill='x')
        last=None;group=None
        for key,(category,label) in self.options['checklist'].items():
            if category!=last:group=self.app.panel(self.body,category);last=category
            line=ttk.Frame(group,style='Panel.TFrame',padding=(0,6));line.pack(fill='x')
            title=tk.StringVar(value=label);ttk.Label(line,textvariable=title,style='Field.TLabel').pack(anchor='w')
            choice=tk.StringVar(value=d.get('items',{}).get(key,{}).get('result','')); row=ttk.Frame(line,style='Panel.TFrame');row.pack(fill='x',pady=(2,5))
            for value,result in self.options['results'].items():ttk.Radiobutton(row,text=result,variable=choice,value=value,style='Panel.TRadiobutton').pack(side='left',padx=(0,6))
            ttk.Label(line,text='Notes',style='PanelMuted.TLabel').pack(anchor='w')
            notes=tk.Text(line,height=2,width=39,wrap='word',font=('Segoe UI',10),relief='flat',borderwidth=1,highlightthickness=1,highlightbackground=self.app.colors['line'],bg=self.app.colors['bg'],fg=self.app.colors['ink'],insertbackground=self.app.colors['ink']);notes.insert('1.0',d.get('items',{}).get(key,{}).get('notes',''));notes.pack(fill='x');notes.edit_modified(False)
            notes.bind('<<Modified>>',lambda evt,k=key:self._text_updated('check_'+k,evt.widget));choice.trace_add('write',lambda *_a,k=key:self.changed('check_'+k))
            self.items[key]=(choice,notes);self.items_ui[key]=(title,label)
            self.evidence_buttons(line,key)
            ttk.Separator(group).pack(fill='x',pady=(3,0))
        foot=self.group('Comments and acknowledgment',[spec('comments','Overall comments',d.get('comments'),'area'),spec('ack_name','Acknowledgment name',d.get('ack_name')),spec('acknowledged','I acknowledge these inspection results and comments.',d.get('acknowledged'),'checkbox')])
        self.evidence_buttons(foot,'',grid=True);self.evidence_refresh()
    def evidence_buttons(self,parent,key,grid=False):
        row=ttk.Frame(parent,style='Panel.TFrame');row.grid(row=20,column=0,columnspan=2,sticky='w',pady=(3,1)) if grid else row.pack(fill='x',pady=(4,0));name='Attach finding photo' if key else 'Attach general photo'
        ttk.Button(row,text=name,command=lambda:self.attach(key)).pack(side='left',padx=(0,5));display=ttk.Frame(row,style='Panel.TFrame');display.pack(side='left',fill='x');self.evidence_ui[key]=display
    def evidence_refresh(self):
        for k,target in self.evidence_ui.items():
            for x in target.winfo_children():x.destroy()
            photos=[p for p in self.photos if p['item_key']==k]
            if photos:ttk.Button(target,text=f'View photos ({len(photos)})',command=lambda all=photos:self.evidence_gallery(all)).pack(side='left')
    def evidence_gallery(self,photos):
        w=Window(self.app,'Draft evidence','Private JPEG and PNG files');p=self.app.panel(w.body,'Photo attachments')
        self.app.table(p,['Item',('Caption',260)],[(r['id'],[self.options['checklist'].get(r['item_key'],['','General'])[1],r['caption']]) for r in photos],height=7,open_row=lambda id:self.app.photo_view(id,next(x['caption'] for x in photos if as_id(x['id'])==as_id(id))))
    def attach(self,key):
        if self.finishing:return
        def saved(_):
            if not self.alive():return
            def uploaded(_r):
                client=self.app.client;id=self.id
                def good(result):
                    if self.alive():self.photos=result['document']['photos'];self.evidence_refresh()
                self.app.jobs(lambda:client.request('document',{'id':id}),good,lambda e:self.message(e,True))
            self.app.photo_upload('inspection',self.id,key,uploaded)
        self.persist('draft',False,saved)
    def sale_form(self,d):
        tpls=self.app.data['templates'];self.group('Agreement setup',[spec('template_id','Agreement template',d.get('template_id',0),'select',[(t['id'],t['name']+' · '+t['jurisdiction']) for t in tpls]),spec('vehicle_id','Linked vehicle',d.get('vehicle_id'),'select',self.app.vchoices('Manual vehicle / no link')),spec('sale_date','Sale date',d.get('sale_date'),hint='YYYY-MM-DD'),spec('sale_time','Sale time',d.get('sale_time'),hint='HH:MM'),spec('sale_location','Sale location',d.get('sale_location')),spec('delivery_date','Delivery / possession date',d.get('delivery_date'),hint='YYYY-MM-DD')]);ttk.Button(self.body,text='Apply suggested template terms',command=self.template_terms).pack(anchor='w',pady=(1,8))
        self.group('Buyer and seller',[spec('seller_name','Seller legal name',d.get('seller_name')),spec('buyer_name','Buyer legal name',d.get('buyer_name')),spec('seller_contact','Seller contact (optional)',d.get('seller_contact')),spec('buyer_contact','Buyer contact (optional)',d.get('buyer_contact')),spec('seller_address','Seller mailing address',d.get('seller_address'),'area'),spec('buyer_address','Buyer mailing address',d.get('buyer_address'),'area')])
        self.group('Vehicle snapshot and mileage disclosure',self.vehicle_fields(d)+[spec('disclosure','Mileage disclosure',d.get('disclosure'),'select',[('','Choose disclosure'),*self.options['disclosures']])])
        p=self.group('Sale price and payment',[spec('currency','Currency',d.get('currency',self.app.currency),'select',list(self.options['currencies'])),spec('price','Sale price',d.get('price')),spec('deposit','Deposit',d.get('deposit')),spec('payment_status','Payment status',d.get('payment_status'),'select',[('','Choose payment status'),*self.options['payment_states']]),spec('payment_method','Payment method',d.get('payment_method')),spec('balance_due','Balance due date',d.get('balance_due')),spec('payment_terms','Agreed payment terms',d.get('payment_terms'),'area')]);self.balance_var=tk.StringVar();ttk.Label(p,textvariable=self.balance_var,style='Field.TLabel').grid(row=30,column=0,columnspan=2,sticky='w',pady=(2,0))
        self.group('Condition and disclosures',[spec('condition','Vehicle condition',d.get('condition'),'area'),spec('defects','Known defects',d.get('defects'),'area'),spec('accessories','Included accessories',d.get('accessories'),'area'),spec('title_status','Title / lien disclosure',d.get('title_status'),'select',[('','Choose disclosure'),*self.options['titles']]),spec('warranty','Warranty / as-is choice',d.get('warranty'),'select',[('','Choose warranty terms'),*self.options['warranties']]),spec('title_notes','Title or lien details',d.get('title_notes'),'area'),spec('warranty_notes','Warranty details',d.get('warranty_notes'),'area'),spec('additional_terms','Additional terms',d.get('additional_terms'),'area'),spec('terms_reviewed','I reviewed the agreement, disclosures, and selected terms.',d.get('terms_reviewed'),'checkbox')])
        self.group('Handwritten signature information',[spec('seller_printed','Seller printed name',d.get('seller_printed')),spec('buyer_printed','Buyer printed name',d.get('buyer_printed')),spec('seller_sign_date','Seller signing date',d.get('seller_sign_date')),spec('buyer_sign_date','Buyer signing date',d.get('buyer_sign_date')),spec('witness_name','Witness printed name',d.get('witness_name')),spec('notary_details','Notary information',d.get('notary_details'),'area')]);self.template_options(initial=True)
    def current_template(self):return next((t for t in self.app.data['templates'] if as_id(t['id'])==as_id(self.get('template_id'))),self.app.data['templates'][0])
    def template_options(self,initial=False):
        t=self.current_template();self.show('witness_name',active(self.data_start.get('template_witness',t['witness'])) if initial else active(t['witness']));self.show('notary_details',active(self.data_start.get('template_notary',t['notary'])) if initial else active(t['notary']))
    def template_terms(self):
        t=self.current_template();old=self.get('additional_terms')
        if t['terms']:self.set('additional_terms',old+'\n\n'+t['terms'] if old else t['terms']);self.set('terms_reviewed',False)
        else:self.message('This template has no suggested text. You can enter your own terms.')
    def changed(self,key):
        if key=='week':return
        if key=='vehicle_id':
            v=self.app.getrow('vehicles',self.get(key))
            if v:
                for field,value in self.app.snapshot(v).items():
                    if field!='vehicle_id':self.set(field,value)
        if self.ins and key=='driver_id':
            d=self.app.getrow('drivers',self.get(key))
            if d:self.set('inspector_name',d['name'])
        if not self.ins and key=='template_id':self.template_options()
        self.dirty=True;self.generation+=1;self.online_error='';self.update_totals();self.message('Unsaved changes…')
        if self.timer:self.win.after_cancel(self.timer);self.timer=None
        if not self.finishing:self.timer=self.win.after(1350,lambda:self.persist('draft'))
    def update_totals(self):
        if self.ins:
            filled=0;issues=0
            for key,(choice,_) in self.items.items():
                value=choice.get();filled+=bool(value);issues+=value in ('attention','fail');text,label=self.items_ui[key];text.set(label+' · '+self.options['results'].get(value,'Incomplete')+(' · notes required' if value in ('attention','fail') else ''))
            self.progress_var.set(f'{filled} of {len(self.items)} complete · {issues} {"finding" if issues==1 else "findings"}');self.progress.configure(value=filled)
            try:
                day=date.fromisoformat(self.get('inspection_date'));week=(day-timedelta(days=day.weekday())).isoformat()
            except ValueError:week=''
            if week!=self.get('week'):self.set('week',week)
        else:self.balance_var.set('Remaining balance: '+fmt_money(self.balance(),self.get('currency')))
    def balance(self):
        try:
            price,deposit=Decimal(self.get('price')),Decimal(self.get('deposit') or '0');amount=price-deposit
            return amount if price.is_finite() and deposit.is_finite() and amount>=0 and deposit>=0 else None
        except InvalidOperation:return None
    def data(self):
        d=self.values();d.pop('week',None)
        if self.id:d.update(id=self.id,revision=self.revision)
        if self.ins:d['items']={key:{'result':var.get(),'notes':txt.get('1.0','end-1c')} for key,(var,txt) in self.items.items()}
        return d
    def persist(self,action='draft',manual=False,continuation=None):
        if not self.alive():return
        if self.timer:self.win.after_cancel(self.timer);self.timer=None
        if self.pending:
            if manual or continuation or action!='draft':self.queued.append((action,manual,continuation))
            return
        if self.app.client.url!=self.session_url:
            self.message('Reconnect to the original workspace before saving this open draft.',True);self.finishing=False;return
        if action=='draft' and not manual and self.id and not self.dirty:
            if continuation:continuation({'id':self.id,'revision':self.revision})
            return
        d=self.data();generation=self.generation;key=self.retry.key(self.kind,action,d);client=self.app.client
        self.message('Saving draft…' if action=='draft' else 'Validating and locking the completed document…')
        def success(r):
            self.retry.success(self.kind,action);self.id=r['id'];self.reference=r.get('reference',self.reference);self.revision=r['revision'];self.dirty=self.generation!=generation
            if action!='draft':
                self.win.destroy();self.app.note(r['message']);self.app.refresh(after=lambda:self.app.open_document(r['id']));return
            self.message('Draft saved · '+self.reference);self.app.refresh()
            if continuation:continuation(r)
            if self.alive() and self.queued:
                next_action=self.queued.pop(0);self.persist(*next_action)
            elif self.dirty and not self.finishing and self.alive():self.timer=self.win.after(1250,lambda:self.persist('draft'))
        def fail(e):
            self.dirty=True;self.finishing=False;self.queued=[]
            self.message(('Offline · ' if getattr(e,'code','')=='offline' else 'Not submitted · ')+str(e),True)
        self.run(lambda:client.save(self.kind,action,d,key),success,fail)
    def finalize(self):
        if self.finishing:return
        self.finishing=True
        # Saving the draft first ensures failed final validation never loses a resume point.
        self.persist('draft',False,lambda _:self.persist('submit' if self.ins else 'finalize',True))
    def preview(self):
        d=self.data();w=Window(self.app,'Live document preview','Current unsaved values are included. Use Save draft before exporting the current version.',size='810x690')
        def line(group,fields):p=self.app.panel(w.body,group);self.app.lines(p,fields)
        line('Vehicle',{'Year / make / model':f'{d["model_year"]} {d["make"]} {d["model"]}','VIN':d['vin'],'Plate':d['plate'],'Color':d['color'],'Odometer':f'{d["odometer"]} {d["unit"]}'})
        if self.ins:
            line('Inspection',{'Inspector':d['inspector_name'],'Date / time':d['inspection_date']+' '+d['inspection_time'],'Week of':self.get('week')})
            line('Checklist',{self.options['checklist'][k][1]:self.options['results'].get(x['result'],'Incomplete')+(f' · {x["notes"]}' if x['notes'] else '') for k,x in d['items'].items()})
            line('Comments and signature',{'Comments':d['comments'],'Acknowledgment':d['ack_name'] if d['acknowledged'] else 'Not acknowledged','Inspector signature':'________________________________','Date':'____________________'})
        else:
            line('Seller and buyer',{'Seller':d['seller_name'],'Seller address':d['seller_address'],'Buyer':d['buyer_name'],'Buyer address':d['buyer_address']})
            line('Transaction',{'Sale date / time':d['sale_date']+' '+d['sale_time'],'Location':d['sale_location'],'Price':fmt_money(d['price'],d['currency']),'Deposit':fmt_money(d['deposit'],d['currency']),'Balance':fmt_money(self.balance(),d['currency']),'Disclosure':d['disclosure'],'Payment status':d['payment_status'],'Payment method':d['payment_method'],'Payment terms':d['payment_terms']})
            line('Terms and signatures',{'Title / lien':d['title_status'],'Lien details':d['title_notes'],'Warranty':d['warranty'],'Warranty terms':d['warranty_notes'],'Condition':d['condition'],'Defects':d['defects'],'Accessories':d['accessories'],'Additional terms':d['additional_terms'],'Seller signature':'________________________________','Buyer signature':'________________________________'})
        def output(open_file):self.persist('draft',False,lambda _:self.app.document_pdf(self.kind,self.id,self.reference,open_file,w.win))
        w.buttons(('Export PDF',lambda:output(False),True),('Print preview',lambda:output(True),False),('Back to form',w.close,False))
    def close(self):
        if (self.dirty or self.pending) and not messagebox.askyesno('Close draft?', 'Changes not yet saved to the website will be lost.',parent=self.win):return
        if self.timer:self.win.after_cancel(self.timer)
        self.win.destroy()


def main():
    root=tk.Tk();Application(root);root.mainloop()


if __name__=='__main__':
    main()
