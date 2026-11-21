"""Isolated SQLite/PHP/desktop integration smoke test. Never uses installed data."""
import os
os.environ.pop("SSLKEYLOGFILE", None)
import importlib.util
import http.cookiejar
import json
import pathlib
import re
import shutil
import socket
import subprocess
import tempfile
import time
import urllib.request
import urllib.parse
import urllib.error
import uuid

ROOT = pathlib.Path(__file__).resolve().parents[1]
def run():
    with tempfile.TemporaryDirectory(prefix='vehicle-tracker-test-') as tmp:
        app = pathlib.Path(tmp)
        for item in ROOT.iterdir():
            if item.name in {'config.php','config.lock','data','.git','tests'}:
                continue
            if item.is_dir(): shutil.copytree(item, app/item.name)
            else: shutil.copy2(item, app/item.name)
        (app/'data').mkdir()
        (app/'router.php').write_text("<?php if (str_starts_with($_SERVER['REQUEST_URI'], '/api/')) { require __DIR__.'/api/index.php'; return true; } return false;")
        sock=socket.socket(); sock.bind(('127.0.0.1',0)); port=sock.getsockname()[1]; sock.close()
        base=f'http://127.0.0.1:{port}'
        log=open(app/'server.log','w+')
        server=subprocess.Popen([shutil.which('php'),'-d','display_errors=1','-S',f'127.0.0.1:{port}','router.php'],cwd=app,stdout=log,stderr=log)
        opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        def web(path,fields=None):
            data=urllib.parse.urlencode(fields).encode() if fields is not None else None
            with opener.open(base+path,data,timeout=10) as r: text=r.read().decode()
            assert 'Fatal error' not in text and 'Warning:' not in text, text[:1000]
            return text
        try:
            for _ in range(60):
                try: web('/install/'); break
                except OSError: time.sleep(.1)
            web('/install/',dict(action='choose_db',driver='sqlite',database=str(app/'data/test.sqlite')))
            web('/install/',dict(action='create_admin',username='testadmin',password='testpassword123',confirm='testpassword123'))
            web('/install/',dict(action='set_prefs',site_name='Test Fleet',currency='USD',distance_unit='mi'))
            page=web('/install/',dict(action='finish',make='Dodge',model='Dart',year=2013,initial_odometer=120000,engine_miles_at_install=25000))
            assert (app/'config.lock').is_file(),page
            for path in ['/','/vehicles.php','/vehicles.php?edit=1','/engines.php','/engines.php?edit=1','/trips.php','/expenses.php','/invoices.php','/settings.php']:
                assert '</html>' in web(path),path
            try: web('/install/'); raise AssertionError('Installer not locked')
            except urllib.error.HTTPError as e: assert e.code==403
            spec=importlib.util.spec_from_file_location('client',ROOT/'desktop/vehicle_tracker.py')
            module=importlib.util.module_from_spec(spec); spec.loader.exec_module(module)
            client=module.ApiClient(base)
            assert client.status()[1]['installed']
            login=client.login('testadmin','testpassword123'); client.token=login['token']
            assert client.list_vehicles()[0]['year']==2013
            client.create_trip(dict(vehicle_id=1,trip_date='2026-11-17',start_odometer=120000,end_odometer=120300,purpose='Test'),str(uuid.uuid4()))
            assert client.list_engines()[0]['engine_miles']==25300
            # Create invoice on website, pay through the actual desktop API client.
            page=web('/invoices.php'); csrf=re.search(r'name="csrf_token" value="([^"]+)"',page)[1]
            web('/invoices.php',dict(action='create_mechanic',csrf_token=csrf,name='Test Mechanic'))
            web('/invoices.php',dict(action='create_invoice',csrf_token=csrf,mechanic_id=1,invoice_number='TEST-600',invoice_date='2026-11-17',due_date='2026-12-01',amount=600))
            client.request('POST','/expenses',dict(vehicle_id=1,expense_date='2026-11-17',category='repairs',amount=600,invoice_id=1))
            result=client.request('POST','/invoices/1/payments',dict(payment_date='2026-11-17',amount=200))[1]
            assert result['balance']==400,result
            assert '$400.00' in web('/invoices.php')
            assert '$600.00' in web('/expenses.php')
            source=web('/download.php?file=vehicle_tracker.py')
            assert source==(ROOT/'desktop/vehicle_tracker.py').read_text()
            print('PASS: fresh onboarding, installer lock, nine page renders, numeric/NULL edit forms, API status/login, desktop trip, 25,300 engine miles, website invoice, desktop payment, $400 balance, $600 expense, current download')
        finally:
            server.terminate(); server.wait(timeout=10); log.close()

if __name__=='__main__': run()
