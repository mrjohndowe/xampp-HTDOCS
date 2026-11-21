"""Run PHP integration without network access, using an isolated copy."""
import json, pathlib, shutil, subprocess, tempfile, re, os, importlib.util
ROOT=pathlib.Path(__file__).resolve().parents[1]
with tempfile.TemporaryDirectory(prefix='vt-cli-') as tmp:
 app=pathlib.Path(tmp)
 for item in ROOT.iterdir():
  if item.name in {'config.php','config.lock','data','.git','tests'}: continue
  if item.is_dir(): shutil.copytree(item,app/item.name)
  else: shutil.copy2(item,app/item.name)
 (app/'data').mkdir()
 api=app/'api/index.php'; api.write_text(api.read_text().replace("file_get_contents('php://input')", "file_get_contents(__DIR__ . '/../body.json')"))
 (app/'runner.php').write_text('''<?php
error_reporting(E_ALL);
set_error_handler(function($severity,$message,$file,$line) { throw new ErrorException($message,0,$severity,$file,$line); });
$request=json_decode(file_get_contents(__DIR__.'/request.json'),true);
session_save_path(__DIR__.'/data'); session_id('integration-test');
$_SERVER['REQUEST_METHOD']=$request['method']; $_SERVER['REQUEST_URI']=$request['uri'];
$_SERVER['HTTP_AUTHORIZATION']='Bearer '.($request['token'] ?? '');
$_POST=$request['fields'] ?? []; parse_str(parse_url($request['uri'],PHP_URL_QUERY) ?? '',$_GET);
function getallheaders() { return ['Authorization'=>$_SERVER['HTTP_AUTHORIZATION']]; }
register_shutdown_function(function() { file_put_contents(__DIR__.'/status.txt',(string)(http_response_code() ?: 200)); });
require __DIR__.'/'.$request['file'];
''')
 def request(file,uri='/',fields=None,body=None,token='',method=None):
  (app/'request.json').write_text(json.dumps(dict(file=file,uri=uri,fields=fields or {},token=token,method=method or ('POST' if fields is not None or body is not None else 'GET'))))
  (app/'body.json').write_text(json.dumps(body or {}))
  p=subprocess.run([shutil.which('php'),'-d','display_errors=1',str(app/'runner.php')],capture_output=True,text=True)
  assert p.returncode==0,p.stdout+p.stderr
  assert 'Fatal error' not in p.stdout,p.stdout
  return p.stdout,int((app/'status.txt').read_text())
 def api(path,body=None,token='',method=None):
  text,status=request('api/index.php','/api/'+path,body=body,token=token,method=method)
  assert status<400,(status,text)
  return json.loads(text)
 request('install/index.php','/install/',dict(action='choose_db',driver='sqlite',database=str(app/'data/test.sqlite')))
 request('install/index.php','/install/',dict(action='create_admin',username='admin',password='password123',confirm='password123'))
 request('install/index.php','/install/',dict(action='set_prefs',site_name='Fleet',currency='USD',distance_unit='mi'))
 request('install/index.php','/install/',dict(action='finish',make='Dodge',model='Dart',year=2013,initial_odometer=120000,engine_miles_at_install=25000))
 assert (app/'config.lock').is_file()
 assert request('install/index.php','/install/')[1]==403
 for name in ['index','vehicles','trips','engines','expenses','invoices','settings']:
  text,status=request(name+'.php'); assert '</html>' in text and status==200,name
 for name in ['vehicles','engines']:
  assert '</html>' in request(name+'.php','/'+name+'.php?edit=1')[0]
 assert api('status')['installed']
 token=api('login',dict(username='admin',password='password123'))['token']
 api('trips',dict(vehicle_id=1,trip_date='2026-11-17',start_odometer=120000,end_odometer=120300,purpose='test'),token)
 engines=api('engines',token=token)['engines']; assert engines[0]['engine_miles']==25300,engines
 page=request('invoices.php')[0]; csrf=re.search(r'name="csrf_token" value="([^"]+)"',page)[1]
 request('invoices.php',fields=dict(action='create_mechanic',csrf_token=csrf,name='Mechanic'))
 request('invoices.php',fields=dict(action='create_invoice',csrf_token=csrf,mechanic_id=1,invoice_number='TEST',invoice_date='2026-11-17',due_date='2026-12-01',amount=600))
 api('expenses',dict(vehicle_id=1,expense_date='2026-11-17',category='repairs',amount=600,invoice_id=1),token)
 result=api('invoices/1/payments',dict(payment_date='2026-11-17',amount=200),token); assert result['balance']==400,result
 assert '$400.00' in request('invoices.php')[0]
 assert '$600.00' in request('expenses.php')[0]
 assert request('download.php','/download.php?file=vehicle_tracker.py')[0]==(ROOT/'desktop/vehicle_tracker.py').read_text()
 print('PASS: onboarding, persistent lock, nine page renders, status/login, 300-mile trip, 25,300 engine miles, website invoice, API payment, $400 debt, $600 expense, maintained download')
