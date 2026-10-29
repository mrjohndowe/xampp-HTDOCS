(() => {
  const api = 'api.php';
  const csrf = document.querySelector('meta[name="pv-csrf"]').content;
  const steps = [...document.querySelectorAll('.wizard-step')];
  const stepLabels = [...document.querySelectorAll('.step')];
  const errorBox = document.getElementById('install-error');
  let current = 0;
  const extensions = JSON.parse(document.documentElement.dataset.required);
  const checks = [
    ['PHP 8.5', `Detected PHP ${document.documentElement.dataset.php}`, document.documentElement.dataset.phpOk === '1'],
    ['OpenSSL', 'Required for encrypted private records', extensions.openssl],
    ['PDO SQLite', 'Required for the recommended database', extensions.pdo_sqlite],
    ['PDO MySQL', 'Required only when using MySQL', extensions.pdo_mysql]
  ];
  const checkList = document.getElementById('environment-checks');
  checks.forEach(([name, detail, passed]) => { const row=document.createElement('div');row.className='check-row';const icon=document.createElement('span');icon.className=`check-icon ${passed?'':'bad'}`;icon.textContent=passed?'✓':'!';const copy=document.createElement('span');const strong=document.createElement('strong');strong.textContent=name;const small=document.createElement('small');small.textContent=detail;copy.append(strong,small);row.append(icon,copy);checkList.append(row); });
  const tz = document.getElementById('timezone');
  (Intl.supportedValuesOf?.('timeZone') || ['UTC','America/Denver','America/Chicago','America/New_York','Europe/London']).forEach(zone => {
    const option = document.createElement('option'); option.value = zone; option.textContent = zone; if (zone === Intl.DateTimeFormat().resolvedOptions().timeZone) option.selected = true; tz.append(option);
  });
  const showStep = n => { current = n; steps.forEach((el,i) => el.classList.toggle('active', i === n)); stepLabels.forEach((el,i) => { el.classList.toggle('active', i === n); el.classList.toggle('done', i < n); }); };
  const validSection = section => {
    for (const field of section.querySelectorAll('input[required],select[required]')) if (!field.reportValidity()) return false;
    return true;
  };
  document.querySelectorAll('.next').forEach(button => button.addEventListener('click', () => { if (current===0 && (!extensions.openssl || !extensions.pdo || document.documentElement.dataset.phpOk !== '1')) { errorBox.textContent='PHP 8.5, PDO, and OpenSSL are required before setup can continue.';errorBox.classList.remove('hidden');return; } errorBox.classList.add('hidden'); if (validSection(steps[current])) showStep(Math.min(current+1, steps.length-1)); }));
  document.querySelectorAll('.back').forEach(button => button.addEventListener('click', () => showStep(Math.max(current-1, 0))));
  document.getElementById('driver').addEventListener('change', event => {
    document.getElementById('mysql-fields').classList.toggle('hidden', event.target.value !== 'mysql');
    document.getElementById('sqlite-fields').classList.toggle('hidden', event.target.value !== 'sqlite');
  });
  document.getElementById('verify-storage').addEventListener('click', async event => {
    const button=event.currentTarget;const note=document.getElementById('database-check');const settings=Object.fromEntries(new FormData(document.getElementById('install-form')).entries());
    button.disabled=true;button.textContent='Checking…';note.className='notice info';note.textContent='Checking database connection and private folder permissions…';
    try{const response=await fetch('api.php?action=verify_database',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:JSON.stringify({settings})});const data=await response.json();if(!response.ok)throw new Error(data.error||'The check failed.');note.className='notice success';note.textContent=data.message;}
    catch(error){note.className='notice error';note.textContent=error.message;}
    finally{button.disabled=false;button.textContent='Test database and private storage';}
  });
  document.getElementById('install-form').addEventListener('submit', async event => {
    event.preventDefault(); if (!validSection(steps[current])) return;
    const form = new FormData(event.target); const settings = Object.fromEntries(form.entries());
    const button = document.getElementById('finish-install'); button.disabled = true; button.textContent = 'Preparing secure storage…'; errorBox.classList.add('hidden');
    try {
      const response = await fetch('api.php?action=install', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':csrf}, body:JSON.stringify({settings})});
      const data = await response.json(); if (!response.ok) throw new Error(data.error || 'Setup could not be completed.');
      event.target.classList.add('hidden'); document.getElementById('steps').classList.add('hidden'); document.getElementById('install-success').classList.remove('hidden');
    } catch (error) { errorBox.textContent = error.message; errorBox.classList.remove('hidden'); button.disabled = false; button.textContent = 'Create private vault'; }
  });
})();
