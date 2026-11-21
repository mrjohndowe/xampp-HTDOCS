"""Disposable end-to-end PHP/SQLite tests. Never point these at a real installation.
Run: python -m pip install requests && python tests/integration.py
Needs php with pdo_sqlite on PATH. Optional VT_TEST_DATABASE=mysql and VT_TEST_MYSQL_HOST/NAME/USER/PASSWORD.
Use a NEW EMPTY disposable MySQL database; never use a real installation.
"""
import datetime, json, os, pathlib, re, shutil, socket, subprocess, tempfile, time, unittest, uuid
import requests
ROOT=pathlib.Path(__file__).resolve().parents[1]
class System(unittest.TestCase):
 @classmethod
 def setUpClass(cls):
  cls.temp=tempfile.TemporaryDirectory();cls.private=pathlib.Path(cls.temp.name)
  sock=socket.socket();sock.bind(('127.0.0.1',0));cls.port=sock.getsockname()[1];sock.close()
  cls.base=f'http://127.0.0.1:{cls.port}/public/';cls.session=requests.Session()
  cls.process=subprocess.Popen(['php','-S',f'127.0.0.1:{cls.port}','-t',str(ROOT)],env={**os.environ,'VT_PRIVATE':str(cls.private)},stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
  for _ in range(50):
   try:
    r=cls.session.get(cls.base+'setup.php',timeout=1)
    if r.status_code==200:break
   except requests.RequestException:time.sleep(.1)
  csrf=re.search(r'name="csrf" value="([^"]+)"',r.text)[1]
  values=dict(csrf=csrf,database='sqlite',operation='install',site_name='Test Garage',username='test-admin',password=uuid.uuid4().hex,currency='USD',unit='mi',make='Dodge',model='Dart',year='2015',odometer='120000',engine_miles='25000',baseline_date='2026-01-01')
  
  if os.environ.get('VT_TEST_DATABASE')=='mysql':values.update(database='mysql',db_host=os.environ.get('VT_TEST_MYSQL_HOST','127.0.0.1'),db_name=os.environ.get('VT_TEST_MYSQL_NAME','vt_test'),db_user=os.environ.get('VT_TEST_MYSQL_USER','vt_test'),db_password=os.environ.get('VT_TEST_MYSQL_PASSWORD',''))
  cls.password=values['password'];r=cls.session.post(cls.base+'setup.php',data=values);assert 'Your garage, connected.' not in r.text,r.text
  r=cls.session.post(cls.base+'api.php?action=login',json={'username':'test-admin','password':cls.password});assert r.status_code==200,r.text
  cls.csrf=r.json()['csrf'];cls.session.headers['X-CSRF-Token']=cls.csrf
 @classmethod
 def tearDownClass(cls):cls.process.terminate();cls.process.wait();cls.temp.cleanup()
 def api(self,action,data=None,expected=200,key=None):
  url=self.base+'api.php?action='+action
  r=self.session.get(url) if data is None else self.session.post(url,json=data,headers={'Idempotency-Key':key or uuid.uuid4().hex})
  self.assertEqual(r.status_code,expected,r.text);return r.json()
 def save(self,kind,data,old=None,expected=200,key=None):return self.api('save',{'kind':kind,'data':data,'id':old['id'] if old else 0,'version':old['version'] if old else 0},expected,key)
 def test_01_setup_lock_and_auth(self):
  self.assertTrue((self.private/'config.lock').exists())
  self.assertNotIn('password',(self.private/'config.lock').read_text().lower())
  self.assertEqual(self.session.get(self.base+'setup.php').status_code,409)
  self.assertEqual(self.session.post(self.base+'setup.php',data={'operation':'install'}).status_code,409)
  r=requests.get(self.base+'api.php?action=state');self.assertEqual(r.status_code,401)
  token=self.api('token-create',{'label':'test','days':1})['token'];r=requests.get(self.base+'api.php?action=state',headers={'Authorization':'Bearer '+token});self.assertEqual(r.status_code,200)
  r=requests.get(self.base+'api.php?action=state',headers={'Authorization':'Bearer invalid'});self.assertEqual(r.status_code,401)
  self.assertNotIn(token,(self.private/'config.lock').read_text())
  (self.private/'config.lock').unlink();self.assertEqual(self.session.get(self.base+'setup.php').status_code,409);(self.private/'config.lock').write_text('Recovered test installation')
 def test_02_calculations(self):
  driver=self.save('drivers',{'name':'Test Driver','active':True,'expires':'2020-01-01'})
  key=uuid.uuid4().hex;trip_data={'vehicle_id':1,'driver_id':driver['id'],'date':'2026-01-02','start':120000,'end':120300,'purpose':'Test'}
  trip=self.save('trips',trip_data,key=key);again=self.save('trips',trip_data,key=key);self.assertEqual(trip['id'],again['id'])
  s=self.api('state');self.assertEqual(s['stats']['distance'],300);self.assertEqual(s['stats']['vehicles']['1']['engine_miles'],25300)
  self.assertEqual(s['stats']['drivers'][str(driver['id'])]['distance'],300)
  self.save('drivers',{**driver['data'],'active':False},driver)
  self.save('driver_records',{'driver_id':driver['id'],'date':'2026-01-02','type':'Training','notes':'Completed'})
  m=self.save('mechanics',{'name':'Test Shop'})
  invoice=self.save('invoices',{'vehicle_id':1,'mechanic_id':m['id'],'number':'TEST-1','date':'2026-01-02','due_date':'2026-01-31','amount':600,'description':'Repair'})
  self.save('payments',{'invoice_id':invoice['id'],'date':'2026-01-03','amount':200,'method':'Card'})
  s=self.api('state');self.assertEqual(s['stats']['expenses'],600);self.assertEqual(s['stats']['debt'],400);linked=[r for r in s['records'] if r['kind']=='expenses' and r['data'].get('invoice_id')==invoice['id']];self.assertEqual(len(linked),1);self.assertEqual(linked[0]['data']['amount'],600)
  self.save('payments',{'invoice_id':invoice['id'],'date':'2026-01-04','amount':401},expected=422)
  base={'vehicle_id':1,'category':'Fuel','unit_price':3.4,'amount':999,'gallons':5}
  first=self.save('expenses',{**base,'date':'2026-01-03','odometer':120300,'full':True})
  partial=self.save('expenses',{**base,'date':'2026-01-04','odometer':120450,'full':False})
  full=self.save('expenses',{**base,'date':'2026-01-05','odometer':120600,'full':True})
  self.assertEqual(full['data']['amount'],17)
  stats=self.api('state')['stats'];self.assertIsNone(stats['fuel'][str(first['id'])]);self.assertIsNone(stats['fuel'][str(partial['id'])]);self.assertEqual(stats['fuel'][str(full['id'])],30)
  for changes in [{'gallons':0},{'gallons':-1},{'gallons':'NaN'},{'unit_price':'1e999'},{'date':'2026-02-30'},{'odometer':120200}]:
   self.save('expenses',{**base,'date':'2026-01-06','odometer':120700,**changes},expected=422)
  edited=self.save('expenses',{**partial['data'],'gallons':10},partial);self.assertEqual(self.api('state')['stats']['fuel'][str(full['id'])],20)
  self.api('delete',{'id':edited['id'],'version':edited['version']});self.assertEqual(self.api('state')['stats']['fuel'][str(full['id'])],60)
  self.save('trips',{**trip_data,'start':-1},expected=422)
  self.save('trips',{**trip_data,'start':120100,'end':120200},expected=422)
  self.save('engines',{'vehicle_id':1,'date':'2026-01-06','odometer':120600,'engine_miles':10000,'serial':'Replacement'})
  self.save('trips',{'vehicle_id':1,'date':'2026-01-07','start':120600,'end':120700})
  hist=self.api('state')['stats']['vehicles']['1'];self.assertEqual(hist['engine_miles'],10100);self.assertEqual(hist['engines'][0]['current_miles'],25600)
 def test_03_inspection(self):
  draft=self.save('inspections',{'status':'Draft','vehicle_id':1,'inspector':'Inspector','date':'2026-01-08','time':'10:30','unit':'mi','odometer':120700,'items':{}})
  self.assertEqual(draft['data']['week'],'2026-01-05');self.assertEqual(draft['data']['next_due'],'2026-01-12')
  self.save('inspections',{**draft['data'],'status':'Completed','acknowledged':True},draft,422)
  checklist=self.api('state')['checklist'];items={k:{'result':'Pass','notes':''} for k in checklist};items['tires']={'result':'Needs attention','notes':''}
  self.save('inspections',{**draft['data'],'status':'Completed','acknowledged':True,'items':items},draft,422)
  items['tires']['notes']='Low pressure'
  complete=self.save('inspections',{**draft['data'],'status':'Completed','acknowledged':True,'items':items},draft)
  self.save('inspections',complete['data'],complete,422)
  issues=[r for r in self.api('state')['records'] if r['kind']=='issues'];self.assertEqual(len(issues),1)
  issue=issues[0];self.save('issues',{**issue['data'],'status':'Resolved','action':'Inflated','responsible':'Inspector','resolved_date':'2026-01-09'},issue)
  original=[r for r in self.api('state')['records'] if r['id']==complete['id']][0];self.assertEqual(original['data']['items']['tires']['result'],'Needs attention')
  revision=self.api('revise',{'id':complete['id'],'reason':'Correct notes'});self.assertEqual(revision['data']['status'],'Draft');self.assertEqual(revision['data']['revises'],complete['id'])
 def test_04_sale_snapshot(self):
  draft=self.save('sales',{'status':'Draft','buyer_name':'Buyer','seller_name':'Seller','price':5000,'deposit':500})
  self.assertEqual(draft['data']['balance'],4500)
  self.save('sales',{**draft['data'],'status':'Finalized'},draft,422)
  fields=dict(buyer_address='Buyer address',seller_address='Seller address',date='2026-01-10',time='12:00',location='Colorado',year='2015',make='Dodge',model='Dart',vin='Test VIN',odometer=120700,unit='mi',disclosure='Actual mileage',currency='USD',payment_method='Cash',payment_status='Deposit received',payment_terms='Balance on delivery',condition='Used',defects='Reviewed: none declared',accessories='Keys',title_liens='Reviewed: seller disclosure',delivery='2026-01-10',warranty='Agreed terms',additional_terms='None',jurisdiction='General / Colorado',reviewed=True)
  final=self.save('sales',{**draft['data'],**fields,'status':'Finalized'},draft)
  self.save('sales',{**final['data'],'price':4000},final,422)
  self.api('mark-signed',{'id':final['id'],'version':final['version'],'confirmed':False,'buyer_date':'2026-01-10','seller_date':'2026-01-10'},expected=422)
  signed=self.api('mark-signed',{'id':final['id'],'version':final['version'],'confirmed':True,'buyer_date':'2026-01-10','seller_date':'2026-01-10'});self.assertEqual(signed['data']['status'],'Signed');self.assertEqual(signed['data']['price'],5000)
  rev=self.api('revise',{'id':final['id'],'reason':'Correct address'});self.assertEqual(rev['data']['status'],'Draft')
  self.assertEqual(self.api('state')['stats']['debt'],400)
 def test_05_csrf_and_stale_version(self):
  r=self.session.post(self.base+'api.php?action=save',json={},headers={'X-CSRF-Token':'invalid'});self.assertEqual(r.status_code,403)
  vehicle=[r for r in self.api('state')['records'] if r['kind']=='vehicles'][0]
  self.save('vehicles',{**vehicle['data'],'notes':'Updated'},vehicle)
  self.save('vehicles',{**vehicle['data'],'notes':'Stale update'},vehicle,422)
 def test_06_photo_validation_and_retry(self):
  import base64
  png=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l1sAAAAASUVORK5CYII=')
  # Generate a canonical valid PNG via GD, avoiding decoder-dependent sample CRCs.
  png=subprocess.check_output(['php','-r','$im=imagecreatetruecolor(2,2);imagepng($im);'])
  key=uuid.uuid4().hex;url=self.base+'api.php?action=upload'
  args=dict(data={'record_id':'1','caption':'Test cover','view_label':'front'},files={'photo':('photo.png',png,'image/png')},headers={'Idempotency-Key':key})
  r=self.session.post(url,**args);self.assertEqual(r.status_code,200,r.text);id=r.json()['id']
  again=self.session.post(url,**args);self.assertEqual(id,again.json()['id'])
  image=self.session.get(self.base+'api.php?action=image&id='+str(id));self.assertEqual(image.headers['Content-Type'],'image/png');self.assertTrue(image.content.startswith(b'\x89PNG'))
  unauth=requests.get(self.base+'api.php?action=image&id='+str(id));self.assertEqual(unauth.status_code,401)
  bad=self.session.post(url,data={'record_id':'1'},files={'photo':('bad.jpg',b'not an image','image/jpeg')},headers={'Idempotency-Key':uuid.uuid4().hex});self.assertEqual(bad.status_code,422)
 def test_07_versioned_migration(self):
  env={**os.environ,'VT_PRIVATE':str(self.private)}
  fixture="require 'app/core.php';$p=db();sql($p,'UPDATE meta SET value=? WHERE name=?',['1','schema_version']);$n=gmdate('c');sql($p,'INSERT INTO records(kind,payload,created,updated) VALUES(?,?,?,?)',['trips',json_encode(['vehicle_id'=>1,'date'=>'2025-01-01','start'=>100,'end'=>200,'notes'=>'legacy preserved']),$n,$n]);sql($p,'INSERT INTO records(kind,payload,created,updated) VALUES(?,?,?,?)',['expenses',json_encode(['vehicle_id'=>1,'date'=>'2025-01-02','category'=>'Fuel','amount'=>42.50]),$n,$n]);"
  subprocess.run(['php','-r',fixture],cwd=ROOT,env=env,check=True)
  before=self.api('state');lock=(self.private/'config.lock').read_text()
  result=subprocess.run(['php','tools/maintenance.php','migrate','--backup-confirmed'],cwd=ROOT,env=env,capture_output=True,text=True);self.assertEqual(result.returncode,0,result.stderr)
  after=self.api('state');self.assertEqual(len(before['records']),len(after['records']));self.assertEqual(lock,(self.private/'config.lock').read_text())
  legacy=next(r for r in after['records'] if r['data'].get('notes')=='legacy preserved');self.assertIsNone(legacy['data']['driver_id']);self.assertEqual(legacy['data']['end'],200)
  fuel=next(r for r in after['records'] if r['data'].get('fuel_details_incomplete'));self.assertEqual(fuel['data']['amount'],42.5);self.assertIsNone(after['stats']['fuel'][str(fuel['id'])])
 def test_08_installer_lock_after_server_restart(self):
  cls=type(self);cls.process.terminate();cls.process.wait()
  cls.process=subprocess.Popen(['php','-S',f'127.0.0.1:{cls.port}','-t',str(ROOT)],env={**os.environ,'VT_PRIVATE':str(cls.private)},stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
  for _ in range(50):
   try:
    r=self.session.get(self.base+'setup.php',timeout=1)
    if r.status_code==409:break
   except requests.RequestException:time.sleep(.1)
  self.assertEqual(r.status_code,409)
  self.assertEqual(self.session.post(self.base+'setup.php',data={'operation':'install'}).status_code,409)
if __name__=='__main__':unittest.main(verbosity=2)
