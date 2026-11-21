#!/usr/bin/env python3
"""End-to-end PHP HTTP tests using the actual desktop client. No third-party services needed."""
import base64
from concurrent.futures import ThreadPoolExecutor
from contextlib import contextmanager
from datetime import date, datetime, timedelta, timezone
from decimal import Decimal
from html import unescape
from http.cookiejar import CookieJar
import importlib.util
import json
import os
from pathlib import Path
import re
import shutil
import socket
import subprocess
import tempfile
import time
from urllib.error import HTTPError, URLError
from urllib.parse import urlencode
from urllib.request import Request, HTTPCookieProcessor, build_opener
import uuid

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('motorlog_desktop_app', ROOT / 'desktop' / 'motorlog.py')
desktop = importlib.util.module_from_spec(spec)
spec.loader.exec_module(desktop)
PHPCMD = os.getenv('MOTORLOG_TEST_PHP', 'php')


def stamp(offset=0):
    return (datetime.now(timezone.utc).date() + timedelta(days=offset)).isoformat()


def port():
    with socket.socket() as s:
        s.bind(('127.0.0.1', 0))
        return s.getsockname()[1]


@contextmanager
def server(private, listen, log):
    env = os.environ | {'MOTORLOG_DATA_DIR': str(private), 'MOTORLOG_DOCUMENT_ROOT': str(ROOT), 'MOTORLOG_DEMO': '0'}
    env.pop('PHP_CLI_SERVER_WORKERS', None)
    with log.open('a', encoding='utf-8') as fd:
        process = subprocess.Popen([PHPCMD, '-S', f'127.0.0.1:{listen}', '-t', str(ROOT), str(ROOT/'router.php')], env=env, stdout=fd, stderr=fd)
        url = f'http://127.0.0.1:{listen}/'
        try:
            for _ in range(65):
                try:
                    if Browser(url).request('ping')[0] == 200:
                        break
                except (OSError, URLError):
                    time.sleep(.07)
            else:
                raise AssertionError(log.read_text(encoding='utf-8'))
            yield url
        finally:
            process.terminate()
            try: process.wait(timeout=3)
            except subprocess.TimeoutExpired:
                process.kill(); process.wait(timeout=3)


class Browser:
    def __init__(self, base):
        self.base = base
        self.csrf = ''
        self.opener = build_opener(HTTPCookieProcessor(CookieJar()))

    def request(self, route='', data=None, query=None, key=None, raw=False, csrf=True):
        url = self.base + (('?' + urlencode({'r': route, **(query or {})})) if route else '')
        headers = {}
        if data is not None:
            if csrf: headers['X-CSRF-Token'] = self.csrf
            headers['Content-Type'] = 'application/json'
            if key: headers['Idempotency-Key'] = key
            data = json.dumps(data, separators=(',', ':')).encode()
        try:
            with self.opener.open(Request(url, data=data, headers=headers), timeout=10) as r:
                out, status, content_type = r.read(), r.status, r.headers['Content-Type']
        except HTTPError as r:
            out, status, content_type = r.read(), r.code, r.headers['Content-Type']
        if raw: return status, out
        if content_type and 'application/json' in content_type: return status, json.loads(out)
        return status, out.decode('utf-8')

    def bootstrap(self):
        status, html = self.request()
        assert status == 200, html
        boot = json.loads(re.search(r'<script type="application/json" id="boot">(.*?)</script>', html, re.S).group(1))
        self.csrf = boot['csrf']
        return boot

    def write(self, entity, action, data, key=None, status=200):
        code, r = self.request('save', {'entity':entity,'action':action,'data':data}, key=key or uuid.uuid4().hex)
        assert code == status, (entity, action, code, r)
        return r

    def get(self, route, query=None):
        code, r = self.request(route, query=query)
        assert code == 200, (route, code, r)
        return r


def must_error(fn,code=None):
    try: fn()
    except desktop.ApiError as e:
        if code is not None: assert e.code == code, (e.code, code, str(e))
        return str(e)
    raise AssertionError('Expected desktop API error')


