const installed = document.body.dataset.installed === 'true';
const api = (body) => fetch('?r=api', { method:'POST', headers:{'content-type':'application/json'}, body: JSON.stringify(body), credentials:'same-origin' }).then(async r => {
  const data = await r.json();
  if (!r.ok || data.ok === false) throw new Error(data.error || 'Request failed');
  return data;
});
const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const ucwords = (value) => String(value ?? '').replace(/(^|\s)(\p{L})/gu, (_, space, letter) => space + letter.toLocaleUpperCase());
const titleCaseInput = input => {
  if (!(input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement)) return;
  if (input instanceof HTMLInputElement && input.type !== 'text') return;
  if (input.dataset.noTitleCase === 'true') return;
  const key = (input.name || input.id || '').toLowerCase();
  if (['email','password','url'].includes(key)) return;
  const start = input.selectionStart;
  const end = input.selectionEnd;
  const titleCased = ucwords(input.value);
  if (input.value !== titleCased) {
    input.value = titleCased;
    if (start !== null && end !== null) input.setSelectionRange(start, end);
  }
};
document.addEventListener('input', event => titleCaseInput(event.target), true);
document.addEventListener('focusout', event => titleCaseInput(event.target));
const el = (html) => { const d = document.createElement('div'); d.innerHTML = html.trim(); return d.firstElementChild; };
let state = { user:null, module:'dashboard', items:[], item:null, lists:{} };

function field(label, inner){ return `<label class="muted" style="display:grid;gap:6px;font-size:12px;font-weight:700">${label}${inner}</label>`; }
function autosave(resource, id, data){
  document.querySelectorAll('.save').forEach(n => n.textContent = 'Saving…');
  return api({ action:'vault', resource, op:'update', id, data }).then(() => {
    document.querySelectorAll('.save').forEach(n => n.textContent = 'Saved');
  }).catch(() => document.querySelectorAll('.save').forEach(n => n.textContent = 'Save failed'));
}
// map to resource ops used by PHP
async function vault(resource, op, extra={}) {
  return api({ action:'noop', resource, op, ...extra });
}

