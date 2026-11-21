#!/usr/bin/env python3
"""Optional GUI smoke run with a display (e.g. xvfb-run -a python3 tests/desktop_smoke.py)."""
from pathlib import Path
import tempfile
import time
import traceback
import tkinter as tk

from integration import server, port, Browser, stamp, desktop


def run():
    with tempfile.TemporaryDirectory(prefix='motorlog-gui-') as name:
        folder=Path(name)
        desktop.PREF_DIR=folder/'prefs';desktop.PREF_FILE=desktop.PREF_DIR/'preferences.json'
        with server(folder/'private',port(),folder/'test.log') as url:
            browser=Browser(url);browser.bootstrap()
            assert browser.request('install',{'driver':'sqlite','site_name':'GUI Acceptance','currency':'USD','unit':'mi','username':'testuser','display_name':'Test User','password':'temporary gui test password','vehicle':{'make':'Dodge','model':'Dart','model_year':2015,'vin':'TEST-GUI-VIN','initial_odometer':120000,'at_engine':25000}})[0]==201
            browser.bootstrap();token=browser.write('token','create',{'label':'GUI smoke'})['token']
            vid=browser.get('state')['vehicles'][0]['id'];did=browser.write('driver','create',{'name':'GUI Inspector'})['id']
            draft=browser.write('inspection','draft',{'vehicle_id':vid,'driver_id':did,'inspection_date':stamp(),'unit':'mi','odometer':'120000','inspection_time':'13:30'})
            root=tk.Tk();app=desktop.Application(root)
            errors=[]
            def abort(e,tb):errors.append(e);traceback.print_exception(type(e),e,tb);root.quit()
            root.report_callback_exception=lambda _type,e,tb:abort(e,tb)
            app.connection(url,token,auto=True)
            def pump_until(test):
                for i in range(180):
                    root.update();time.sleep(.045)
                    if errors:raise errors[0]
                    if test():return
                raise AssertionError('GUI did not reach expected state')
            try:
                pump_until(lambda:bool(app.data))
                for page,_ in desktop.SECTIONS:app.navigate(page);root.update();assert app.body.winfo_children()
                app.toggle();assert desktop.prefs_read()['theme']=='dark'
                app.vehicle_form();root.update()
                def pick_form():
                    return next((w for w in root.winfo_children() if isinstance(w,tk.Toplevel) and 'Add vehicle' in w.title()),None)
                assert pick_form();pick_form().destroy();root.update()
                doc=desktop.DocEditor(app,'inspection',app.client.request('document',{'id':draft['id']})['document'])
                assert '0 of 18 complete' in doc.progress_var.get()
                for k,(opt,txt) in doc.items.items():opt.set('pass')
                doc.items['tires'][0].set('attention');doc.items['tires'][1].insert('1.0','Check pressure before travel.');doc.set('inspector_name','GUI Inspector');doc.set('acknowledged',True);doc.set('ack_name','GUI Inspector')
                assert '18 of 18 complete' in doc.progress_var.get() and '1 finding' in doc.progress_var.get();doc.finalize();pump_until(lambda:not doc.alive())
                result=browser.get('document',{'id':draft['id']})['document'];assert result['status']=='submitted' and result['data']['items']['tires']['result']=='attention'
                sale=desktop.DocEditor(app,'sale',vehicle_id=vid);sale.set('price','5000');sale.set('deposit','500');assert '$4,500.00' in sale.balance_var.get();sale.persist('draft',True);pump_until(lambda:sale.id is not None)
                assert browser.get('document',{'id':sale.id})['document']['data']['balance']=='4500.00'
                print('PASS: Tk GUI connection, navigation, persistent theme, synchronized inspection draft/submission and live sale balance')
            finally:
                app.closed=True;app.pool.shutdown(wait=True,cancel_futures=True);root.destroy();app.temp.cleanup()


if __name__=='__main__':run()
