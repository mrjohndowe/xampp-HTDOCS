"""Actual tkinter GUI smoke test against disposable server data.
Needs a graphical display. Linux CI: xvfb-run -a python tests/gui_smoke.py
Windows: py tests/gui_smoke.py (briefly opens test windows).
"""
import pathlib,sys,time,traceback
from integration import System
sys.path.insert(0,str(pathlib.Path(__file__).resolve().parents[1]/'desktop'))
import vehicle_tracker as vt

def main():
 System.setUpClass();app=None
 try:
  token=System.session.post(System.base+'api.php?action=token-create',json={'label':'temporary GUI test','days':1}).json()['token']
  settings={'url':System.base.rsplit('/public/',1)[0],'token':token,'theme':'dark'}
  vt.load_settings=lambda:settings;vt.save_settings=lambda s:None
  app=vt.App();errors=[]
  app.report_callback_exception=lambda typ,value,tb:errors.append(''.join(traceback.format_exception(typ,value,tb)))
  start=time.time()
  while app.state is None and time.time()-start<10:app.update();time.sleep(.03)
  assert app.state,'No server connection'
  for _ in range(30):app.update();time.sleep(.03)
  for kind in app.state['forms']:
   e=vt.Editor(app,kind)
   for _ in range(4):app.update();time.sleep(.03)
   d=e.data()
   if kind=='inspections':
    assert not any(i['result'] for i in d['items'].values())
    e.vars['vehicle_id'].set(next(label for label,id in e.maps['vehicle_id'].items() if id==1));e.prefill()
    assert e.vars['odometer'].get()=='120000'
   if kind=='sales':
    e.vars['price'].set('5000');e.vars['deposit'].set('500');assert e.data()['balance']==4500
   e.destroy()
  for i in range(len(app.sections)):
   app.nav.selection_clear(0,'end');app.nav.selection_set(i);app.render();app.update()
  assert not errors,errors
  print('PASS: actual tkinter window startup, authentication, navigation and every shared form, blank checklist and sale balance.')
 finally:
  if app:app.destroy()
  System.tearDownClass()
if __name__=='__main__':main()
