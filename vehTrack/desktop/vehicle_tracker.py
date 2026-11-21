#!/usr/bin/env python3
"""Vehicle Tracker 1.0.0 — Windows desktop GUI, Python 3.10+, standard library only.
Run: py vehicle_tracker.py. All persistent vehicle data lives on the PHP server.
Print/PDF opens a self-contained HTML document in your browser; use Print → Save as PDF.
"""
import base64
import csv
import ctypes
import datetime as dt
import html
import io
import json
import mimetypes
import os
from pathlib import Path
import queue
import tempfile
import threading
import tkinter as tk
from tkinter import ttk, messagebox, filedialog
import urllib.error
import urllib.parse
import urllib.request
import uuid
import webbrowser

VERSION = '1.0.0'
HOME = Path(os.environ.get('APPDATA', str(Path.home()))) / 'VehicleTracker'
HOME.mkdir(exist_ok=True)
SETTINGS = HOME / 'settings.json'

def protect(value, decrypt=False):
    """Windows DPAPI binds the stored API token to the current Windows account."""
    if os.name != 'nt':
        return value
    from ctypes import wintypes
    class BLOB(ctypes.Structure):
        _fields_ = [('length', wintypes.DWORD), ('data', ctypes.POINTER(ctypes.c_byte))]
    raw = base64.b64decode(value) if decrypt else value.encode()
    buf = ctypes.create_string_buffer(raw)
    src = BLOB(len(raw), ctypes.cast(buf, ctypes.POINTER(ctypes.c_byte)))
    dst = BLOB()
    if decrypt:
        ok = ctypes.windll.crypt32.CryptUnprotectData(ctypes.byref(src), None, None, None, None, 0, ctypes.byref(dst))
    else:
        ok = ctypes.windll.crypt32.CryptProtectData(ctypes.byref(src), 'Vehicle Tracker', None, None, None, 0, ctypes.byref(dst))
    if not ok:
        raise OSError('Windows could not protect/unprotect this token. Enter it again in Connection settings.')
    try:
        result = ctypes.string_at(dst.data, dst.length)
        return result.decode() if decrypt else base64.b64encode(result).decode()
    finally:
        ctypes.windll.kernel32.LocalFree(dst.data)

def load_settings():
    try:
        s = json.loads(SETTINGS.read_text())
        if s.get('protected'):
            s['token'] = protect(s['token'], True)
        return s
    except Exception:
        return {'url': '', 'token': '', 'theme': 'light'}

def save_settings(s):
    output = dict(s)
    if os.name == 'nt':
        output['token'] = protect(s.get('token', '')); output['protected'] = True
    temp = SETTINGS.with_suffix('.tmp')
    temp.write_text(json.dumps(output), encoding='utf-8')
    try: os.chmod(temp, 0o600)
    except OSError: pass
    temp.replace(SETTINGS)

