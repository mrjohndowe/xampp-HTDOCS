"""Optional real browser smoke test on a disposable PHP/SQLite installation.
Requirements: requests, playwright, Chromium. Run: python tests/browser_smoke.py
No production URL/credentials are accepted or used.
"""
from integration import System
from playwright.sync_api import sync_playwright

def main():
 System.setUpClass()
 try:
  with sync_playwright() as p:
   browser=p.chromium.launch(headless=True,args=['--no-sandbox'])
   page=browser.new_page(viewport={'width':1440,'height':1000});errors=[]
   page.on('pageerror',lambda e:errors.append(str(e)))
   page.on('dialog',lambda d:d.accept())
   page.goto(System.base)
   page.locator('[name=username]').fill('test-admin');page.locator('[name=password]').fill(System.password)
   page.get_by_role('button',name='Sign in').click();page.locator('#page-title').wait_for(state='visible');page.wait_for_timeout(500)
   page.locator('[data-nav=vehicles]').click();page.get_by_role('button',name='+ New record').click()
   page.locator('[name=make]').fill('DODGE');page.locator('[name=make]').dispatch_event('input')
   assert page.locator('#model-list option[value="Dart"]').count()==1
   page.locator('[name=model]').fill('Dart');page.locator('[name=make]').fill('FORD');page.locator('[name=make]').dispatch_event('input')
   assert page.locator('[name=model]').input_value()==''
   assert page.locator('#model-list option[value="Dart"]').count()==0
   page.locator('#close-editor').click()
   page.locator('[data-nav=inspections]').click();page.get_by_role('button',name='+ New inspection').click()
   page.locator('[name=vehicle_id]').select_option('1');page.locator('[name=inspector]').fill('Test Inspector')
   page.locator('[name=date]').fill('2026-11-19');page.locator('[name=time]').fill('09:30');page.locator('[name=odometer]').fill('120000')
   assert page.locator('[type=radio]:checked').count()==0
   page.locator('#draft').click();page.wait_for_timeout(400);assert 'Saved' in page.locator('#save-state').inner_text()
   page.set_viewport_size({'width':390,'height':844})
   assert page.locator('#editor').bounding_box()['width']<390
   keys=page.locator('.check-item').evaluate_all('(els)=>els.map(e=>e.dataset.item)')
   for key in keys:page.locator(f'[name="item-{key}"][value="Pass"]').check()
   page.locator('[name="item-tires"][value="Needs attention"]').check();page.locator('[name=note-tires]').fill('Low pressure')
   assert '1 finding' in page.locator('#completion').inner_text()
   page.locator('[name=acknowledged]').check();page.locator('[name=status]').select_option('Completed');page.locator('#save-record').click();page.wait_for_timeout(500)
   assert page.locator('[name=status]').is_disabled()
   page.locator('#close-editor').click();page.locator('[data-nav=sales]').click();page.get_by_role('button',name='+ New agreement').click()
   page.locator('[name=buyer_name]').fill('Test Buyer');page.locator('[name=seller_name]').fill('Test Seller');page.locator('[name=price]').fill('5000');page.locator('[name=deposit]').fill('500')
   assert page.locator('[name=balance]').input_value()=='4500.00'
   page.locator('#draft').click();page.wait_for_timeout(400);assert 'Saved' in page.locator('#save-state').inner_text()
   page.locator('#preview').click();assert '4500' in page.locator('#live-preview').inner_text()
   page.locator('#close-editor').click();page.locator('#theme').click();page.reload();page.wait_for_timeout(400)
   assert page.locator('html').get_attribute('data-theme')=='dark'
   assert not errors,errors
   browser.close()
   print('PASS: catalog dependencies, incomplete draft, 390px sheet, checklist progress/submission, bill balance/preview and persistent browser theme. No uncaught JavaScript errors.')
 finally:System.tearDownClass()
if __name__=='__main__':main()
