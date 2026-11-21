"""Exercise desktop navigation and queued success/error callbacks without network."""
import importlib.util, pathlib, tempfile, time
from unittest.mock import patch
root=pathlib.Path(__file__).resolve().parents[1]
spec=importlib.util.spec_from_file_location('vehicle_client',root/'desktop/vehicle_tracker.py')
m=importlib.util.module_from_spec(spec); spec.loader.exec_module(m)
class Client:
 def dashboard(self): return {}
 def __getattr__(self,name): return lambda *args,**kwargs: []
with patch.object(m,'load_settings',return_value={'theme':'light'}), patch.object(m,'save_settings'):
 app=m.App(); app.withdraw(); errors=[]
 app.report_callback_exception=lambda *args: errors.append(str(args))
 app.client=Client(); app.connected=True
 for view in ['dashboard','vehicles','trips','engines','expenses','invoices','settings']:
  app.switch_view(view)
  for _ in range(12): app.update(); time.sleep(.01)
  assert app.current_view==view
 app._show_connection(); app._cycle_theme(); app.update()
 received=[]
 def fail(): raise m.ApiError('expected failure')
 app._run_threaded(fail,lambda result: received.append(result),received.append)
 for _ in range(12): app.update(); time.sleep(.01)
 assert received==['expected failure'],received
 assert not errors,errors
 app.destroy()
print('PASS: all seven desktop views, connection theme/refresh, background error delivery')