async function boot(){
  const root = document.getElementById('app');
  if (!installed) return renderInstall(root);
  try {
    const me = await api({ action:'me' });
    state.user = me.user;
    document.documentElement.dataset.theme = me.user.theme === 'dark' ? 'dark' : (me.user.theme === 'light' ? 'light' : (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
    renderApp(root);
  } catch {
    renderLogin(root);
  }
}

function renderLogin(root){
  root.innerHTML = `<div class="panel" style="max-width:460px;margin:10vh auto;padding:34px">
    <div class="brand-mark"><span class="brand-icon">✦</span><div><h2>Haven</h2><span class="muted">Your private space</span></div></div><h1 style="margin-top:32px">Welcome back</h1><p class="muted">Sign in to open your encrypted vault.</p>
    <form id="login" class="grid" style="margin-top:16px">
      ${field('Email', '<input name="email" type="email" required>')}
      ${field('Password', '<input name="password" type="password" required>')}
      <button style="width:100%">Sign in</button>
      <p class="muted err"></p>
    </form></div>`;
  root.querySelector('#login').onsubmit = async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try { await api({ action:'login', email:fd.get('email'), password:fd.get('password') }); location.reload(); }
    catch(err){ root.querySelector('.err').textContent = err.message; }
  };
}

function renderInstall(root){
  const form = { driver:'sqlite', appName:'Haven', timezone:Intl.DateTimeFormat().resolvedOptions().timeZone, dateFormat:'YYYY-MM-DD', email:'', username:'admin', password:'', mysqlHost:'127.0.0.1', mysqlDatabase:'haven', mysqlUser:'root', mysqlPassword:'' };
  root.innerHTML = `<div class="panel" style="max-width:900px;margin:4vh auto"><div class="brand-mark"><span class="brand-icon">✦</span><div><h2>Haven</h2><span class="muted">Private by design</span></div></div><h1 style="margin-top:28px">Set up your space</h1>
    <p class="muted">PHP environment, SQLite (default) or MySQL, admin account, encryption key, then lock.</p>
    <div class="grid" id="ins"></div>
    <p class="save"></p>
    <button id="go">Install and lock</button>
    <p class="err muted"></p>
  </div>`;
  const ins = root.querySelector('#ins');
  const draw = () => {
    ins.innerHTML = [
      field('Database', `<select id="driver"><option value="sqlite">SQLite</option><option value="mysql">MySQL</option></select>`),
      field('App name', `<input id="appName" value="${form.appName}">`),
      field('Admin email', `<input id="email" type="email">`),
      field('Username', `<input id="username" value="admin">`),
      field('Password (min 10)', `<input id="password" type="password">`),
      field('MySQL host', `<input id="mysqlHost" value="127.0.0.1">`),
      field('MySQL database', `<input id="mysqlDatabase" value="haven">`),
      field('MySQL user', `<input id="mysqlUser" value="root">`),
      field('MySQL password', `<input id="mysqlPassword" type="password">`),
    ].join('');
  };
  draw();
  root.querySelector('#go').onclick = async () => {
    try {
      const g = (id) => root.querySelector('#'+id).value;
      await api({ action:'install', step:'write-config', driver:g('driver'), appName:g('appName'), mysqlHost:g('mysqlHost'), mysqlDatabase:g('mysqlDatabase'), mysqlUser:g('mysqlUser'), mysqlPassword:g('mysqlPassword') });
      await api({ action:'install', step:'secret' });
      await api({ action:'install', step:'admin', email:g('email'), username:g('username'), password:g('password'), displayName:g('username') });
      location.reload();
    } catch(err) { root.querySelector('.err').textContent = err.message; }
  };
}

function renderApp(root){
  root.innerHTML = `<div class="layout">
    <aside class="panel app-sidebar">
      <div class="brand-mark"><span class="brand-icon">✦</span><h2>Haven</h2></div>
      <p class="muted" style="margin:9px 0 0">A little more room to breathe.</p>
      <div class="account-chip" style="margin-top:22px">Signed in as <strong>${escapeHtml(state.user.displayName)}</strong></div>
      <nav id="nav"></nav>
      <div class="sidebar-foot"><span class="muted" style="font-size:12px">🔒 Your vault is encrypted</span><button class="ghost" id="out">Log out</button></div>
    </aside>
    <main class="panel app-main" id="main"></main>
  </div>`;
  const modules = [
    {id:'dashboard',label:'Overview',icon:'⌂'}, {id:'contacts',label:'People',icon:'♡'},
    {id:'accounts',label:'Accounts',icon:'▣'}, {id:'journal',label:'Journal',icon:'✎'},
    {id:'intimacy',label:'Shared moments',icon:'◷'}, {id:'backup',label:'Backup',icon:'↓'}, {id:'settings',label:'Settings',icon:'⚙'}
  ];
  const nav = root.querySelector('#nav');
  modules.forEach(m => {
    const b = document.createElement('button');
    b.textContent = `${m.icon}  ${m.label}`;
    b.dataset.module = m.id;
    b.onclick = () => { state.module = m.id; state.item = null; paint(); };
    nav.appendChild(b);
  });
  root.querySelector('#out').onclick = async () => { await api({ action:'logout' }); location.reload(); };
  async function paint(){
    [...nav.children].forEach(b => b.classList.toggle('active', b.dataset.module === state.module));
    const main = root.querySelector('#main');
    main.innerHTML = '<p class="muted">Loading…</p>';
    if (state.module === 'dashboard') {
      const results = await Promise.all(['contacts','accounts','journal'].map(resource => api({ resource, op:'list' })));
      const [contacts, accounts, journal] = results.map(result => result.items || []);
      const recentContacts = contacts.slice(0,3);
      const journalDates = journal.slice(0,3).map(entry => entry.entry_at ? new Date(entry.entry_at).toLocaleDateString(undefined,{year:'numeric',month:'short',day:'numeric'}) : 'Date not set');
      main.innerHTML = `<section class="dashboard-hero">
          <div class="eyebrow">Your private space</div><h1>Good to see you, ${escapeHtml(state.user.displayName)}</h1>
          <p>A calm overview of the people and records you’ve saved. Your vault stays private, and account passwords remain encrypted.</p>
        </section>
        <section class="dash-stats" aria-label="Vault overview">
          <div class="dash-stat"><span>People</span><strong>${contacts.length}</strong><span>contacts in your circle</span></div>
          <div class="dash-stat"><span>Accounts</span><strong>${accounts.length}</strong><span>saved sign-ins and records</span></div>
          <div class="dash-stat"><span>Journal</span><strong>${journal.length}</strong><span>private entries</span></div>
          <div class="dash-stat"><span>Vault</span><strong>✓</strong><span>autosave is ready</span></div>
        </section>
        <section class="dash-columns">
          <div class="dash-section"><h2>Recently updated people</h2>
            ${recentContacts.length ? recentContacts.map(person => `<div class="dash-row"><div><strong>${escapeHtml(person.name || 'Unnamed contact')}</strong><small>${escapeHtml(person.relationship || 'Contact')}</small></div><span class="muted">${person.updated_at ? new Date(person.updated_at).toLocaleDateString(undefined,{month:'short',day:'numeric'}) : ''}</span></div>`).join('') : '<p class="muted">Your contact list is ready when you are.</p>'}
          </div>
          <div class="dash-section"><h2>Quick access</h2><p class="muted">Jump into a section or create your first record.</p><div class="dash-actions"><button data-go="contacts">People</button><button data-go="accounts">Accounts</button><button data-go="journal">Journal</button></div><div style="margin-top:18px;padding-top:14px;border-top:1px solid var(--line)"><strong>Journal activity</strong><p class="muted" style="margin:6px 0 0">${journalDates.length ? `Latest entry dates: ${journalDates.join(' · ')}` : 'Your journal is private and ready for a first entry.'}</p></div><p class="muted" style="font-size:12px;margin:16px 0 0">Tip: download a JSON backup regularly from Backup.</p></div>
        </section>`;
      main.querySelectorAll('[data-go]').forEach(button => button.onclick = () => { state.module = button.dataset.go; state.item = null; paint(); });
      return;
    }
    if (state.module === 'backup') {
      main.innerHTML = `<div class="page-heading"><div><h1>Your backup</h1><p class="muted">Keep a copy of your Haven data somewhere safe.</p></div></div><div class="dash-section"><h2>Download a copy</h2><p class="muted">Your export can contain private information. Store it securely and delete copies you no longer need.</p><button id="dl">Download encrypted vault data</button></div>`;
      main.querySelector('#dl').onclick = async () => {
        const data = await api({ action:'backup' });
        const blob = new Blob([JSON.stringify(data,null,2)], {type:'application/json'});
        const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'haven-backup.json'; a.click();
      };
      return;
    }
    if (state.module === 'settings') {
      main.innerHTML = `<div class="page-heading"><div><h1>Settings</h1><p class="muted">Make Haven feel comfortable to use.</p></div></div><div class="dash-section"><h2>Appearance</h2><p class="muted">Choose the color theme for this device.</p>
        ${field('Color theme', `<select id="theme"><option value="light">Light</option><option value="dark">Dark</option><option value="system">Use device setting</option></select>`)}</div>`;
      main.querySelector('#theme').value = state.user.theme || 'system';
      main.querySelector('#theme').onchange = async (e) => {
        await api({ action:'theme', theme:e.target.value });
        location.reload();
      };
      return;
    }
    const resource = state.module;
    const data = await api({ resource, op:'list' });
    const items = data.items || [];
    const contactItems = ['intimacy','journal'].includes(resource) ? ((await api({ resource:'contacts', op:'list' })).items || []) : [];
    const labels = {contacts:['People','The people who matter to you.','Add person'],accounts:['Accounts','Keep your sign-ins and important account details together.','Add account'],journal:['Journal','A private place for your thoughts.','New entry'],intimacy:['Shared moments','Keep meaningful moments in your own private record.','Add moment']};
    const [title,subtitle,addLabel] = labels[resource] || [resource,'Your saved records.','New'];
    main.innerHTML = `<div class="page-heading"><div><h1>${title}</h1><p class="muted">${subtitle}</p></div><div><span class="save" aria-live="polite">Autosave is on</span> <button id="new">${addLabel}</button></div></div>
      <div class="workspace-layout">
        <div class="list record-list" id="list"></div><div id="editor" class="dash-section"></div>
      </div>`;
    main.querySelector('#new').onclick = async () => { const r = await api({ resource, op:'create' }); state.itemId = r.id; state.item = null; paint(); };
    const list = main.querySelector('#list');
    items.forEach(item => {
      const b = document.createElement('button');
      b.textContent = item.name || item.title || (item.event_type ? item.event_type + ' · ' + item.event_date : item.id);
      if (item.id === state.itemId) state.item = item;
      b.classList.toggle('selected', item === state.item);
      b.onclick = () => { state.item = item; state.itemId = item.id; [...list.children].forEach(row => row.classList.toggle('selected', row === b)); drawEditor(); };
      list.appendChild(b);
    });
    if (state.item) drawEditor();
    else main.querySelector('#editor').innerHTML = `<div class="empty-state"><div style="font-size:28px;margin-bottom:8px">✦</div><strong>${items.length ? 'Choose a record' : 'A fresh start'}</strong><p>${items.length ? 'Select something from your list to view or edit it.' : `Add your first ${title.toLowerCase().replace(/s$/,'').replace('people','person')} to get started.`}</p>${items.length ? '' : `<button id="empty-new">${addLabel}</button>`}</div>`;
    main.querySelector('#empty-new')?.addEventListener('click', () => main.querySelector('#new').click());
    function drawEditor(){
      const item = state.item; if (!item) { main.querySelector('#editor').innerHTML = '<p class="muted">Select a record</p>'; return; }
      const editor = main.querySelector('#editor');
      if (resource === 'accounts') {
        const accountTypes = ['Website or online service','Email account','Social media account','Gaming account','Subscription','Banking account','Credit or debit card record','Investment account','Utility account','Other'];
        const typeOptions = accountTypes.includes(String(item.type || '')) ? accountTypes : [String(item.type || ''), ...accountTypes];
        const bankSelected = String(item.type || '').toLowerCase().includes('bank');
        const accountField = (label, key, control) => `<div class="account-field"><label for="account-${key}">${label}</label><div class="account-field-row">${control}<button type="button" class="copy-button" data-copy-key="${key}" aria-label="Copy ${label}">Copy</button></div></div>`;
        editor.innerHTML = `<div class="record-editor">
          ${accountField('Type','type',`<select id="account-type" data-account-key="type">${typeOptions.map(type => `<option value="${escapeHtml(type)}"${type === item.type ? ' selected' : ''}>${escapeHtml(type)}</option>`).join('')}</select>`)}
          ${accountField('Account Name','name',`<input id="account-name" data-account-key="name" type="text" value="${escapeHtml(item.name || '')}">`)}
          ${accountField('Username','username',`<input id="account-username" data-account-key="username" data-no-title-case="true" type="text" autocomplete="off" value="${escapeHtml(item.username || '')}">`)}
          ${accountField('Password','password',`<input id="account-password" data-account-key="password" type="password" autocomplete="new-password" placeholder="${item.hasPassword ? 'Saved securely · enter to replace' : 'Enter password'}">`)}
          ${accountField('URL','url',`<input id="account-url" data-account-key="url" data-no-title-case="true" type="text" inputmode="url" value="${escapeHtml(item.url || '')}">`)}
          <label class="show-fields" id="account-show-fields" style="display:${bankSelected ? 'flex' : 'none'}"><input data-account-show-fields type="checkbox"${item.show_banking_fields ? ' checked' : ''}> <span>Show Fields</span></label>
          <div class="account-banking-fields wide" id="account-banking-fields" style="display:${bankSelected && item.show_banking_fields ? 'grid' : 'none'}">
            ${accountField('Account Number','account_number',`<input id="account-account-number" data-account-key="account_number" data-no-title-case="true" type="password" inputmode="numeric" autocomplete="off" placeholder="${item.hasAccountNumber ? 'Saved securely · enter to replace' : 'Enter account number'}">`)}
            ${accountField('Routing Number','routing_number',`<input id="account-routing-number" data-account-key="routing_number" data-no-title-case="true" type="password" inputmode="numeric" autocomplete="off" placeholder="${item.hasRoutingNumber ? 'Saved securely · enter to replace' : 'Enter routing number'}">`)}
          </div>
          <p class="notice wide">Passwords and banking numbers are encrypted. Leave a secret field blank to keep its saved value.</p>
        </div>`;
        let pendingAccountChanges = {};
        const saveStatus = (message) => { const status = main.querySelector('.save'); if (status) status.textContent = message; };
        const queueAccountSave = (key, value) => {
          item[key] = value;
          pendingAccountChanges[key] = value;
          clearTimeout(window.__havenT);
          saveStatus('Saving changes…');
          window.__havenT = setTimeout(async () => {
            const changes = pendingAccountChanges;
            pendingAccountChanges = {};
            try {
              await api({ resource, op:'update', id:item.id, data:changes });
              if (Object.prototype.hasOwnProperty.call(changes, 'password')) {
                item.hasPassword = Boolean(changes.password);
                const passwordInput = editor.querySelector('[data-account-key="password"]');
                if (passwordInput) {
                  passwordInput.value = '';
                  passwordInput.placeholder = item.hasPassword ? 'Saved securely · enter to replace' : 'Enter password';
                }
              }
              if (Object.prototype.hasOwnProperty.call(changes, 'account_number')) item.hasAccountNumber = Boolean(changes.account_number);
              if (Object.prototype.hasOwnProperty.call(changes, 'routing_number')) item.hasRoutingNumber = Boolean(changes.routing_number);
              saveStatus('All changes saved');
            } catch {
              Object.assign(pendingAccountChanges, changes);
              saveStatus('Could not save changes');
            }
          }, 600);
        };
        editor.querySelectorAll('[data-account-key]').forEach(input => {
          input.addEventListener(input.matches('select') ? 'change' : 'input', () => {
            const key = input.dataset.accountKey;
            queueAccountSave(key, input.value);
            if (key === 'type') {
              const banking = input.value.toLowerCase().includes('bank');
              const toggle = editor.querySelector('#account-show-fields');
              const fields = editor.querySelector('#account-banking-fields');
              toggle.style.display = banking ? 'flex' : 'none';
              fields.style.display = banking && editor.querySelector('[data-account-show-fields]').checked ? 'grid' : 'none';
            }
          });
        });
        editor.querySelector('[data-account-show-fields]').addEventListener('change', event => {
          const checked = event.currentTarget.checked;
          queueAccountSave('show_banking_fields', checked ? 1 : 0);
          editor.querySelector('#account-banking-fields').style.display = checked && editor.querySelector('[data-account-key="type"]').value.toLowerCase().includes('bank') ? 'grid' : 'none';
        });
        const copyText = async value => {
          if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(value);
          const temporary = document.createElement('textarea');
          temporary.value = value;
          temporary.style.position = 'fixed';
          temporary.style.opacity = '0';
          document.body.appendChild(temporary);
          temporary.select();
          const copied = document.execCommand('copy');
          temporary.remove();
          if (!copied) throw new Error('Clipboard access is unavailable');
        };
        editor.querySelectorAll('[data-copy-key]').forEach(button => button.addEventListener('click', async () => {
          const key = button.dataset.copyKey;
          const input = editor.querySelector(`[data-account-key="${key}"]`);
          let value = input?.value || '';
          const secretFlags = {password:'hasPassword',account_number:'hasAccountNumber',routing_number:'hasRoutingNumber'};
          if (!value && item[secretFlags[key]]) {
            let result = await api({ resource:'accounts', op:'get', id:item.id, reveal:true });
            if (result.item.needsReauth) {
              const password = window.prompt('Enter your Haven password to copy this saved secret.');
              if (!password) { button.textContent = 'Cancelled'; setTimeout(() => button.textContent = 'Copy', 1400); return; }
              await api({ action:'reauth', password });
              result = await api({ resource:'accounts', op:'get', id:item.id, reveal:true });
            }
            value = result.item[key] || '';
          }
          if (!value) { button.textContent = 'Empty'; setTimeout(() => button.textContent = 'Copy', 1400); return; }
          try {
            await copyText(String(value));
            button.textContent = 'Copied';
          } catch (error) {
            button.textContent = 'Failed';
            saveStatus(error.message || 'Could not copy this value');
          }
          setTimeout(() => button.textContent = 'Copy', 1400);
        }));
        return;
      }
      const displayOnly = ['owner_id','id','name','age','hasPassword','passwordMasked','created_at','updated_at','deleted_at'];
      const questionnaireRatingKeys = ['rating_overall','rating_emotional','rating_communication','rating_comfort','rating_trust','rating_satisfaction'];
      const keys = Object.keys(item).filter(k => !k.endsWith('_enc') && !displayOnly.includes(k) && !(resource === 'intimacy' && (k === 'contact_ids' || questionnaireRatingKeys.includes(k))) && !(resource === 'journal' && k === 'contact_id'));
      const relationshipOptions = ['Mother','Father','Brother','Sister','Son','Daughter','Friend','Best-Friend','Family','Work','Girl-Friend','Boy-Friend','Partner','Ex','Fling','Friends with Benefits','One Night Stand','Other'];
      const eventOptions = ['Date','Romantic encounter','Affectionate activity','Intimate encounter','Sexual activity','Other'];
      const intimacyDropdowns = {event_type:eventOptions,privacy_level:['Private','Personal','Shared']};
      if (resource === 'intimacy') {
        if (typeof item.questionnaire === 'string') {
          try { item.questionnaire = JSON.parse(item.questionnaire); } catch { item.questionnaire = { reflection:item.questionnaire }; }
        }
        if (!item.questionnaire || typeof item.questionnaire !== 'object' || Array.isArray(item.questionnaire)) item.questionnaire = {};
      }
      const linkedContacts = Array.isArray(item.contact_ids) ? item.contact_ids.map(String) : [];
      const contactPicker = resource === 'intimacy' ? `<div class="contact-picker"><span class="rating-label">Contact Ids</span><details><summary><span class="contact-selection-summary">${linkedContacts.length ? `${linkedContacts.length} contact${linkedContacts.length === 1 ? '' : 's'} selected` : 'Choose contacts'}</span>　⌄</summary><div class="contact-picker-list">${contactItems.length ? contactItems.map(contact => `<label class="contact-picker-option"><input type="checkbox" data-contact-id="${escapeHtml(contact.id)}"${linkedContacts.includes(String(contact.id)) ? ' checked' : ''}>${escapeHtml(contact.name || 'Unnamed contact')}</label>`).join('') : '<span class="muted">Add contacts first to link people to this moment.</span>'}</div></details></div>` : '';
      const journalContact = resource === 'journal' ? `<div class="journal-contact-picker wide">${field('Contact Ids', `<select data-k="contact_id"><option value=""${item.contact_id ? '' : ' selected'}>No linked contact</option>${contactItems.map(contact => `<option value="${escapeHtml(contact.id)}"${String(item.contact_id || '') === String(contact.id) ? ' selected' : ''}>${escapeHtml(contact.name || 'Unnamed contact')}</option>`).join('')}</select>`)}</div>` : '';
      editor.innerHTML = `<div class="record-editor">${contactPicker}${journalContact}${keys.map(k => {
        const val = typeof item[k] === 'object' ? JSON.stringify(item[k]) : (item[k] ?? '');
        const type = String(k).includes('date') ? 'date' : (String(k).includes('time') ? 'time' : (String(k).includes('password') ? 'password' : 'text'));
        const longField = ['description','notes','body','private_notes','questionnaire','optional_details','banking_notes','recovery_codes'].includes(k);
        const safeVal = escapeHtml(val);
        if (k.startsWith('rating_')) {
          const rating = Math.max(0, Math.min(5, Number(val) || 0));
          const label = ucwords(k.replace(/^rating_/, '').replaceAll('_',' '));
          return `<div class="rating-field"><span class="rating-label">${escapeHtml(label)}</span><div class="rating-stars" role="radiogroup" aria-label="${escapeHtml(label)} rating">${[1,2,3,4,5].map(star => `<button type="button" role="radio" aria-checked="${rating === star}" aria-label="${star} of 5 stars" class="rating-star${star <= rating ? ' selected' : ''}" data-rating-key="${k}" value="${star}">${star <= rating ? '★' : '☆'}</button>`).join('')}<span class="rating-value">${rating ? `${rating} / 5` : 'Not rated'}</span></div></div>`;
        }
        if (k === 'relationship') {
          const options = relationshipOptions.includes(String(val)) ? relationshipOptions : [String(val), ...relationshipOptions];
          return field('Relationship', `<select data-k="relationship">${options.map(option => `<option value="${escapeHtml(option)}"${option === String(val) ? ' selected' : ''}>${escapeHtml(option)}</option>`).join('')}</select>`);
        }
        if (resource === 'journal' && k === 'mood') {
          const moodOptions = [[1,'Very low'],[2,'Low'],[3,'Okay'],[4,'Good'],[5,'Great']];
          return field('Mood', `<select data-k="mood"><option value="">Not set</option>${moodOptions.map(([score,label]) => `<option value="${score}"${Number(val) === score ? ' selected' : ''}>${score} · ${label}</option>`).join('')}</select>`);
        }
        if (resource === 'intimacy' && k === 'questionnaire') {
          const qValue = key => {
            const value = item.questionnaire[key];
            return value && typeof value === 'object' ? (value.answer ?? '') : (value ?? '');
          };
          const qSkipped = key => Boolean(item.questionnaire[key] && typeof item.questionnaire[key] === 'object' && item.questionnaire[key].skipped);
          const questionSelect = (number,label,key,options) => `<div class="questionnaire-item"><label><span class="question-number">${number}</span>${label}</label><select data-question="${key}"><option value=""${qValue(key) === '' ? ' selected' : ''}>Skip / not recorded</option>${options.map(option => `<option value="${escapeHtml(option)}"${qValue(key) === option ? ' selected' : ''}>${escapeHtml(option)}</option>`).join('')}</select></div>`;
          const questionText = (number,label,key) => `<div class="questionnaire-item"><label for="question-${key}"><span class="question-number">${number}</span>${label}</label><textarea id="question-${key}" rows="3" data-question="${key}"${qSkipped(key) ? ' disabled' : ''}>${escapeHtml(qValue(key))}</textarea><label class="question-skip"><input type="checkbox" data-question-skip="${key}"${qSkipped(key) ? ' checked' : ''}> Skip this question</label></div>`;
          const questionRating = (number,label,key) => {
            const rating = Math.max(0, Math.min(5, Number(item[key]) || 0));
            return `<div class="questionnaire-item"><span class="question-label">${number ? `<span class="question-number">${number}</span>` : ''}${label}</span><div class="questionnaire-rating" role="radiogroup" aria-label="${escapeHtml(label)}">${[1,2,3,4,5].map(star => `<button type="button" role="radio" aria-checked="${rating === star}" aria-label="${star} of 5" class="rating-star${star <= rating ? ' selected' : ''}" data-question-rating="${key}" value="${star}">${star <= rating ? '★' : '☆'}</button>`).join('')}<button type="button" class="question-rating-skip" data-question-rating-skip="${key}">Skip</button><span class="rating-value">${rating ? `${rating} / 5` : 'Not recorded'}</span></div></div>`;
          };
          const moodOptions = ['Very low','Low','Okay','Good','Great','Unknown','Not recorded'];
          return `<section class="questionnaire wide"><h2>Event Questionnaire</h2><p class="muted">Each answer belongs to this event. You may skip any question. A blank or skipped consent answer means consent was not recorded; it must never be taken as proof of consent.</p><div class="questionnaire-fields">
            ${questionSelect(1,'What type of encounter or activity occurred?','activity_type',eventOptions)}
            ${questionSelect(2,'Was the encounter consensual and mutually agreed upon?','consent',['Yes','No','Unknown','Not recorded'])}
            ${questionSelect(3,'Did everyone involved feel comfortable participating?','comfortable',['Yes','No','Unknown','Not recorded'])}
            ${questionRating(4,'How would you rate the overall experience?','rating_overall')}
            ${questionRating(5,'How would you rate your emotional connection?','rating_emotional')}
            ${questionRating(6,'How would you rate the communication?','rating_communication')}
            <div class="questionnaire-combined"><span class="question-label"><span class="question-number">7</span>How would you rate the level of comfort and trust?</span>${questionRating('','Comfort','rating_comfort')}${questionRating('','Trust','rating_trust')}</div>
            ${questionRating(8,'How satisfied were you with the experience?','rating_satisfaction')}
            ${questionSelect(9,'How would you describe your mood before the event?','mood_before',moodOptions)}
            ${questionSelect(10,'How would you describe your mood afterward?','mood_after',moodOptions)}
            ${questionText(11,'What did you particularly enjoy?','enjoyed')}
            ${questionText(12,'Was there anything you disliked or would change?','disliked_or_change')}
            ${questionSelect(13,'Were any boundaries discussed or respected?','boundaries',['Yes','No','Unknown','Not recorded'])}
            ${questionText(14,'Was there anything new you learned about your preferences?','preferences_learned')}
            ${questionText(15,'Is there anything you would like to remember for next time?','remember_next_time')}
            ${questionText(16,'Are there any additional notes you want to record?','additional_notes')}
          </div></section>`;
        }
        if (resource === 'intimacy' && intimacyDropdowns[k]) {
          const options = intimacyDropdowns[k].includes(String(val)) ? intimacyDropdowns[k] : [String(val), ...intimacyDropdowns[k]];
          return field(ucwords(k.replaceAll('_',' ')), `<select data-k="${k}">${options.map(option => `<option value="${escapeHtml(option)}"${option === String(val) ? ' selected' : ''}>${escapeHtml(ucwords(option))}</option>`).join('')}</select>`);
        }
        if (resource === 'intimacy' && k === 'duration_minutes') {
          return field('Duration Minutes', `<input data-k="duration_minutes" type="number" min="0" value="${safeVal}" readonly aria-readonly="true">`);
        }
        return field(ucwords(k.replaceAll('_',' ')), longField ? `<textarea class="wide" data-k="${k}" rows="4">${safeVal}</textarea>` : `<input data-k="${k}" type="${type}" value="${safeVal}">`);
      }).join('')}</div>` + (resource==='accounts' ? `<p class="notice" style="margin-top:16px">Account passwords and banking secrets stay encrypted. Revealing them requires password verification.</p>` : '');
      const queueSave = (input) => {
          item[input.dataset.k] = input.value;
          clearTimeout(window.__havenT);
          const status = main.querySelector('.save'); if (status) status.textContent = 'Saving changes…';
          window.__havenT = setTimeout(() => api({ resource, op:'update', id:item.id, data:item }).then(() => { const liveStatus = main.querySelector('.save'); if (liveStatus) liveStatus.textContent = 'All changes saved'; }).catch(() => { const liveStatus = main.querySelector('.save'); if (liveStatus) liveStatus.textContent = 'Could not save changes'; }), 600);
      };
      const recalculateDuration = () => {
        if (resource !== 'intimacy') return;
        const start = editor.querySelector('[data-k="start_time"]')?.value;
        const end = editor.querySelector('[data-k="end_time"]')?.value;
        const duration = editor.querySelector('[data-k="duration_minutes"]');
        if (!duration) return;
        let minutes = null;
        if (start && end) {
          const [startHour,startMinute] = start.split(':').map(Number);
          const [endHour,endMinute] = end.split(':').map(Number);
          const startTotal = startHour * 60 + startMinute;
          let endTotal = endHour * 60 + endMinute;
          if (endTotal < startTotal) endTotal += 24 * 60;
          minutes = endTotal - startTotal;
        }
        item.duration_minutes = minutes;
        duration.value = minutes === null ? '' : String(minutes);
      };
      recalculateDuration();
      editor.querySelectorAll('input[data-k],select[data-k],textarea[data-k]').forEach(inp => {
        const onChange = () => {
          if (inp.dataset.k === 'start_time' || inp.dataset.k === 'end_time') recalculateDuration();
          queueSave(inp);
        };
        inp.addEventListener(inp.matches('select') ? 'change' : 'input', onChange);
        if (inp.type === 'time') inp.addEventListener('change', onChange);
      });
      let pendingQuestionnaireChanges = {};
      const scheduleQuestionnaireSave = changes => {
        Object.assign(pendingQuestionnaireChanges, changes);
        clearTimeout(window.__havenT);
        const status = main.querySelector('.save'); if (status) status.textContent = 'Saving changes…';
        window.__havenT = setTimeout(() => {
          const payload = pendingQuestionnaireChanges;
          pendingQuestionnaireChanges = {};
          api({ resource, op:'update', id:item.id, data:payload }).then(() => { const liveStatus = main.querySelector('.save'); if (liveStatus) liveStatus.textContent = 'All changes saved'; }).catch(() => { Object.assign(pendingQuestionnaireChanges, payload); const liveStatus = main.querySelector('.save'); if (liveStatus) liveStatus.textContent = 'Could not save changes'; });
        }, 600);
      };
      editor.querySelectorAll('[data-question]').forEach(input => {
        const saveAnswer = () => {
          const key = input.dataset.question;
          if (input.matches('select')) item.questionnaire[key] = input.value === '' ? {answer:'',skipped:true} : input.value;
          else {
            const skipped = editor.querySelector(`[data-question-skip="${key}"]`)?.checked || false;
            item.questionnaire[key] = {answer:input.value,skipped};
          }
          scheduleQuestionnaireSave({ questionnaire:item.questionnaire });
        };
        input.addEventListener(input.matches('select') ? 'change' : 'input', saveAnswer);
      });
      editor.querySelectorAll('[data-question-skip]').forEach(skip => skip.addEventListener('change', () => {
        const key = skip.dataset.questionSkip;
        const answer = editor.querySelector(`[data-question="${key}"]`);
        answer.disabled = skip.checked;
        item.questionnaire[key] = {answer:answer.value,skipped:skip.checked};
        scheduleQuestionnaireSave({ questionnaire:item.questionnaire });
      }));
      const updateQuestionRating = (key, value, group) => {
        item[key] = value;
        group.querySelectorAll('[data-question-rating]').forEach(button => {
          const selected = value !== null && Number(button.value) <= value;
          button.classList.toggle('selected', selected);
          button.textContent = selected ? '★' : '☆';
          button.setAttribute('aria-checked', String(value !== null && Number(button.value) === value));
        });
        group.querySelector('.rating-value').textContent = value === null ? 'Not recorded' : `${value} / 5`;
        scheduleQuestionnaireSave({ [key]:value });
      };
      editor.querySelectorAll('[data-question-rating]').forEach(button => button.addEventListener('click', () => updateQuestionRating(button.dataset.questionRating, Number(button.value), button.parentElement)));
      editor.querySelectorAll('[data-question-rating-skip]').forEach(button => button.addEventListener('click', () => updateQuestionRating(button.dataset.questionRatingSkip, null, button.parentElement)));
      editor.querySelectorAll('[data-rating-key]').forEach(starButton => {
        starButton.addEventListener('click', () => {
          const key = starButton.dataset.ratingKey;
          const rating = Number(starButton.value);
          item[key] = rating;
          const group = starButton.parentElement;
          group.querySelectorAll('[data-rating-key]').forEach(button => {
            const selected = Number(button.value) <= rating;
            button.classList.toggle('selected', selected);
            button.textContent = selected ? '★' : '☆';
            button.setAttribute('aria-checked', String(Number(button.value) === rating));
          });
          group.querySelector('.rating-value').textContent = `${rating} / 5`;
          queueSave(starButton);
        });
      });
      editor.querySelectorAll('[data-contact-id]').forEach(contactCheckbox => {
        contactCheckbox.addEventListener('change', () => {
          item.contact_ids = [...editor.querySelectorAll('[data-contact-id]:checked')].map(option => option.dataset.contactId);
          const count = item.contact_ids.length;
          editor.querySelector('.contact-selection-summary').textContent = count ? `${count} contact${count === 1 ? '' : 's'} selected` : 'Choose contacts';
          clearTimeout(window.__havenT);
          const status = main.querySelector('.save'); if (status) status.textContent = 'Saving changes…';
          window.__havenT = setTimeout(() => api({ resource, op:'update', id:item.id, data:item }).then(() => { const liveStatus = main.querySelector('.save'); if (liveStatus) liveStatus.textContent = 'All changes saved'; }).catch(() => { const liveStatus = main.querySelector('.save'); if (liveStatus) liveStatus.textContent = 'Could not save changes'; }), 600);
        });
      });
    }
  }
  paint();
}
boot();