def assert_catalog(browser):
    car=browser.get('catalog',{'type':'car'}); moto=browser.get('catalog',{'type':'motorcycle'})
    dod=next(x for x in car['makes'] if x['name']=='DODGE'); ford=next(x for x in car['makes'] if x['name']=='FORD')
    assert len(car['makes'])==car['meta']['counts']['car']['makes'] and len(moto['makes'])==car['meta']['counts']['motorcycle']['makes']
    dodge=browser.get('catalog',{'type':'car','make':dod['id']})['models']; ford_models=browser.get('catalog',{'type':'car','make':ford['id']})['models']
    assert any(x['name']=='Dart' for x in dodge) and not any(x['name']=='Dart' for x in ford_models)
    assert all(x['type']=='car' for x in ford_models) and all(x['name'] not in ('F-150','F-350','F-650') for x in ford_models)
    sample=next(x for x in moto['makes'] if x['name']=='YAMAHA')
    assert {x['type'] for x in browser.get('catalog',{'type':'motorcycle','make':sample['id']})['models']}=={'motorcycle'}
    snap=json.loads((ROOT/'catalog'/'vpic.json').read_text(encoding='utf-8'))
    pairs={(m['id'],x[0]) for m in snap['makes'] for t,models in m['types'].items() for x in models if t in ('car','motorcycle')}
    assert len(pairs)==snap['counts']['models'] and len(snap['makes'])==snap['counts']['makes']
    assert all(set(m['types'])<= {'car','motorcycle'} for m in snap['makes'])