class API:
    def __init__(self, url, token):
        url = url.strip().rstrip('/')
        if url.endswith('/index.php'):
            url = url[:-10]
        if url.endswith('/public'):
            url = url[:-7]
        parsed = urllib.parse.urlparse(url)
        if parsed.scheme not in ('http', 'https') or not parsed.netloc:
            raise ValueError('Use a complete website URL such as http://localhost/vehicle-tracker')
        self.url = url + '/public/api.php?action='
        self.token = token.strip()
    def request(self, action, data=None, key=None, raw=False, multipart=None):
        headers = {'Authorization': 'Bearer ' + self.token, 'Accept': 'application/json'}
        payload = None
        if data is not None:
            headers['Content-Type'] = 'application/json'; payload = json.dumps(data, allow_nan=False).encode()
        if key: headers['Idempotency-Key'] = key
        if multipart:
            payload, content_type = multipart; headers['Content-Type'] = content_type
        req = urllib.request.Request(self.url + action, payload, headers)
        try:
            with urllib.request.urlopen(req, timeout=25) as response:
                result = response.read()
                return result if raw else json.loads(result)
        except urllib.error.HTTPError as exc:
            try: error = json.loads(exc.read()).get('error', str(exc))
            except Exception: error = str(exc)
            raise RuntimeError(error) from exc
        except (urllib.error.URLError, TimeoutError, OSError) as exc:
            raise RuntimeError('Offline or server unavailable. Your form is retained. Check URL/network and retry. ' + str(exc)) from exc
    def upload(self, record_id, path, caption, view, item, key):
        content = Path(path).read_bytes()
        if len(content) > 8 * 1024 * 1024:
            raise ValueError('Photo exceeds 8 MB.')
        mime = 'image/png' if content.startswith(b'\x89PNG\r\n\x1a\n') else 'image/jpeg' if content.startswith(b'\xff\xd8\xff') else ''
        if not mime: raise ValueError('Select a JPEG or PNG photo.')
        boundary = 'vt' + uuid.uuid4().hex
        output = io.BytesIO()
        for key, value in {'record_id':record_id, 'caption':caption, 'view_label':view, 'item_key':item}.items():
            output.write(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
        output.write(f'--{boundary}\r\nContent-Disposition: form-data; name="photo"; filename="photo.{"png" if mime=="image/png" else "jpg"}"\r\nContent-Type: {mime}\r\n\r\n'.encode())
        output.write(content); output.write(f'\r\n--{boundary}--\r\n'.encode())
        return self.request('upload', key=key, multipart=(output.getvalue(), 'multipart/form-data; boundary=' + boundary))

class App(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title('Vehicle Tracker'); self.geometry('1280x820'); self.minsize(950, 630)
        self.settings = load_settings(); self.state = None; self.api = None; self.catalog = []; self.jobs = queue.Queue(); self.editors = []
        self.style = ttk.Style(self); self.style.theme_use('clam'); self.apply_theme()
        self.status = tk.StringVar(value='Not connected · Open Connection settings to begin')
        bar = ttk.Frame(self, padding=15); bar.pack(fill='x')
        ttk.Label(bar, text='V  VEHICLE TRACKER', font=('Segoe UI', 15, 'bold')).pack(side='left')
        for text, fn in [('Connection settings',self.connection), ('Light / dark',self.theme), ('Check for updates',lambda:self.updates(True)), ('Refresh',self.refresh)]:
            ttk.Button(bar, text=text, command=fn).pack(side='right',padx=4)
        body=ttk.Panedwindow(self, orient='horizontal'); body.pack(fill='both',expand=True,padx=15)
        left=ttk.Frame(body, padding=(0,10,10,10)); body.add(left, weight=0)
        self.nav=tk.Listbox(left,width=24,font=('Segoe UI',11),borderwidth=0,highlightthickness=0,exportselection=False)
        self.nav.pack(fill='both',expand=True); self.nav.bind('<<ListboxSelect>>',lambda e:self.render())
        self.content=ttk.Frame(body,padding=15);body.add(self.content,weight=1)
        ttk.Label(self,textvariable=self.status,padding=12).pack(fill='x')
        ttk.Label(self,text='Vehicle data by VehiclesDB (vehiclesdb.com) + NHTSA vPIC · Local offline catalog · source/coverage details in CATALOG.md',padding=(12,0,12,8)).pack(fill='x')
        self.after(70,self.poll)
        self.after(150, self.connect_saved if self.settings.get('url') and self.settings.get('token') else self.connection)
    def apply_theme(self):
        dark=self.settings.get('theme')=='dark'; bg='#14232d' if dark else '#f2f6f8';panel='#1d303c' if dark else '#ffffff';fg='#e1eeee' if dark else '#193440'; muted='#98b7c4' if dark else '#637e89'
        self.configure(bg=bg)
        self.style.configure('.',background=bg,foreground=fg,font=('Segoe UI',10))
        self.style.configure('TFrame',background=bg);self.style.configure('TLabel',background=bg)
        self.style.configure('TButton',padding=(12,8),background=panel,foreground=fg)
        self.style.map('TButton',background=[('active','#087f75')],foreground=[('active','white')])
        self.style.configure('TEntry',fieldbackground=panel,foreground=fg,padding=7)
        self.style.configure('TCombobox',fieldbackground=panel,background=panel,foreground=fg,padding=6)
        self.style.map('TCombobox',fieldbackground=[('readonly',panel)],foreground=[('readonly',fg)])
        self.style.configure('Treeview',background=panel,fieldbackground=panel,foreground=fg,rowheight=34,borderwidth=0)
        self.style.configure('Treeview.Heading',background=bg,foreground=muted,padding=10)
        self.style.map('Treeview',background=[('selected','#087f75')],foreground=[('selected','white')])
        self.palette=(bg,panel,fg,muted)
        if hasattr(self,'nav'):self.nav.configure(bg=panel,fg=fg,selectbackground='#087f75',selectforeground='white')
        self.option_add('*TCombobox*Listbox.background',panel);self.option_add('*TCombobox*Listbox.foreground',fg)
    def theme(self):
        self.settings['theme']='light' if self.settings.get('theme')=='dark' else 'dark';save_settings(self.settings);self.apply_theme()
    def run_job(self,work,ok=None,fail=None):
        def worker():
            try:self.jobs.put((ok,work(),None))
            except Exception as exc:self.jobs.put((fail,None,str(exc)))
        threading.Thread(target=worker,daemon=True).start()
    def poll(self):
        try:
            while True:
                fn,result,error=self.jobs.get_nowait()
                if fn:fn(error if error else result)
                elif error:self.status.set(error)
        except queue.Empty:pass
        self.after(70,self.poll)
    def connection(self):
        w=tk.Toplevel(self);w.title('Website connection');w.geometry('650x370');w.transient(self)
        frame=ttk.Frame(w,padding=25);frame.pack(fill='both',expand=True)
        ttk.Label(frame,text='Connect to your garage',font=('Segoe UI',19,'bold')).pack(anchor='w',pady=(0,10))
        ttk.Label(frame,text='Finish website onboarding, then create a token in Settings.\nUse HTTPS outside localhost. Invalid or expired tokens are rejected.').pack(anchor='w')
        variables={}
        for key,label in [('url','Website URL (not /api.php)'),('token','API token')]:
            ttk.Label(frame,text=label).pack(anchor='w',pady=(12,3));v=tk.StringVar(value=self.settings.get(key,''));variables[key]=v
            ttk.Entry(frame,textvariable=v,show='•' if key=='token' else '').pack(fill='x')
        msg=tk.StringVar();ttk.Label(frame,textvariable=msg,wraplength=580).pack(fill='x',pady=10)
        def test():
            try:client=API(variables['url'].get(),variables['token'].get())
            except Exception as exc:msg.set(str(exc));return
            msg.set('Testing server availability and authentication…');button.state(['disabled'])
            def success(state):
                self.api=client;self.state=state;self.settings.update({k:v.get().strip() for k,v in variables.items()});save_settings(self.settings)
                w.destroy();self.connected()
            def failure(error):
                msg.set(error);button.state(['!disabled'])
            self.run_job(lambda:client.request('state'),success,failure)
        button=ttk.Button(frame,text='Test & connect',command=test);button.pack(anchor='e')
    def connect_saved(self):
        try:self.api=API(self.settings['url'],self.settings['token']);self.refresh(first=True)
        except Exception as e:self.status.set(str(e));self.connection()
    def connected(self):
        self.status.set('Connected · '+self.state['settings']['site_name']+' · Server '+self.state['version'])
        self.sections=[('dashboard','Overview')]+[(k,v['title']) for k,v in self.state['forms'].items()]
        previous=self.nav.curselection();index=previous[0] if previous else 0
        self.nav.delete(0,'end')
        for k,label in self.sections:self.nav.insert('end',label)
        self.nav.selection_set(index);self.apply_theme();self.render()
        self.run_job(lambda:self.api.request('catalog'),lambda c:setattr(self,'catalog',c['makes']))
        self.updates()
    def refresh(self,first=False):
        if not self.api:self.connection();return
        self.status.set('Loading shared server data…')
        def success(state):
            self.state=state
            if first or not hasattr(self,'sections'):self.connected()
            else:self.status.set('Connected · Refreshed '+dt.datetime.now().strftime('%H:%M:%S'));self.render()
        self.run_job(lambda:self.api.request('state'),success,lambda e:(self.status.set(e),self.connection() if first else None))
    def rows(self,kind):return [r for r in self.state['records'] if r['kind']==kind]
    def record(self,id):return next((r for r in self.state['records'] if str(r['id'])==str(id)),None)
    def name(self,r):
        if not r:return 'Unassigned'
        d=r['data']
        return f"{d.get('year','')} {d.get('make','')} {d.get('model','')} · {d.get('plate','')}" if r['kind']=='vehicles' else str(d.get('name') or d.get('reference') or d.get('number') or f"{r['kind']} #{r['id']}")
    def money(self,x):return self.state['settings']['currency']+' '+format(float(x or 0),',.2f')
    def stat(self,kind,id):
        d=self.state['stats'].get(kind,{})
        return d.get(str(id),{}) if isinstance(d,dict) else (d[id] if isinstance(d,list) and id<len(d) else {})
    def section(self):
        sel=self.nav.curselection();return self.sections[sel[0] if sel else 0][0]
    def render(self):
        if not self.state:return
        for child in self.content.winfo_children():child.destroy()
        k=self.section();label='Garage overview' if k=='dashboard' else self.state['forms'][k]['title']
        ttk.Label(self.content,text=label,font=('Segoe UI',25,'bold')).pack(anchor='w',pady=(0,20))
        if k=='dashboard':self.dashboard();return
        bar=ttk.Frame(self.content);bar.pack(fill='x',pady=(0,12))
        ttk.Button(bar,text='+ New record',command=lambda:Editor(self,k)).pack(side='right')
        ttk.Button(bar,text='Export filtered CSV',command=self.export_csv).pack(side='right',padx=6)
        if k in ('sales','inspections'):ttk.Button(bar,text='Blank print / PDF',command=lambda:self.document(k,{})).pack(side='right')
        self.search=tk.StringVar();ttk.Entry(bar,textvariable=self.search,width=25).pack(side='left');ttk.Label(bar,text=' Search all fields').pack(side='left')
        filters=ttk.Frame(self.content);filters.pack(fill='x',pady=(0,12));self.filters={}
        for field,label,options in [('vehicle_id','Vehicle',['All']+[f"#{r['id']} {self.name(r)}" for r in self.rows('vehicles')]),('driver_id','Driver',['All']+[f"#{r['id']} {self.name(r)}" for r in self.rows('drivers')]),('from','From YYYY-MM-DD',None),('to','Through YYYY-MM-DD',None)]:
            box=ttk.Frame(filters);box.pack(side='left',fill='x',expand=True,padx=(0,8));ttk.Label(box,text=label).pack(anchor='w')
            v=tk.StringVar(value='All' if options else '');self.filters[field]=v
            widget=ttk.Combobox(box,textvariable=v,values=options,state='readonly',width=20) if options else ttk.Entry(box,textvariable=v,width=16)
            widget.pack(fill='x');v.trace_add('write',lambda *args:self.fill_table())
        if k=='inspections':
            extra=ttk.Frame(self.content);extra.pack(fill='x',pady=(0,10))
            for field,label,values in [('week','Week Monday',None),('result','Result',['All','Pass','Needs attention','Fail','N/A']),('unresolved','Unresolved',['All','Only unresolved'])]:
                ttk.Label(extra,text=label).pack(side='left',padx=6);v=tk.StringVar(value='All' if values else '');self.filters[field]=v
                (ttk.Combobox(extra,textvariable=v,values=values,width=18,state='readonly') if values else ttk.Entry(extra,textvariable=v,width=14)).pack(side='left');v.trace_add('write',lambda *a:self.fill_table())
        self.count=tk.StringVar();ttk.Label(self.content,textvariable=self.count).pack(anchor='w',pady=(0,10))
        defaults={'vehicles':['year','make','model','plate','odometer','engine_miles'],'trips':['date','vehicle_id','driver_id','start','end','distance','purpose'],'expenses':['date','vehicle_id','category','odometer','gallons','unit_price','amount','mpg'],'invoices':['number','vehicle_id','date','due_date','amount','balance','status'],'drivers':['name','active','expires','trips','distance'],'inspections':['reference','vehicle_id','inspector','week','status'],'sales':['reference','buyer_name','seller_name','price','deposit','balance','status']}
        self.cols=defaults.get(k,[f['key'] for f in self.state['forms'][k]['fields'] if f['type']!='textarea'][:8]);area=ttk.Frame(self.content);area.pack(fill='both',expand=True)
        self.tree=ttk.Treeview(area,columns=self.cols,show='headings',selectmode='browse');y=ttk.Scrollbar(area,orient='vertical',command=self.tree.yview);x=ttk.Scrollbar(area,orient='horizontal',command=self.tree.xview);self.tree.configure(yscrollcommand=y.set,xscrollcommand=x.set)
        self.tree.grid(row=0,column=0,sticky='nsew');y.grid(row=0,column=1,sticky='ns');x.grid(row=1,column=0,sticky='ew');area.columnconfigure(0,weight=1);area.rowconfigure(0,weight=1)
        for c in self.cols:self.tree.heading(c,text=c.replace('_id','').replace('_',' ').title());self.tree.column(c,width=145,minwidth=100)
        self.tree.bind('<Double-1>',lambda e:self.edit_selected());ttk.Button(self.content,text='Open selected record',command=self.edit_selected).pack(anchor='e',pady=12)
        self.search.trace_add('write',lambda *a:self.fill_table());self.fill_table()
    def dashboard(self):
        s=self.state['stats'];unit=self.state['settings']['unit'];grid=ttk.Frame(self.content);grid.pack(fill='x',pady=10)
        for i,(label,value) in enumerate([('Recorded trips',s['trip_count']),('Total distance',f"{s['distance']:,.1f} {unit}"),('Expenses',self.money(s['expenses'])),('Mechanic debt',self.money(s['debt']))]):
            frame=ttk.LabelFrame(grid,text=label,padding=20);frame.grid(row=0,column=i,sticky='ew',padx=5);grid.columnconfigure(i,weight=1);ttk.Label(frame,text=str(value),font=('Segoe UI',19,'bold')).pack()
        ttk.Label(self.content,text='Your vehicles',font=('Segoe UI',17,'bold')).pack(anchor='w',pady=(25,10))
        for r in self.rows('vehicles'):
            v=self.stat('vehicles',r['id']);frame=ttk.Frame(self.content,padding=14);frame.pack(fill='x',pady=4)
            ttk.Label(frame,text=self.name(r),font=('Segoe UI',13,'bold')).pack(anchor='w')
            ttk.Label(frame,text=f"Odometer {v['odometer']:,.1f} {unit}  ·  Engine {v['engine_miles']:,.1f} {unit}  ·  {v['weekly_status']}  ·  {v['unresolved']} unresolved issues").pack(anchor='w',pady=5)
            ttk.Button(frame,text='Vehicle / photos',command=lambda r=r:Editor(self,'vehicles',r)).pack(anchor='e')
        ttk.Label(self.content,text='Completed inspections are observations, not a safety certification.\nDriver records do not automatically satisfy a particular compliance standard.').pack(anchor='w',pady=25)
    def filtered(self):
        k=self.section();out=[]
        for r in self.rows(k):
            d=r['data'];f={key:v.get() for key,v in self.filters.items()}
            if self.search.get().lower() not in json.dumps(d).lower():continue
            skip=False
            for key in ('vehicle_id','driver_id'):
                if f.get(key,'All')!='All':
                    id=f[key].split()[0][1:];actual=r['id'] if (k=='vehicles' and key=='vehicle_id') or (k=='drivers' and key=='driver_id') else d.get(key,'')
                    if str(actual)!=id:skip=True
            if skip:continue
            date=d.get('date',d.get('baseline_date',''))
            if f.get('from') and date<f['from']:continue
            if f.get('to') and date>f['to']:continue
            if f.get('week') and d.get('week')!=f['week']:continue
            if f.get('result','All')!='All' and not any(i.get('result')==f['result'] for i in d.get('items',{}).values()):continue
            if f.get('unresolved','All')!='All' and not any(str(i['data'].get('inspection_id'))==str(r['id']) and i['data']['status']!='Resolved' for i in self.rows('issues')):continue
            out.append(r)
        return sorted(out,key=lambda r:(r['data'].get('date',r['created']),r['id']),reverse=True)
    def cell(self,r,c):
        d=r['data'];k=r['kind'];v=d.get(c,'—')
        if c.endswith('_id'):return self.name(self.record(v))
        if k=='vehicles' and c in ('odometer','engine_miles'):return self.stat('vehicles',r['id']).get(c)
        if k=='drivers' and c in ('trips','distance'):return self.stat('drivers',r['id']).get(c)
        if k=='drivers' and c=='expires':return str(v)+(' · EXPIRED' if self.stat('drivers',r['id']).get('expired') else '')
        if k=='invoices' and c in ('balance','status'):v=self.stat('invoices',r['id']).get(c)
        if c=='mpg':
            fuels=self.state['stats']['fuel'];v=fuels.get(str(r['id'])) if isinstance(fuels,dict) else None
            return f'{v} US MPG' if v is not None else '—'
        if c in ('amount','balance','price','deposit','unit_price') and isinstance(v,(int,float)):return self.money(v)
        if k=='trips' and c=='distance':return float(d['end'])-float(d['start'])
        if isinstance(v,bool):return 'Yes' if v else 'No'
        return str(v)
    def fill_table(self):
        if not hasattr(self,'tree') or not self.tree.winfo_exists():return
        self.tree.delete(*self.tree.get_children());rr=self.filtered()
        for r in rr:self.tree.insert('', 'end',iid=str(r['id']),values=[self.cell(r,c) for c in self.cols])
        k=self.section();text=f'{len(rr)} record(s)'
        if k=='trips':text+=f" · {sum(float(r['data']['end'])-float(r['data']['start']) for r in rr):,.1f} {self.state['settings']['unit']}"
        if k in ('expenses','invoices'):text+=' · '+self.money(sum(float(r['data'].get('amount',0)) for r in rr))
        self.count.set(text)
    def edit_selected(self):
        selected=self.tree.selection()
        if selected:Editor(self,self.section(),self.record(selected[0]))
    def export_csv(self):
        path=filedialog.asksaveasfilename(defaultextension='.csv',initialfile=self.section()+'.csv',filetypes=[('CSV','*.csv')])
        if path:
            with open(path,'w',newline='',encoding='utf-8-sig') as f:
                w=csv.writer(f);w.writerow(self.cols)
                for r in self.filtered():w.writerow([self.cell(r,c) for c in self.cols])
            self.status.set('CSV exported: '+path)
    def updates(self,manual=False):
        if not self.api:return
        def result(u):
            if u.get('newer') and (manual or self.settings.get('dismissed')!=u['latest']):
                answer=messagebox.askyesno('Update available',f"Installed {u['installed']} · Latest {u['latest']}\nWould you like to download the latest version?\n{u['download_label']}\nNo code will be run or installed automatically.")
                self.settings['dismissed']=u['latest'];save_settings(self.settings)
                if answer:webbrowser.open(u['download_url'])
            elif manual:messagebox.showinfo('Update check',u.get('message','')+f"\nInstalled: {u['installed']}\nLatest: {u.get('latest','not available')}")
        self.run_job(lambda:self.api.request('updates'+('&manual=1' if manual else '')),result,lambda e:self.status.set('Update check: '+e))
    def document(self,kind,data,record_id=None):
        path=filedialog.asksaveasfilename(defaultextension='.html',initialfile=('bill-of-sale' if kind=='sales' else 'weekly-inspection')+'.html',filetypes=[('Printable HTML / browser Save as PDF','*.html')])
        if not path:return
        # Explicitly chosen local export; no personal document is sent to an external service.
        def make():
            esc=lambda v:html.escape(str(v if v is not None else ''))
            lines=[]
            for f in self.state['forms'][kind]['fields']:
                if f['key'] in ('status','reviewed','acknowledged'):continue
                v=data.get(f['key'],'________________________________________________________________')
                if f['key'].endswith('_id') and v!='________________________________________________________________':v=self.name(self.record(v))
                lines.append('<section><b>'+esc(f['label'])+'</b><div>'+esc(v)+'</div></section>')
            if data.get('paper_signing'):lines.append('<section>Paper signing metadata (not an electronic signature): '+esc(json.dumps(data['paper_signing'],indent=2))+'</section>')
            if kind=='inspections':
                lines.append('<h2>Checklist</h2><table><tr><th>Item</th><th>Result</th><th>Notes</th></tr>')
                for key,label in self.state['checklist'].items():
                    item=data.get('items',{}).get(key,{})
                    lines.append('<tr><td>'+esc(label)+'</td><td>'+esc(item.get('result') or '□ Pass  □ Needs attention  □ Fail  □ N/A')+'</td><td>'+esc(item.get('notes','________________________'))+'</td></tr>')
                lines.append('</table><h2>Follow-up and evidence</h2>')
                for issue in self.rows('issues'):
                    d=issue['data']
                    if str(d.get('inspection_id'))==str(record_id):lines.append('<section>'+esc(json.dumps(d,ensure_ascii=False,indent=2))+'</section>')
                for photo in self.state['files']:
                    if str(photo['record_id'])==str(record_id):
                        content=self.api.request('image&id='+str(photo['id']),raw=True);mime='image/png' if content.startswith(b'\x89PNG') else 'image/jpeg'
                        lines.append(f'<figure><img src="data:{mime};base64,{base64.b64encode(content).decode()}"><figcaption>{esc(photo.get("caption"))} · {esc(photo.get("item_key"))}</figcaption></figure>')
            signatures=['Buyer signature / printed name / signing date','Seller signature / printed name / signing date','Witness / notary (if required)'] if kind=='sales' else ['Inspector signature / printed name / signing date']
            lines.extend('<div class="signature">'+s+'</div>' for s in signatures)
            heading='VEHICLE BILL OF SALE' if kind=='sales' else 'WEEKLY VEHICLE INSPECTION'
            result='<!doctype html><html><meta charset="utf-8"><title>'+heading+'</title><style>body{font:13px Segoe UI,Arial;color:#111;background:#fff;max-width:850px;margin:35px auto;line-height:1.6}h1{font-size:26px}section{border-bottom:1px solid #ddd;padding:10px 0;white-space:pre-wrap;overflow-wrap:anywhere;break-inside:avoid}table{border-collapse:collapse;width:100%;font-size:11px}td,th{border:1px solid #ccc;text-align:left;padding:9px;white-space:pre-wrap}tr,figure{break-inside:avoid}thead{display:table-header-group}.signature{margin-top:55px;border-top:1px solid #555;padding-top:8px}img{max-width:260px;max-height:180px}@media print{button{display:none}body{margin:0}}@page{margin:16mm}</style><button onclick="print()">Print / Save as PDF</button><h1>'+heading+'</h1><p>Reference: '+esc(data.get('reference','Blank form'))+' · '+esc(data.get('status','Blank'))+'</p><p>'+('General editable template. Confirm jurisdiction-specific requirements and official title-transfer documents. No electronic signatures inserted.' if kind=='sales' else 'Observations only, not a safety certification. Week Monday: '+esc(data.get('week','________'))+' · Next due: '+esc(data.get('next_due','________'))+' · Acknowledgment: '+('Explicit action by '+esc(data.get('inspector',''))+' at '+esc(data.get('completed_at','pending')) if data.get('acknowledged') else 'Not acknowledged'))+'</p>'+''.join(lines)+'</html>'
            Path(path).write_text(result,encoding='utf-8');return path
        self.run_job(make,lambda p:(self.status.set('Private local document exported. Browser Print → Save as PDF: '+p),webbrowser.open(Path(p).resolve().as_uri())),lambda e:messagebox.showerror('Export failed',e))

class Editor(tk.Toplevel):
    def __init__(self,app,kind,record=None):
        super().__init__(app);self.app=app;self.kind=kind;self.record=json.loads(json.dumps(record)) if record else None
        self.title(app.state['forms'][kind]['title']);self.geometry('960x780');self.minsize(700,500);self.vars={};self.widgets={};self.maps={};self.checks={};self.dirty=False;self.busy=False;self.pending=None;self.upload_pending=None
        self.locked=bool(record and kind in ('sales','inspections') and record['data'].get('status')!='Draft')
        data=record['data'] if record else {'date':dt.date.today().isoformat(),'unit':app.state['settings']['unit'],'currency':app.state['settings']['currency'],'status':'Open' if kind=='issues' else 'Draft'}
        self.original=data;self.protocol('WM_DELETE_WINDOW',self.close)
        top=ttk.Frame(self,padding=15);top.pack(fill='x');ttk.Label(top,text=app.state['forms'][kind]['title'],font=('Segoe UI',19,'bold')).pack(side='left')
        self.indicator=tk.StringVar(value='Locked snapshot' if self.locked else 'Not saved');ttk.Label(top,textvariable=self.indicator).pack(side='right')
        self.error=tk.StringVar();ttk.Label(self,textvariable=self.error,foreground='#c94e54',wraplength=880,padding=(15,0)).pack(fill='x')
        self.book=ttk.Notebook(self);self.book.pack(fill='both',expand=True,padx=15,pady=10)
        form_tab=ttk.Frame(self.book);self.book.add(form_tab,text='Record details');self.form=self.scroll_frame(form_tab)
        if kind=='sales' and not self.locked:
            templatebar=ttk.Frame(self.form);templatebar.grid(row=0,column=0,columnspan=2,sticky='ew',pady=10)
            self.template_var=tk.StringVar(value='General blank template · no default waivers')
            self.template_map={f"#{r['id']} {app.name(r)} · {r['data']['jurisdiction']}":r for r in app.rows('sale_templates')}
            ttk.Combobox(templatebar,textvariable=self.template_var,values=list(self.template_map),width=65,state='readonly').pack(side='left')
            ttk.Button(templatebar,text='Apply for review',command=self.apply_template).pack(side='left',padx=6)
        if kind=='sales':ttk.Label(self.form,text='General editable template. Review every term. No signatures or waivers are inserted.\nFinalization locks a snapshot. Sign by hand on the printed agreement.',wraplength=820).grid(row=1,column=0,columnspan=2,sticky='w',pady=10)
        for i,f in enumerate(app.state['forms'][kind]['fields'],start=2):self.create_field(self.form,f,data,i)
        if kind=='inspections':
            tab=ttk.Frame(self.book);self.book.add(tab,text='Fillable inspection checklist');body=self.scroll_frame(tab)
            self.progress=tk.StringVar();ttk.Label(body,textvariable=self.progress,font=('Segoe UI',12,'bold')).pack(anchor='w',pady=10)
            self.bar=ttk.Progressbar(body,maximum=len(app.state['checklist']));self.bar.pack(fill='x',pady=10)
            for key,label in app.state['checklist'].items():
                frame=ttk.LabelFrame(body,text=label,padding=12);frame.pack(fill='x',pady=6)
                result=tk.StringVar(value=data.get('items',{}).get(key,{}).get('result',''));notes=tk.StringVar(value=data.get('items',{}).get(key,{}).get('notes',''))
                choices=ttk.Frame(frame);choices.pack(fill='x')
                for value in ['Pass','Needs attention','Fail','N/A']:ttk.Radiobutton(choices,text=value,value=value,variable=result,state='disabled' if self.locked else 'normal').pack(side='left',padx=8)
                ttk.Label(frame,text='Notes required for Needs attention / Fail. Photo item key: '+key).pack(anchor='w',pady=(8,3));entry=ttk.Entry(frame,textvariable=notes);entry.pack(fill='x')
                if self.locked:entry.state(['disabled'])
                status=tk.StringVar();ttk.Label(frame,textvariable=status).pack(anchor='w',pady=5)
                self.checks[key]=(result,notes,status)
                result.trace_add('write',self.changed);notes.trace_add('write',self.changed)
        if kind in ('vehicles','inspections','sales'):
            self.photos_tab=ttk.Frame(self.book,padding=18);self.book.add(self.photos_tab,text='Private photos / evidence');self.photos()
        if kind=='sales':
            preview_tab=ttk.Frame(self.book,padding=12);self.book.add(preview_tab,text='Live agreement preview')
            self.live_preview=tk.Text(preview_tab,wrap='word',font=('Segoe UI',11),bg='white',fg='#182c38',padx=20,pady=20)
            preview_scroll=ttk.Scrollbar(preview_tab,orient='vertical',command=self.live_preview.yview);self.live_preview.configure(yscrollcommand=preview_scroll.set);preview_scroll.pack(side='right',fill='y');self.live_preview.pack(fill='both',expand=True)
        actions=ttk.Frame(self,padding=15);actions.pack(fill='x')
        if not self.locked:ttk.Button(actions,text='Save record',command=self.save).pack(side='right',padx=4)
        if kind in ('sales','inspections'):
            ttk.Button(actions,text='Preview / Print / PDF',command=lambda:app.document(kind,self.data(),self.record['id'] if self.record else None)).pack(side='right',padx=4)
            if self.locked:ttk.Button(actions,text='Create revision',command=self.revise).pack(side='right',padx=4)
            else:ttk.Button(actions,text='Save draft',command=lambda:(self.vars['status'].set('Draft'),self.save())).pack(side='right',padx=4)
        if self.locked and kind=='sales' and data.get('status')=='Finalized':ttk.Button(actions,text='Record paper signing',command=self.paper_signed).pack(side='right',padx=4)
        if record:
            ttk.Button(actions,text='Revision history',command=self.history).pack(side='left')
            if kind in ('trips','expenses','payments','driver_records'):ttk.Button(actions,text='Delete',command=self.delete).pack(side='left',padx=5)
        self.summary=tk.StringVar();ttk.Label(self,textvariable=self.summary,padding=(18,0,18,12)).pack(fill='x')
        self.update_live();self.dirty=False;self.after(5000,self.autosave)
    def scroll_frame(self,parent):
        frame=ttk.Frame(parent);frame.pack(fill='both',expand=True);canvas=tk.Canvas(frame,bg=self.app.palette[0],highlightthickness=0);bar=ttk.Scrollbar(frame,orient='vertical',command=canvas.yview);canvas.configure(yscrollcommand=bar.set);bar.pack(side='right',fill='y');canvas.pack(side='left',fill='both',expand=True)
        body=ttk.Frame(canvas,padding=15);window=canvas.create_window((0,0),window=body,anchor='nw');body.bind('<Configure>',lambda e:canvas.configure(scrollregion=canvas.bbox('all')));canvas.bind('<Configure>',lambda e:canvas.itemconfigure(window,width=e.width));body.columnconfigure(1,weight=1)
        def wheel(e):
            if canvas.winfo_exists():canvas.yview_scroll(-int(e.delta/120),'units')
        canvas.bind('<Enter>',lambda e:canvas.bind_all('<MouseWheel>',wheel));canvas.bind('<Leave>',lambda e:canvas.unbind_all('<MouseWheel>'))
        return body
    def create_field(self,parent,f,data,row):
        key=f['key'];typ=f['type'];v=data.get(key,'')
        var=tk.BooleanVar(value=bool(v)) if typ=='checkbox' else tk.StringVar(value=str(v) if v is not None else '')
        self.vars[key]=var;ttk.Label(parent,text=f['label']+(' (YYYY-MM-DD)' if typ=='date' else ' (HH:MM)' if typ=='time' else ''),wraplength=280).grid(row=row,column=0,sticky='nw',pady=9,padx=(0,18))
        if typ=='checkbox':widget=ttk.Checkbutton(parent,variable=var)
        elif typ.startswith('ref:'):
            options={'— Select / unassigned —':''}
            for r in self.app.rows(typ[4:]):options[f"#{r['id']} {self.app.name(r)}"]=r['id']
            self.maps[key]=options;var.set(next((label for label,id in options.items() if str(id)==str(v)),'— Select / unassigned —'));widget=ttk.Combobox(parent,textvariable=var,values=list(options),state='readonly')
            if key=='vehicle_id':widget.bind('<<ComboboxSelected>>',self.prefill)
        elif typ.startswith('select:'):
            widget=ttk.Combobox(parent,textvariable=var,values=['']+typ[7:].split(','),state='readonly')
        elif typ in ('make','model'):
            values=[m['name'] for m in self.app.catalog] if typ=='make' else self.model_names(data.get('make',''))
            widget=ttk.Combobox(parent,textvariable=var,values=values)
            def typed(event):
                if key=='make' and 'model' in self.vars:self.vars['model'].set('');self.widgets['model']['values']=self.model_names(var.get())
                values=[m['name'] for m in self.app.catalog] if key=='make' else self.model_names(self.vars.get('make').get())
                widget['values']=[x for x in values if var.get().casefold() in x.casefold()]
            widget.bind('<KeyRelease>',typed)
            if key=='make':widget.bind('<<ComboboxSelected>>',lambda e:(self.vars['model'].set(''),self.widgets['model'].configure(values=self.model_names(var.get()))))
        elif typ=='textarea':
            widget=tk.Text(parent,height=4,wrap='word',font=('Segoe UI',10),bg=self.app.palette[1],fg=self.app.palette[2],insertbackground=self.app.palette[2],relief='flat',padx=8,pady=8);widget.insert('1.0',str(v or ''))
            widget.bind('<<Modified>>',lambda e,w=widget:self.text_changed(w))
        else:widget=ttk.Entry(parent,textvariable=var)
        widget.grid(row=row,column=1,sticky='ew',pady=6);self.widgets[key]=widget
        if self.locked:
            if isinstance(widget,tk.Text):widget.configure(state='disabled')
            else:widget.state(['disabled'])
        var.trace_add('write',self.changed)
    def text_changed(self,w):
        if w.edit_modified():self.changed();w.edit_modified(False)
    def model_names(self,make):return next(([m['name'] for m in row['models']] for row in self.app.catalog if row['name']==make),[])
    def data(self):
        d=dict(self.record['data'] if self.record else {})
        for f in self.app.state['forms'][self.kind]['fields']:
            key=f['key'];w=self.widgets[key]
            v=w.get('1.0','end-1c') if isinstance(w,tk.Text) else self.vars[key].get()
            if key in self.maps:v=self.maps[key].get(v,'')
            d[key]=v
        if self.kind=='sales':
            try:d['balance']=round(float(d.get('price') or 0)-float(d.get('deposit') or 0),2)
            except ValueError:pass
        if self.kind=='expenses' and d.get('category')=='Fuel':
            try:d['amount']=round(float(d.get('gallons') or 0)*float(d.get('unit_price') or 0),2)
            except ValueError:pass
        if self.kind=='inspections':d['items']={key:{'result':r.get(),'notes':n.get()} for key,(r,n,s) in self.checks.items()}
        return d
    def changed(self,*args):
        if self.locked:return
        self.dirty=True;self.indicator.set('Unsaved changes · draft autosave every 5 seconds');self.update_live()
    def update_live(self):
        if not hasattr(self,'summary'):return
        try:
            if self.kind=='expenses':
                fuel=self.vars['category'].get()=='Fuel'
                for key in ['odometer','gallons','unit_price','full']:
                    self.widgets[key].state(['!disabled'] if fuel else ['disabled'])
                self.widgets['amount'].state(['readonly'] if fuel else ['!readonly'])
                if fuel:
                    total=round(float(self.vars['gallons'].get() or 0)*float(self.vars['unit_price'].get() or 0),2)
                    self.summary.set(f"{self.vars['gallons'].get()} US gal × {self.vars['unit_price'].get()} = {self.app.money(total)} · Server recalculates total")
                else:self.summary.set('Non-fuel expenses clear all fuel fields on the server.')
            if self.kind=='sales':
                balance=float(self.vars['price'].get() or 0)-float(self.vars['deposit'].get() or 0);self.summary.set('Remaining balance: '+self.app.money(balance)+' · Finalized snapshots are locked; handwritten signatures only.')
                self.widgets['balance'].state(['readonly'])
                if hasattr(self,'live_preview'):
                    d=self.data();parts=['VEHICLE BILL OF SALE',str(d.get('reference','Draft · not yet assigned')),'General editable template — confirm local title-transfer requirements.','']
                    for field in self.app.state['forms']['sales']['fields']:
                        if field['key'] in ('reviewed','status'):continue
                        value=d.get(field['key'],'')
                        if field['key'].endswith('_id') and value:value=self.app.name(self.app.record(value))
                        parts.extend([field['label'].upper(),str(value),''])
                    parts.extend(['Buyer signature: ________________________   Date: __________','Seller signature: ________________________   Date: __________','Witness / notary (if required): ________________________'])
                    self.live_preview.configure(state='normal');self.live_preview.delete('1.0','end');self.live_preview.insert('1.0','\n'.join(parts));self.live_preview.configure(state='disabled')
            if self.kind=='inspections':
                done=sum(bool(r.get()) for r,n,s in self.checks.values());issues=sum(r.get() in ('Needs attention','Fail') for r,n,s in self.checks.values())
                self.progress.set(f'{done} / {len(self.checks)} completed · {issues} findings');self.bar['value']=done
                for r,n,s in self.checks.values():s.set('INCOMPLETE' if not r.get() else 'FINDING · explanation required' if r.get() in ('Needs attention','Fail') else 'Recorded')
        except (ValueError,tk.TclError):self.summary.set('Enter valid numeric values; the server will validate all readings.')
    def prefill(self,event=None):
        if self.kind not in ('sales','inspections'):return
        vehicle=self.app.record(self.maps['vehicle_id'].get(self.vars['vehicle_id'].get()))
        if not vehicle:return
        for key in ('year','make','model','vin','plate','color'):
            if key in self.vars:self.vars[key].set(str(vehicle['data'].get(key,'')))
        self.vars['odometer'].set(str(self.app.stat('vehicles',vehicle['id']).get('odometer','')))
    def autosave(self):
        if not self.winfo_exists():return
        if self.kind in ('sales','inspections') and not self.locked and self.dirty and not self.busy and self.vars['status'].get()=='Draft':self.save(auto=True)
        self.after(5000,self.autosave)
    def save(self,auto=False):
        if self.busy:return
        payload={'kind':self.kind,'id':self.record['id'] if self.record else 0,'version':self.record['version'] if self.record else 0,'data':self.data()}
        snapshot=json.dumps(payload['data'],sort_keys=True)
        if not self.pending or self.pending[0]!=json.dumps(payload,sort_keys=True):self.pending=(json.dumps(payload,sort_keys=True),uuid.uuid4().hex)
        key=self.pending[1];self.busy=True;self.indicator.set('Saving…');self.error.set('')
        def success(r):
            if not self.winfo_exists():return
            # Preserve any additional edits typed while the request was in flight.
            current=json.dumps(self.data(),sort_keys=True);self.record=r;self.pending=None;self.busy=False;self.dirty=current!=snapshot;self.indicator.set('Saved '+dt.datetime.now().strftime('%H:%M:%S')+f" · #{r['id']}"+(' · newer edits not saved' if self.dirty else ''))
            existing=self.app.record(r['id'])
            if existing:self.app.state['records']=[r if x['id']==r['id'] else x for x in self.app.state['records']]
            else:self.app.state['records'].append(r)
            if hasattr(self,'photos_tab') and not auto:self.photos()
            self.app.refresh()
            if self.kind in ('sales','inspections') and r['data']['status']!='Draft':self.destroy();Editor(self.app,self.kind,r)
        def failure(error):
            if self.winfo_exists():self.busy=False;self.error.set(error);self.indicator.set('Not saved · correct values or retry')
        self.app.run_job(lambda:self.app.api.request('save',payload,key),success,failure)
    def photos(self):
        for w in self.photos_tab.winfo_children():w.destroy()
        if self.record and self.kind=='vehicles':
            for engine in self.app.stat('vehicles',self.record['id']).get('engines',[]):
                ttk.Label(self.photos_tab,text=f"Engine {engine.get('serial','')} · installed {engine['date']} at {engine['odometer']} · baseline {engine['engine_miles']} → {engine['current_miles']} {self.app.state['settings']['unit']} · {('Retired '+str(engine['retirement_date'])) if engine['retired'] else 'Current'}",wraplength=840).pack(anchor='w',pady=4)
        if not self.record:ttk.Label(self.photos_tab,text='Save the record before adding photos.').pack(anchor='w');return
        for f in self.app.state['files']:
            if str(f['record_id'])!=str(self.record['id']):continue
            frame=ttk.Frame(self.photos_tab);frame.pack(fill='x',pady=5)
            ttk.Label(frame,text=f"#{f['id']} · {f['view_label']} · {f.get('item_key','')} · {f['caption']}",wraplength=600).pack(side='left')
            ttk.Button(frame,text='View photo',command=lambda f=f:self.view_photo(f)).pack(side='right')
            if self.kind=='vehicles':ttk.Button(frame,text='Set cover',command=lambda f=f:(self.vars['cover_id'].set(str(f['id'])),self.save())).pack(side='right',padx=4)
        ttk.Separator(self.photos_tab).pack(fill='x',pady=15)
        self.photo_path=tk.StringVar();self.photo_caption=tk.StringVar();self.photo_view=tk.StringVar(value='front');self.photo_item=tk.StringVar()
        ttk.Label(self.photos_tab,text='JPEG / PNG, up to 8 MB. Evidence is stored with this inspection independently of vehicle photos.').pack(anchor='w')
        ttk.Entry(self.photos_tab,textvariable=self.photo_path).pack(fill='x',pady=8)
        def choose():
            p=filedialog.askopenfilename(filetypes=[('JPEG and PNG','*.jpg *.jpeg *.png')])
            if p:self.photo_path.set(p);self.preview_local(p)
        ttk.Button(self.photos_tab,text='Choose photo & preview',command=choose).pack(anchor='w')
        for label,var in [('Caption',self.photo_caption),('View: front / rear / sides / interior / odometer / finding',self.photo_view),('Finding item key (optional)',self.photo_item)]:ttk.Label(self.photos_tab,text=label).pack(anchor='w',pady=(10,3));ttk.Entry(self.photos_tab,textvariable=var).pack(fill='x')
        ttk.Button(self.photos_tab,text='Upload private photo',command=self.upload).pack(anchor='e',pady=15)
    def preview_local(self,path):
        try:
            raw=Path(path).read_bytes();mime='image/png' if raw.startswith(b'\x89PNG') else 'image/jpeg';p=HOME/'photo-preview.html';p.write_text('<html><title>Local photo preview</title><body><img style="max-width:100%;max-height:90vh" src="data:'+mime+';base64,'+base64.b64encode(raw).decode()+'"></body></html>');webbrowser.open(p.as_uri())
        except Exception as exc:self.error.set(str(exc))
    def view_photo(self,photo):
        def success(raw):
            p=HOME/('photo-preview.png' if raw.startswith(b'\x89PNG') else 'photo-preview.jpg');p.write_bytes(raw);webbrowser.open(p.as_uri())
        self.app.run_job(lambda:self.app.api.request('image&id='+str(photo['id']),raw=True),success,lambda e:self.error.set(e))
    def upload(self):
        if self.busy:return
        rid=self.record['id'];path=self.photo_path.get();caption=self.photo_caption.get();view=self.photo_view.get();item=self.photo_item.get()
        sig=(rid,path,Path(path).stat().st_mtime if Path(path).is_file() else 0,caption,view,item)
        if not self.upload_pending or self.upload_pending[0]!=sig:self.upload_pending=(sig,uuid.uuid4().hex)
        key=self.upload_pending[1]
        self.busy=True;self.indicator.set('Uploading…')
        def success(result):
            def refreshed(state):
                self.app.state=state;self.busy=False;self.upload_pending=None;self.indicator.set('Photo uploaded');self.photos();self.app.render()
            self.app.run_job(lambda:self.app.api.request('state'),refreshed,lambda e:(setattr(self,'busy',False),self.error.set(e)))
        self.app.run_job(lambda:self.app.api.upload(rid,path,caption,view,item,key),success,lambda e:(setattr(self,'busy',False),self.error.set(e),self.indicator.set('Upload failed · input retained')))
    def history(self):
        def success(history):
            w=tk.Toplevel(self);w.title('Immutable audit / revision history');w.geometry('800x600');text=tk.Text(w,wrap='word');text.pack(fill='both',expand=True)
            for r in history:text.insert('end',r['created']+' · '+r['action']+'\n'+json.dumps(json.loads(r['payload']),indent=2)+'\n\n')
            text.configure(state='disabled')
        self.app.run_job(lambda:self.app.api.request('history&id='+str(self.record['id'])),success,lambda e:self.error.set(e))
    def apply_template(self):
        r=self.template_map.get(self.template_var.get())
        if not r:return
        if not messagebox.askyesno('Apply template','Replace editable terms with this template for review? No signatures or waivers are assumed.',parent=self):return
        for key in ('jurisdiction','condition','defects','accessories','title_liens','warranty','additional_terms','payment_terms'):
            if not r['data'].get(key):continue
            w=self.widgets[key]
            if isinstance(w,tk.Text):w.delete('1.0','end');w.insert('1.0',r['data'][key])
            else:self.vars[key].set(r['data'][key])
        self.error.set('Review template instructions: '+r['data'].get('instructions','Review every term.'));self.dirty=True
    def paper_signed(self):
        from tkinter.simpledialog import askstring
        buyer=askstring('Paper signing','Actual buyer handwritten-signing date (YYYY-MM-DD):',parent=self)
        if not buyer:return
        seller=askstring('Paper signing','Actual seller handwritten-signing date (YYYY-MM-DD):',parent=self)
        if not seller:return
        if not messagebox.askyesno('Confirm handwritten signing','I confirm both parties actually signed this finalized printed agreement by hand. This records paper signing only; no electronic signatures are inserted.',parent=self):return
        payload={'id':self.record['id'],'version':self.record['version'],'buyer_date':buyer,'seller_date':seller,'confirmed':True}
        self.app.run_job(lambda:self.app.api.request('mark-signed',payload,uuid.uuid4().hex),lambda r:(self.destroy(),Editor(self.app,self.kind,r),self.app.refresh()),lambda e:self.error.set(e))
    def revise(self):
        from tkinter.simpledialog import askstring
        reason=askstring('Create identified revision','Reason for correction (original is preserved):',parent=self)
        if not reason:return
        self.app.run_job(lambda:self.app.api.request('revise',{'id':self.record['id'],'reason':reason},uuid.uuid4().hex),lambda r:(self.destroy(),Editor(self.app,self.kind,r),self.app.refresh()),lambda e:self.error.set(e))
    def delete(self):
        if not messagebox.askyesno('Delete','Delete this record? Audit history is retained.',parent=self):return
        self.app.run_job(lambda:self.app.api.request('delete',{'id':self.record['id'],'version':self.record['version']},uuid.uuid4().hex),lambda r:(self.destroy(),self.app.refresh()),lambda e:self.error.set(e))
    def close(self):
        if self.busy:messagebox.showinfo('Operation in progress','Wait for the current save or upload before closing.',parent=self);return
        if self.dirty and not messagebox.askyesno('Unsaved changes','Close without saving these changes?',parent=self):return
        self.destroy()

if __name__=='__main__':
    App().mainloop()