def per_backend(driver, mysql):
    with tempfile.TemporaryDirectory(prefix='motorlog-http-') as tmp:
        path=Path(tmp);private=path/'private';listen=port();log=path/'server.log'
        try:
            with server(private,listen,log) as url:
                browser=Browser(url)
                assert browser.bootstrap()['mode']=='setup'
                assert 'onboarding is incomplete' in must_error(lambda:desktop.Client(url,'invalid').connect(),'setup_required')
                assert_catalog(browser)
                cfg={'driver':driver, **mysql}
                assert browser.request('install',{'action':'test',**cfg},csrf=False)[0]==403
                if driver=='mysql':assert browser.request('install',{'action':'test',**cfg,'db_password':'deliberately-wrong'})[0]==422
                assert browser.request('install',{'action':'test',**cfg})[0]==200
                data=cfg|{'site_name':'Acceptance Fleet','currency':'USD','unit':'mi','username':'judge','display_name':'Test Judge','password':'Test-only password 390!',
                          'vehicle':{'kind':'car','make':'Custom Works','model':'Example Sedan','model_year':2014,'vin':'ACCEPTANCEVIN01','plate':'TEST42','initial_odometer':'120000','at_odometer':'120000','at_engine':'25000'}}
                # Deliberate validation failure must not publish setup or block the retry.
                assert browser.request('install',data|{'password':'short'})[0]==422
                assert not (private/'config.lock').exists() and not (private/'config.php').exists()
                assert browser.request('install',data)[0]==201
                assert browser.bootstrap()['mode']=='app'; assert browser.request('install')[0]==423 and browser.request('install',data)[0]==423
                locked=json.loads((private/'config.lock').read_text()); assert set(locked)=={'format','installed_at'}
                assert browser.get('state')['vehicles'][0]['make']=='Custom Works'
                assert Browser(url).request('state')[0]==401
                must_error(lambda:desktop.Client(url,'ml_'+'1'*64).connect(),'invalid_token')
                token=browser.write('token','create',{'label':'Integration desktop','days':7})
                assert token['token'] not in (private/'config.php').read_text() and token['token'] not in (private/'config.lock').read_text()
                client=desktop.Client(url,token['token']);s=client.connect(); vid=s['vehicles'][0]['id']
                did=browser.write('driver','create',{'name':'Emilia Driver','expiration_date':stamp(-2),'jurisdiction':'Test jurisdiction'})['id']
                client.save('driver','record',{'id':did,'record_date':stamp(-9),'kind':'Training','title':'Vehicle familiarization'},uuid.uuid4().hex)
                t={'vehicle_id':vid,'driver_id':did,'trip_date':stamp(-8),'start_odometer':'120000','end_odometer':'120300','purpose':'Acceptance trip'}
                trip=client.save('trip','create',t,uuid.uuid4().hex);assert trip['distance']==300
                current=browser.get('state'); assert current['vehicles'][0]['engine_odometer']==25300
                assert current['drivers'][0]['trip_count']==1 and current['drivers'][0]['distance']==300
                assert current['drivers'][0]['records'][0]['kind']=='Training' and current['drivers'][0]['license_expired']
                assert b'Emilia Driver' in browser.request('trip-csv',query={'driver_id':did},raw=True)[1]
                assert 'conflict' in must_error(lambda:client.save('trip','create',t|{'trip_date':stamp(-7),'start_odometer':'120100'},uuid.uuid4().hex),'odometer_conflict').lower()
                assert browser.write('driver','archive',{'id':did})['id']==did
                assert browser.get('state')['trips'][0]['driver_name']=='Emilia Driver'
                new_driver=browser.write('driver','create',{'name':'Lee Inspector'})['id']
                mid=browser.write('mechanic','create',{'name':'Test Garage'})['id']
                inv=browser.write('invoice','create',{'vehicle_id':vid,'mechanic_id':mid,'invoice_number':'INV-600','invoice_date':stamp(-9),'due_date':stamp(-2),'amount':'600.00','description':'Repair'})['id']
                pay={'id':inv,'payment_date':stamp(-5),'amount':'200.00','method':'Card','notes':'Recorded in Python'};key=uuid.uuid4().hex
                assert client.save('invoice','pay',pay,key)['balance']=='400.00'
                assert client.save('invoice','pay',pay,key)['balance']=='400.00'
                must_error(lambda:client.save('invoice','pay',pay|{'amount':'199.00'},key),'idempotency_conflict')
                must_error(lambda:client.save('invoice','pay',pay|{'amount':'401.00'},uuid.uuid4().hex))
                s=browser.get('state'); assert s['totals']['expenses']=='600.00' and s['totals']['debt']=='400.00' and len(s['invoices'][0]['payments'])==1
                assert s['invoices'][0]['overdue'] and len(s['expenses'])==1
                def fuel(data,key=None):return client.save('expense','create',data,key or uuid.uuid4().hex)
                f={'vehicle_id':vid,'category':'Fuel','price_per_gallon':'3.40','gallons':'5','full_tank':True,'amount':'0.01'}
                first=fuel(f|{'expense_date':stamp(-8),'odometer':'120300'});part_data=f|{'full_tank':False,'expense_date':stamp(-7),'odometer':'120450'};part=fuel(part_data)
                final=fuel(f|{'expense_date':stamp(-6),'odometer':'120600'});assert final['amount']=='17.00'
                mpg={r['id']:r for r in browser.get('state')['expenses']}; assert mpg[final['id']]['mpg']==30 and mpg[first['id']]['mpg'] is None and mpg[part['id']]['mpg'] is None
                for item in [{'gallons':'0'},{'gallons':'-1'},{'gallons':'Infinity'},{'price_per_gallon':'oops'},{'expense_date':'2026-02-30'}]:
                    must_error(lambda item=item:fuel(f|{'expense_date':stamp(-5),'odometer':'120610'}|item))
                must_error(lambda:fuel(f|{'expense_date':stamp(-5),'odometer':'120540'}),'odometer_conflict')
                client.save('expense','update',part_data|{'id':part['id'],'gallons':'7.5'},uuid.uuid4().hex)
                assert next(r['mpg'] for r in browser.get('state')['expenses'] if r['id']==final['id'])==24
                client.save('expense','delete',{'id':part['id']},uuid.uuid4().hex)
                assert next(r['mpg'] for r in browser.get('state')['expenses'] if r['id']==final['id'])==60
                opts=s['options'];i={'vehicle_id':vid,'driver_id':new_driver,'inspector_name':'Lee Inspector','inspection_date':stamp(),'inspection_time':'09:30','odometer':'120600','unit':'mi','model_year':2014,'make':'Custom Works','model':'Example Sedan','vin':'ACCEPTANCEVIN01'}
                draft=browser.write('inspection','draft',i|{'items':{'tires':{'result':'attention','notes':''}}})
                reopened=client.request('document',{'id':draft['id']})['document']; assert reopened['status']=='draft' and reopened['result_count']==1
                base=i|{'id':draft['id'],'revision':draft['revision'],'acknowledged':True,'ack_name':'Lee Inspector'}
                must_error(lambda:client.save('inspection','submit',base,uuid.uuid4().hex));assert browser.get('document',{'id':draft['id']})['document']['revision']==1
                checks={k:{'result':'pass','notes':''} for k in opts['checklist']};checks['tires']['result']='attention'
                assert 'explanatory notes' in must_error(lambda:client.save('inspection','submit',base|{'items':checks},uuid.uuid4().hex))
                img=base64.b64encode((ROOT/'assets'/'civic.jpg').read_bytes()).decode()
                photo=client.save('photo','upload',{'owner_kind':'inspection','owner_id':draft['id'],'item_key':'tires','caption':'Tire evidence','image':img},uuid.uuid4().hex)
                assert Browser(url).request('image',query={'id':photo['id']},raw=True)[0]==401
                assert client.request('image',{'id':photo['id']},raw=True).startswith(b'\xff\xd8')
                checks['tires']['notes']='Tire pressure below the recommendation.'
                completed=client.save('inspection','submit',base|{'items':checks},uuid.uuid4().hex);doc=browser.get('document',{'id':completed['id']})['document']
                today=date.fromisoformat(stamp());week=today-timedelta(days=today.weekday())
                assert (doc['week_start'],doc['due_next'])==(week.isoformat(),(week+timedelta(days=7)).isoformat())
                assert doc['unresolved']==1 and doc['issue_count']==1 and doc['result_count']==len(opts['checklist'])
                assert doc['data']['ack_at'] and doc['data']['ack_name']=='Lee Inspector' and doc['photos'][0]['caption']=='Tire evidence'
                issue=doc['issues'][0]['id'];browser.write('issue','update',{'id':issue,'status':'resolved','action_taken':'Inflated to spec','responsible':'Lee Inspector','resolved_date':stamp()})
                doc=browser.get('document',{'id':completed['id']})['document'];assert doc['data']['items']['tires']['result']=='attention' and doc['unresolved']==0 and doc['issue_count']==1 and any(x['action']=='issue_followup' for x in doc['audit'])
                html=browser.get('print',{'type':'inspection','id':completed['id']});assert 'Handwritten signatures' in html and 'Tire pressure' in html and 'Main navigation' not in html
                pdf=client.request('pdf',{'type':'inspection','id':completed['id']},raw=True);assert pdf.startswith(b'%PDF') and b'Tire pressure' in pdf and b'/Subtype /Image' in pdf
                fixed=browser.write('inspection','revise',{'id':completed['id']});assert fixed['id']!=completed['id'];assert client.request('document',{'id':fixed['id']})['document']['photos']
                cover=client.save('photo','upload',{'owner_kind':'vehicle','owner_id':vid,'view_label':'Front','caption':'Cover from Python','cover':True,'image':img},uuid.uuid4().hex)
                assert browser.get('state')['vehicles'][0]['cover_id']==cover['id']
                sale={'vehicle_id':vid,'model_year':2014,'make':'Custom Works','model':'Example Sedan','vin':'ACCEPTANCEVIN01','plate':'TEST42','odometer':'120600','unit':'mi','sale_date':stamp(),'sale_time':'12:00','sale_location':'Test City','disclosure':opts['disclosures'][0],
                      'buyer_name':'Example Buyer','seller_name':'Example Seller','buyer_address':'500 Buyer St','seller_address':'100 Seller St','currency':'USD','price':'5000.00','deposit':'500.00','payment_method':'Bank transfer','payment_status':'Deposit received','title_status':opts['titles'][0],'warranty':opts['warranties'][0]}
                draft=browser.write('sale','draft',{'price':'5000','deposit':'500'});assert draft['balance']=='4500.00'
                client.request('document',{'id':draft['id']});must_error(lambda:client.save('sale','finalize',{'id':draft['id'],'revision':draft['revision']},uuid.uuid4().hex))
                changed=client.save('sale','draft',sale|{'id':draft['id'],'revision':draft['revision']},uuid.uuid4().hex)
                stale=browser.write('sale','draft',sale|{'id':draft['id'],'revision':draft['revision']},status=409);assert stale['code']=='revision_conflict'
                final_sale=browser.write('sale','finalize',sale|{'id':draft['id'],'revision':changed['revision'],'terms_reviewed':True});assert final_sale['balance']=='4500.00'
                browser.write('vehicle','update',{'id':vid,'kind':'car','make':'Changed Profile','model':'New Name','model_year':2014})
                new=client.request('document',{'id':draft['id']})['document'];assert new['data']['make']=='Custom Works' and new['data']['buyer_name']=='Example Buyer'
                assert b'Seller signature' in client.request('pdf',{'type':'sale','id':draft['id']},raw=True)
                signed=client.save('sale','signed',{'id':draft['id'],'revision':new['revision'],'buyer_printed':'Example Buyer','seller_printed':'Example Seller','buyer_sign_date':stamp(),'seller_sign_date':stamp(),'confirmed':True},uuid.uuid4().hex)
                revision=client.save('sale','revise',{'id':draft['id']},uuid.uuid4().hex);assert revision['id']!=signed['id'] and client.request('document',{'id':draft['id']})['document']['status']=='signed'
                assert browser.get('updates')['state']=='unconfigured'
                assert browser.request('save',{'entity':'settings','action':'update','data':{'site_name':'x'}},csrf=False,key=uuid.uuid4().hex)[0]==403
                assert Browser(url).opener.open(url+'assets/app.js').status==200
                assert b'class Application' in browser.request('download',raw=True)[1]
                for denied in ['lib/core.php','router.php','catalog/vpic.json','desktop/motorlog.py','config.php','motorlog.sqlite']:
                    try: Browser(url).opener.open(url+denied)
                    except HTTPError as e: assert e.code in (403,404)
                    else:raise AssertionError(denied)
            with server(private,listen,log) as url:
                browser.base=url; assert browser.bootstrap()['mode']=='app'
                assert browser.request('install')[0]==423 and browser.request('install',data)[0]==423
                state=client.connect();assert state['trips'][0]['driver_name']=='Emilia Driver'
                assert state['vehicles'][0]['current_odometer']==120600 and state['vehicles'][0]['engine_odometer']==25600
                (private/'config.lock').unlink()
                assert browser.request('install')[0]==423 and browser.request('install',data)[0]==423
                if driver=='mysql':
                    (private/'config.php').rename(private/'config.keep'); assert browser.request('install',{'action':'test',**cfg})[0]==409
                    (private/'config.keep').rename(private/'config.php')
                env=os.environ|{'MOTORLOG_DATA_DIR':str(private),'MOTORLOG_DOCUMENT_ROOT':str(ROOT)}
                subprocess.run([PHPCMD,str(ROOT/'tools'/'console.php'),'recover-lock','--backed-up'],env=env,check=True)
                assert client.connect()['totals']['debt']=='400.00'
                browser.write('token','revoke',{'id':token['id']});must_error(client.connect,'invalid_token')
        except Exception:
            if log.is_file():print(log.read_text(encoding='utf-8')[-3800:])
            raise
    print(f'PASS: {driver} onboarding, API/desktop sync, fuel/engine/invoice accuracy, drivers, inspections/photos, sale revisions, PDF, locks and access controls')


if __name__ == '__main__':
    per_backend('sqlite',{})
    if os.getenv('MOTORLOG_TEST_MYSQL_DATABASE'):
        per_backend('mysql',{'host':os.getenv('MOTORLOG_TEST_MYSQL_HOST','127.0.0.1'),'port':os.getenv('MOTORLOG_TEST_MYSQL_PORT','3306'),
                            'database':os.environ['MOTORLOG_TEST_MYSQL_DATABASE'],'db_user':os.getenv('MOTORLOG_TEST_MYSQL_USER','root'),'db_password':os.getenv('MOTORLOG_TEST_MYSQL_PASSWORD','')})
    else:
        print('MySQL/MariaDB optional: set MOTORLOG_TEST_MYSQL_DATABASE and connection variables for an EMPTY dedicated test database.')
