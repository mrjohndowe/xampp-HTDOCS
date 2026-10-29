<?php

declare(strict_types=1);
require __DIR__ . '/config.php';
if (!is_installed()) {
  header('Location: install/');
  exit;
}
$pdo = pdo();
$statement = $pdo->query("SELECT value FROM settings WHERE `key` = 'appName'");
$appName = $statement->fetchColumn() ?: APP_NAME;
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0f172a">
  <title><?= htmlspecialchars((string)$appName, ENT_QUOTES, 'UTF-8') ?> — Phone Book</title>
  <link rel="stylesheet" href="assets/css/globals.css">
</head>

<body>
  <header class="topbar">
    <div class="topbar-inner">
      <a class="brand" href="./"><span class="brand-icon">📖</span><span><strong id="app-name"><?= htmlspecialchars((string)$appName, ENT_QUOTES, 'UTF-8') ?></strong><small id="owner-line">Contacts, accounts & private journal</small></span></a>
      <label class="search"><span>⌕</span><input id="search" type="search" placeholder="Search name, email, phone, tag…"></label>
      <nav class="top-actions">
        <button id="theme-toggle" class="button button-muted" data-action="toggle-theme">🌙 Dark mode</button>
        <button class="button button-muted" data-view="settings">⚙️ Settings & Backup</button>
        <button class="button button-muted" data-view="xampp">🐘 XAMPP / PHP</button>
        <button class="button button-primary" data-action="new-contact">+ Add Contact</button>
      </nav>
    </div>
  </header>
  <div id="toast" role="status" aria-live="polite"></div>
  <section id="stats" class="stats container"></section>
  <main class="workspace container">
    <aside class="contact-pane">
      <div id="filters" class="filters"></div>
      <div id="contact-list" class="contact-list"></div>
    </aside>
    <section id="content" class="content-pane">
      <div class="empty-state">Loading your phone book…</div>
    </section>
  </main>
  <div id="modal-root"></div>
  <script>
    (() => {
      const $ = (selector, root = document) => root.querySelector(selector);
      const state = {
        contacts: [],
        stats: {},
        selectedId: null,
        view: 'overview',
        filter: 'All',
        search: '',
        accounts: [],
        journal: [],
        journalFilter: 'All',
        showPasswords: {}
      };
      const relationships = ['Mother', 'Father', 'Brother', 'Sister', 'Son', 'Daughter', 'Friend', 'Best-Friend', 'Family', 'Work', 'Girl-Friend', 'Boy-Friend', 'Partner', 'Ex', 'Fling', 'Friends with Benefits', 'One Night Stand', 'Other'];
      const accountTypes = ['Bank', 'Email', 'Social', 'Work', 'Finance', 'Gaming', 'Shopping', 'Other'];
      const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      } [char]));
      const dateInput = value => value ? String(value).slice(0, 10) : '';
      const selectedContact = () => state.contacts.find(contact => Number(contact.id) === Number(state.selectedId)) || null;
      const text = (label, name, value = '', type = 'text', extra = '') => `<label class="field"><span>${label}</span><input name="${name}" type="${type}" value="${esc(value)}" ${extra}></label>`;
      const area = (label, name, value = '', rows = 3) => `<label class="field"><span>${label}</span><textarea name="${name}" rows="${rows}">${esc(value)}</textarea></label>`;
      const select = (label, name, choices, value = '') => `<label class="field"><span>${label}</span><select name="${name}">${choices.map(item => `<option value="${esc(item)}" ${item === value ? 'selected' : ''}>${esc(item || '—')}</option>`).join('')}</select></label>`;
      const checkbox = (label, name, checked = false) => `<label class="check-field"><input type="checkbox" name="${name}" ${checked ? 'checked' : ''}><span>${label}</span></label>`;
      const button = (label, action, style = 'button-light') => `<button type="button" class="button ${style}" data-action="${action}">${label}</button>`;

      async function api(action, method = 'GET', data = null, id = null, params = {}) {
        const query = new URLSearchParams({
          action,
          ...params
        });
        if (id !== null) query.set('id', id);
        const response = await fetch(`api.php?${query}`, {
          method,
          headers: data ? {
            'Content-Type': 'application/json'
          } : {},
          body: data ? JSON.stringify(data) : undefined
        });
        const body = await response.json();
        if (!response.ok) throw new Error(body.error || `Request failed (${response.status})`);
        return body;
      }

      function notify(message, error = false) {
        const toast = $('#toast');
        toast.textContent = message;
        toast.className = error ? 'toast toast-error visible' : 'toast visible';
        clearTimeout(notify.timeout);
        notify.timeout = setTimeout(() => {
          toast.className = 'toast';
        }, 3600);
      }

      function age(dob) {
        if (!dob) return null;
        const birth = new Date(`${dob}T12:00:00`);
        if (Number.isNaN(birth.getTime())) return null;
        const today = new Date();
        let years = today.getFullYear() - birth.getFullYear();
        if (today.getMonth() < birth.getMonth() || (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate())) years--;
        return years;
      }

      async function refresh() {
        const [contacts, stats, settings] = await Promise.all([api('contacts', 'GET', null, null, state.search ? {
          q: state.search
        } : {}), api('stats'), api('settings')]);
        state.contacts = contacts;
        state.stats = stats;
        window.phonebookSettings = settings.settings || {};
        window.phonebookDatabase = settings.database || {};
        document.documentElement.dataset.theme = settings.settings.theme === 'dark' ? 'dark' : 'light';
        updateThemeButton();
        $('#app-name').textContent = settings.settings.appName || 'My Phone Book';
        $('#owner-line').textContent = `Owner: ${settings.settings.ownerName || settings.settings.adminUsername || '—'} • ${settings.database.driver.toUpperCase()} • PHP ${settings.settings.phpVersion || '8.5'} XAMPP`;
        if (!contacts.some(contact => Number(contact.id) === Number(state.selectedId))) state.selectedId = contacts.length ? Number(contacts[0].id) : null;
        await loadDetail();
        render();
      }

      async function loadDetail() {
        const contact = selectedContact();
        if (!contact) {
          state.accounts = [];
          state.journal = [];
          return;
        }
        [state.accounts, state.journal] = await Promise.all([
          api('accounts', 'GET', null, null, {
            contactId: contact.id
          }),
          api('journal', 'GET', null, null, {
            contactId: contact.id
          })
        ]);
      }

      function renderStats() {
        const cards = [
          ['👥', 'Contacts', state.stats.contacts],
          ['⭐', 'Favorites', state.stats.favorites],
          ['🔑', 'Accounts', state.stats.accounts],
          ['🏦', 'Bank Accounts', state.stats.bankAccounts],
          ['📓', 'Journal', state.stats.journal],
          ['💜', 'Intimate', state.stats.intimate]
        ];
        $('#stats').innerHTML = cards.map(([icon, label, value]) => `<div class="stat-card"><strong>${esc(value || 0)}</strong><span>${icon} ${label}</span></div>`).join('');
      }

      function renderFilters() {
        const filters = ['All', 'Favorites', ...relationships];
        $('#filters').innerHTML = filters.map(filter => `<button class="filter-chip ${state.filter === filter ? 'active' : ''}" data-filter="${esc(filter)}">${esc(filter)}</button>`).join('');
      }

      function renderContacts() {
        const contacts = state.contacts.filter(contact => state.filter === 'All' ? true : state.filter === 'Favorites' ? contact.isFavorite : contact.relationship === state.filter);
        $('#contact-list').innerHTML = contacts.length ? contacts.map(contact => {
          const selected = Number(contact.id) === Number(state.selectedId);
          return `<button class="contact-row ${selected ? 'selected' : ''}" data-select="${contact.id}">
        <span class="avatar" style="background:${esc(contact.avatarColor || '#6366f1')}">${esc((contact.firstName || '?').slice(0, 1))}${esc((contact.lastName || '').slice(0, 1))}</span>
        <span class="contact-summary"><strong>${esc(contact.firstName)} ${esc(contact.lastName)} ${contact.isFavorite ? '⭐' : ''}</strong><small>${esc(contact.phone || contact.email || 'No phone/email')} ${age(contact.dob) !== null ? `· ${age(contact.dob)} yrs` : ''}</small><small>${esc(contact.relationship || 'Friend')} · ${contact.accountsCount || 0} accounts · ${contact.journalCount || 0} journal</small></span>
      </button>`;
        }).join('') : `<div class="list-empty">No contacts yet.<br><button class="link-button" data-action="new-contact">+ Add your first contact</button></div>`;
      }

      function info(label, value) {
        return `<div class="info-item"><span>${label}</span><strong>${value ? esc(value) : '—'}</strong></div>`;
      }

      function renderOverview(contact) {
        const next = contact.dob ? new Date(`${contact.dob}T12:00:00`) : null;
        if (next) {
          next.setFullYear(new Date().getFullYear());
          if (next < new Date()) next.setFullYear(next.getFullYear() + 1);
        }
        const address = [contact.address, contact.city, contact.state, contact.zip, contact.country].filter(Boolean).join(', ');
        return `<div class="detail-grid">
      ${info('🎂 Date of Birth', contact.dob ? `${dateInput(contact.dob)}${age(contact.dob) !== null ? ` (${age(contact.dob)} years old)` : ''}${next ? ` · Next birthday ${next.toLocaleDateString()}` : ''}` : '')}
      ${info('💑 Anniversary', contact.anniversary)}
      ${info('✉️ Email', contact.email)}
      ${info('📞 Phone', contact.phone)}
      ${info('📞 Phone 2', contact.phone2)}
      ${info('💼 Company / Job', [contact.company, contact.jobTitle].filter(Boolean).join(' · '))}
      ${info('🤝 Relationship', contact.relationship)}
      ${info('⚧ Gender / Pronouns', [contact.gender, contact.pronouns].filter(Boolean).join(' · '))}
      ${info('🏷️ Tags', contact.tags)}
      ${info('🤝 How we met', contact.howWeMet)}
      <div class="info-item span-two"><span>🏠 ADDRESS</span><strong>${address ? esc(address) : 'No address added — address is optional.'}</strong></div>
      <div class="info-item span-two"><span>📝 NOTES</span><strong class="pre-wrap">${contact.notes ? esc(contact.notes) : '—'}</strong></div>
      <div class="detail-actions span-two">${contact.phone ? `<a class="button button-success" href="tel:${esc(contact.phone)}">📞 Call</a>` : ''}${contact.email ? `<a class="button button-blue" href="mailto:${esc(contact.email)}">✉️ Email</a>` : ''}${button('🔑 View Accounts', 'view-accounts', 'button-dark')}${button('💜 View Journal', 'view-journal', 'button-rose')}</div>
    </div>`;
      }

      function renderAccounts(contact) {
        const cards = state.accounts.map(account => `<article class="record-card">
      <div class="record-head"><div><span class="badge ${account.type === 'Bank' ? 'badge-green' : 'badge-violet'}">${account.type === 'Bank' ? '🏦 BANK' : esc(account.type)}</span> ${account.isPrimary ? '<span class="badge badge-gold">PRIMARY</span>' : ''}<h3>${esc(account.label)}</h3>${account.type === 'Bank' ? `<p>${esc(account.bankName)} · ${esc(account.accountType)}</p>` : ''}</div><div class="record-actions">${button('Edit', `edit-account:${account.id}`)}${button('Delete', `delete-account:${account.id}`, 'button-danger')}</div></div>
      <div class="credential-lines"><p><b>Username:</b> ${esc(account.username || '—')} ${copyButton(account.username)}</p><p><b>Email:</b> ${esc(account.email || '—')} ${copyButton(account.email)}</p><p><b>Password:</b> <span>${state.showPasswords[account.id] ? esc(account.password || '—') : '••••••••'}</span> <button class="text-button" data-action="toggle-password:${account.id}">${state.showPasswords[account.id] ? 'Hide' : 'Show'}</button> ${copyButton(account.password)}</p>${account.website ? `<p><b>Website:</b> <a href="${esc(account.website)}" target="_blank" rel="noopener noreferrer">${esc(account.website)}</a></p>` : ''}
      ${account.type === 'Bank' ? `<div class="bank-details"><p><b>Routing #:</b> ${esc(account.routingNumber || '—')} ${copyButton(account.routingNumber)}</p><p><b>Account #:</b> ${state.showPasswords[account.id] ? esc(account.accountNumber || '—') : `••••${esc((account.accountNumber || '').slice(-4))}`} ${copyButton(account.accountNumber)}</p></div>` : ''}</div>${account.notes ? `<p class="record-note">📝 ${esc(account.notes)}</p>` : ''}</article>`).join('');
        return `<div class="section-heading"><div><h2>Account Information</h2><p>Username, email, password & notes per account. Bank accounts include routing and account details.</p></div>${button('+ Add Account', 'new-account', 'button-primary')}</div><div class="record-grid">${cards || `<div class="empty-box">No accounts yet for ${esc(contact.firstName)}.<br>Add bank, email, social, work accounts etc.</div>`}</div>`;
      }

      function copyButton(value) {
        return value ? `<button class="text-button" data-copy="${esc(value)}">Copy</button>` : '';
      }

      function journalMatches(entry) {
        if (state.journalFilter === 'All') return true;
        if (state.journalFilter === 'Intimate Only') return entry.isIntimate;
        if (state.journalFilter === 'Non-Intimate') return !entry.isIntimate;
        return entry.entryType === state.journalFilter;
      }

      function renderJournal(contact) {
        const filters = ['All', 'Intimate Only', 'Non-Intimate', 'Romantic', 'Health'];
        const entries = state.journal.filter(journalMatches);
        return `<div class="section-heading"><div><h2>💜 Sex & Relationship Journal — ${esc(contact.firstName)}</h2><p>Private per-person journal. Toggle intimacy to open the detailed questionnaire.</p></div>${button('+ New Entry', 'new-journal', 'button-rose')}</div>
      <div class="filters journal-filters">${filters.map(filter => `<button class="filter-chip ${state.journalFilter === filter ? 'active rose-active' : ''}" data-journal-filter="${esc(filter)}">${esc(filter)}</button>`).join('')}</div>
      <div class="journal-list">${entries.length ? entries.map(entry => `<article class="journal-card ${entry.isIntimate ? 'intimate' : ''}">
        <div class="record-head"><div><span class="badge">${esc(entry.entryDate)}</span> <span class="badge ${entry.isIntimate ? 'badge-rose' : ''}">${entry.isIntimate ? '💜 INTIMATE' : esc(entry.entryType)}</span> ${entry.rating > 0 ? `<span class="rating">★ ${esc(entry.rating)}/10</span>` : ''}<h3>${esc(entry.title)}</h3><p>${esc(entry.location)} ${entry.mood ? `· Mood: ${esc(entry.mood)}` : ''}</p></div>${button('Delete', `delete-journal:${entry.id}`, 'button-danger')}</div>
        ${entry.notes ? `<p class="pre-wrap">${esc(entry.notes)}</p>` : ''}${entry.isIntimate ? `<div class="questionnaire-summary"><p><b>Intimacy details:</b> ${esc(entry.intimacyType || '—')} · ${entry.durationMinutes ? `${esc(entry.durationMinutes)} min` : 'duration not recorded'} · ${esc(entry.protectionUsed || '—')}</p><p><b>Consent confirmed:</b> ${entry.consentConfirmed ? 'Yes' : 'No'} · <b>Orgasm:</b> ${esc(entry.orgasm || '—')} · <b>Would repeat:</b> ${esc(entry.wouldRepeat || '—')}</p><p><b>Ratings:</b> My satisfaction ${esc(entry.satisfaction)}/10 · Partner ${esc(entry.partnerSatisfaction)}/10 · Closeness ${esc(entry.emotionalCloseness)}/10 · Communication ${esc(entry.communicationRating)}/10</p>${entry.activities ? `<p><b>Activities:</b> ${esc(entry.activities)}</p>` : ''}${entry.aftercare ? `<p><b>Aftercare:</b> ${esc(entry.aftercare)}</p>` : ''}${entry.healthConcerns ? `<p><b>Health:</b> ${esc(entry.healthConcerns)}</p>` : ''}</div>` : ''}</article>`).join('') : '<div class="empty-box">No journal entries in this filter.</div>'}</div>`;
      }

      function renderContent() {
        const content = $('#content');
        if (state.view === 'settings') {
          content.innerHTML = `<section class="panel"><h2>⚙️ Settings, Install Lock & Backup</h2><div class="settings-grid">
        <article class="settings-card"><h3>🔒 Install Lock Status</h3><p>Installed: <b>Yes</b><br>Lock file: <b>${isFileLockPresent ? 'Found' : 'Database lock'}</b></p><p class="muted">After installation, remove or rename the installer directory. A lock file prevents installer access.</p></article>
        <article class="settings-card"><h3>👤 App & Owner</h3><p>App: ${esc(window.phonebookSettings.appName || 'My Phone Book')}<br>Owner: ${esc(window.phonebookSettings.ownerName || '—')}<br>Database: ${esc(window.phonebookDatabase?.driver?.toUpperCase() || 'SQLite')}${window.phonebookDatabase?.driver === 'mysql' ? ` · ${esc(window.phonebookDatabase.host)}:${esc(window.phonebookDatabase.port)}/${esc(window.phonebookDatabase.name)}` : ` · ${esc(window.phonebookDatabase?.file || 'phonebook.sqlite')}`}</p></article>
        <article class="settings-card"><h3>🎨 Appearance</h3><p>Current theme: <b>${document.documentElement.dataset.theme === 'dark' ? 'Dark' : 'Light'}</b></p>${button(document.documentElement.dataset.theme === 'dark' ? '☀️ Switch to light mode' : '🌙 Switch to dark mode', 'toggle-theme', 'button-light')}</article>
        <article class="settings-card"><h3>💾 Export / Backup</h3><div class="button-row"><a class="button button-violet" href="api.php?action=export&amp;type=json">⬇ JSON Backup (All Data)</a><a class="button button-dark" href="api.php?action=export&amp;type=sqlite">⬇ SQLite SQL</a><a class="button button-blue" href="api.php?action=export&amp;type=mysql">⬇ MySQL Schema</a></div><p class="muted">Keep backups in a secure location. Export files contain private contacts and journal data.</p></article>
        <article class="settings-card"><h3>📥 Import JSON Backup</h3><p>Restore contacts, accounts, journal entries, and app settings from a Phone Book JSON backup.</p><form id="import-backup-form" class="import-form"><label class="field"><span>Backup file</span><input type="file" name="backupFile" accept=".json,application/json" required></label><label class="field"><span>How to import</span><select name="mode"><option value="merge">Merge — add backup records to current data</option><option value="replace">Replace — delete current contacts, accounts, and journal first</option></select></label><button class="button button-primary" type="submit">Import JSON Backup</button></form><p class="muted">Replace keeps your database connection and theme settings. This action cannot be undone.</p></article>
        <article class="settings-card"><h3>🛡️ Privacy</h3><p>This PHP package runs on your XAMPP server. Account passwords and bank fields are stored in the database; secure the host and database file.</p></article>
      </div></section>`;
          $('#import-backup-form').addEventListener('submit', async event => {
            event.preventDefault();
            const form = event.currentTarget;
            const file = form.elements.backupFile.files[0];
            const mode = form.elements.mode.value;
            if (!file) return;
            if (mode === 'replace' && !confirm('Replace the current contacts, accounts, and journal with this backup? This cannot be undone.')) return;
            try {
              const backup = JSON.parse(await file.text());
              const result = await api('import', 'POST', {
                mode,
                backup
              });
              await refresh();
              state.view = 'settings';
              render();
              notify(`Imported ${result.contacts} contacts, ${result.accounts} accounts, and ${result.journalEntries} journal entries.`);
            } catch (error) {
              notify(error instanceof SyntaxError ? 'The selected file is not valid JSON.' : error.message, true);
            }
          });
          return;
        }
        if (state.view === 'xampp') {
          content.innerHTML = `<section class="panel"><h2>🐘 XAMPP / PHP Deployment</h2><p class="muted">This is the native PHP edition of the phone book shown in the preview: contacts, relationship filters, accounts, private journal, search, stats, and backups are included.</p><div class="deploy-grid"><article class="settings-card"><h3>📦 Package Contents</h3><pre>index.php        Full interactive app
api.php          PHP JSON endpoints
config.php       PDO SQLite / MySQL config
assets/css/      App stylesheet
install/         First-run setup
database/        SQLite & MySQL schemas</pre></article><article class="settings-card"><h3>🚀 Deploy Steps</h3><ol><li>Extract the ZIP into <code>htdocs/phonebook/</code>.</li><li>Start Apache in XAMPP (PHP 8.5).</li><li>Open <code>http://localhost/phonebook/</code> and complete setup.</li><li>After setup, remove or rename <code>install/</code>.</li><li>Keep private backups and protect the SQLite database.</li></ol></article></div></section>`;
          return;
        }
        const contact = selectedContact();
        if (!contact) {
          content.innerHTML = `<div class="empty-state"><div class="empty-icon">📖</div><h2>Your phone book is ready</h2><p>Add a contact to manage their details, accounts, and journal.</p>${button('+ Add Contact', 'new-contact', 'button-primary')}</div>`;
          return;
        }
        const tabs = [
          ['overview', '📋 Overview'],
          ['accounts', `🔑 Accounts (${state.accounts.length})`],
          ['journal', `💜 Journal (${state.journal.length})`]
        ];
        content.innerHTML = `<article class="profile-card"><div class="profile-head"><span class="avatar avatar-large" style="background:${esc(contact.avatarColor || '#6366f1')}">${esc((contact.firstName || '?').slice(0, 1))}${esc((contact.lastName || '').slice(0, 1))}</span><div class="profile-name"><h1>${esc(contact.firstName)} ${esc(contact.lastName)} ${contact.isFavorite ? '⭐' : ''}</h1><p>${esc(contact.relationship || 'Friend')}${contact.company ? ` · ${esc(contact.company)}` : ''}${age(contact.dob) !== null ? ` · ${age(contact.dob)} years old` : ''}</p></div><div class="profile-actions">${button('✏️ Edit', `edit-contact:${contact.id}`)}${button('Delete', `delete-contact:${contact.id}`, 'button-danger')}</div></div>
      <div class="detail-tabs">${tabs.map(([key, label]) => `<button class="detail-tab ${state.view === key ? 'active' : ''}" data-view="${key}">${label}</button>`).join('')}</div>
      <div class="tab-content">${state.view === 'overview' ? renderOverview(contact) : state.view === 'accounts' ? renderAccounts(contact) : renderJournal(contact)}</div>
      </article>`;
      }

      function render() {
        renderStats();
        renderFilters();
        renderContacts();
        renderContent();
      }

      function modal(title, body, saveLabel = 'Save') {
        $('#modal-root').innerHTML = `<div class="modal-backdrop" data-action="close-modal"><section class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title"><div class="modal-head"><h2 id="modal-title">${title}</h2><button class="icon-button" data-action="close-modal" aria-label="Close">×</button></div><form id="edit-form">${body}<div class="modal-footer"><button class="button button-primary" type="submit">${saveLabel}</button><button class="button button-light" type="button" data-action="close-modal">Cancel</button></div></form></section></div>`;
        $('#edit-form').addEventListener('submit', event => event.preventDefault());
      }

      function contactForm(contact = {}) {
        const colors = ['#6366f1', '#ec4899', '#14b8a6', '#f59e0b', '#8b5cf6', '#06b6d4', '#f43f5e', '#22c55e'];
        return `<div class="form-grid">${text('First Name *', 'firstName', contact.firstName || '', 'text', 'required')}${text('Last Name', 'lastName', contact.lastName)}${text('Nickname', 'nickname', contact.nickname)}${select('Relationship', 'relationship', relationships, contact.relationship || 'Friend')}
      ${text('Date of Birth', 'dob', dateInput(contact.dob), 'date')}${text('Anniversary', 'anniversary', dateInput(contact.anniversary), 'date')}${text('Email', 'email', contact.email, 'email')}${text('Phone', 'phone', contact.phone, 'tel')}${text('Phone 2', 'phone2', contact.phone2, 'tel')}
      ${select('Gender', 'gender', ['', 'Female', 'Male', 'Non-binary', 'Other', 'Prefer not to say'], contact.gender || '')}${text('Pronouns', 'pronouns', contact.pronouns)}${text('Company', 'company', contact.company)}${text('Job Title', 'jobTitle', contact.jobTitle)}
      ${text('Street Address', 'address', contact.address)}${text('City', 'city', contact.city)}${text('State', 'state', contact.state)}${text('ZIP', 'zip', contact.zip)}${text('Country', 'country', contact.country || 'USA')}${text('Tags (comma separated)', 'tags', contact.tags)}</div>
      <fieldset class="color-field"><legend>Avatar color</legend><div class="color-options">${colors.map(color => `<label><input type="radio" name="avatarColor" value="${color}" ${color === (contact.avatarColor || '#6366f1') ? 'checked' : ''}><span style="background:${color}"></span></label>`).join('')}</div></fieldset>
      ${text('How we met', 'howWeMet', contact.howWeMet)}${area('Notes', 'notes', contact.notes)}${checkbox('⭐ Favorite contact', 'isFavorite', Boolean(contact.isFavorite))}`;
      }

      function openContact(contact = null) {
        modal(contact ? '✏️ Edit Contact' : '+ Add Contact', contactForm(contact || {}), contact ? 'Save Changes' : 'Add Contact');
        $('#edit-form').onsubmit = async event => {
          event.preventDefault();
          const data = formData(event.currentTarget);
          try {
            const saved = await api('contacts', contact ? 'PUT' : 'POST', data, contact?.id || null);
            state.selectedId = Number(saved.id);
            state.view = 'overview';
            closeModal();
            await refresh();
            notify(contact ? 'Contact updated.' : 'Contact added.');
          } catch (error) {
            notify(error.message, true);
          }
        };
      }

      function accountForm(account = {}) {
        return `<div class="form-grid">${text('Account Label *', 'label', account.label || '', 'text', 'required')}${select('Account Type', 'type', accountTypes, account.type || 'Bank')}
      ${text('Username', 'username', account.username)}${text('Email', 'email', account.email || selectedContact()?.email, 'email')}${text('Password', 'password', account.password)}${text('Website / App', 'website', account.website, 'url')}
      <div class="bank-fields form-grid ${account.type === 'Bank' || !account.type ? '' : 'hidden'}">${text('Bank Name', 'bankName', account.bankName)}${select('Account Type', 'accountType', ['Checking', 'Savings', 'Money Market', 'Credit Card', 'Joint Checking'], account.accountType || 'Checking')}${text('Routing Number (9 digits)', 'routingNumber', account.routingNumber, 'text', 'maxlength="12" inputmode="numeric"')}${text('Account Number', 'accountNumber', account.accountNumber)}</div></div>
      ${area('Notes', 'notes', account.notes)}${checkbox('Primary account for this person', 'isPrimary', Boolean(account.isPrimary))}`;
      }

      function openAccount(account = null) {
        modal(account ? '✏️ Edit Account' : '🔑 Add Account', accountForm(account || {}), 'Save Account');
        const form = $('#edit-form');
        form.querySelector('[name="type"]').addEventListener('change', event => form.querySelector('.bank-fields').classList.toggle('hidden', event.target.value !== 'Bank'));
        form.onsubmit = async event => {
          event.preventDefault();
          const data = formData(form);
          data.contactId = selectedContact().id;
          if (data.type === 'Bank' && data.routingNumber && data.routingNumber.replace(/\D/g, '').length !== 9) {
            notify('Routing number must contain 9 digits.', true);
            return;
          }
          try {
            await api('accounts', account ? 'PUT' : 'POST', data, account?.id || null);
            closeModal();
            await refresh();
            state.view = 'accounts';
            render();
            notify(account ? 'Account updated.' : 'Account added.');
          } catch (error) {
            notify(error.message, true);
          }
        };
      }

      function journalForm() {
        const intimacyTypes = ['Vaginal', 'Oral', 'Anal', 'Manual / Fingering', 'Mutual Masturbation', 'Intercourse + Oral', 'Other'];
        return `<div class="form-grid">${text('Date *', 'entryDate', new Date().toISOString().slice(0, 10), 'date', 'required')}${text('Title *', 'title', '', 'text', 'required')}
      ${select('Entry Type', 'entryType', ['Note', 'Date', 'Romantic', 'Conversation', 'Health', 'Intimacy'], 'Note')}${text('Overall Rating (1–10)', 'rating', '8', 'number', 'min="0" max="10"')}${text('Location', 'location')}${text('Mood', 'mood')}</div>
      ${area('Notes / What happened', 'notes')}
      <div class="intimate-toggle">${checkbox(`💜 Was this an intimate / sexual encounter with ${esc(selectedContact().firstName)}?`, 'isIntimate')}</div>
      <section class="intimate-fields hidden"><h3>💜 Intimacy Questionnaire</h3><div class="form-grid">
        ${select('Type of intimacy', 'intimacyType', intimacyTypes, 'Vaginal')}${text('Duration (minutes)', 'durationMinutes', '30', 'number', 'min="0"')}
        ${select('Protection / Contraception', 'protectionUsed', ['Condom', 'Birth Control Pill', 'IUD', 'Implant', 'Patch / Ring', 'Pull-out', 'None', 'Other'], 'Condom')}${text('Contraception notes', 'contraceptionNotes')}
        ${select('Orgasm', 'orgasm', ['Both', 'Me', 'Partner', 'Neither', 'Prefer not to say'], 'Both')}${select('Would you repeat?', 'wouldRepeat', ['Yes', 'Maybe', 'No'], 'Yes')}
        ${text('My satisfaction (1–10)', 'satisfaction', '8', 'number', 'min="0" max="10"')}${text('Partner satisfaction (1–10)', 'partnerSatisfaction', '8', 'number', 'min="0" max="10"')}
        ${text('Emotional closeness (1–10)', 'emotionalCloseness', '8', 'number', 'min="0" max="10"')}${text('Communication (1–10)', 'communicationRating', '8', 'number', 'min="0" max="10"')}
        ${text('Mood before', 'moodBefore', 'Relaxed')}${text('Mood after', 'moodAfter', 'Happy')}</div>
        ${checkbox('Consent was clear and enthusiastic from both', 'consentConfirmed', true)}
        ${area('Activities', 'activities', '', 2)}${area('Positions / details', 'positionNotes', '', 2)}${area('Aftercare', 'aftercare', '', 2)}
        ${area('Health concerns', 'healthConcerns', '', 2)}${area('What went well?', 'whatWentWell', '', 2)}${area('What to improve next time?', 'whatToImprove', '', 2)}
        ${checkbox('STI tested recently (either partner)', 'stiTestedRecent')}</section>`;
      }

      function openJournal() {
        modal(`📓 New Journal Entry — ${esc(selectedContact().firstName)}`, journalForm(), 'Save Entry');
        const form = $('#edit-form');
        form.querySelector('[name="isIntimate"]').addEventListener('change', event => {
          form.querySelector('.intimate-fields').classList.toggle('hidden', !event.target.checked);
          if (event.target.checked) form.querySelector('[name="entryType"]').value = 'Intimacy';
        });
        form.onsubmit = async event => {
          event.preventDefault();
          const data = formData(form);
          data.contactId = selectedContact().id;
          data.isPrivate = true;
          if (data.isIntimate && !data.consentConfirmed && !confirm('Consent was not confirmed. Save this record anyway?')) return;
          try {
            await api('journal', 'POST', data);
            closeModal();
            await refresh();
            state.view = 'journal';
            render();
            notify('Journal entry saved.');
          } catch (error) {
            notify(error.message, true);
          }
        };
      }

      function formData(form) {
        const data = Object.fromEntries(new FormData(form).entries());
        form.querySelectorAll('input[type="checkbox"]').forEach(input => {
          data[input.name] = input.checked;
        });
        ['rating', 'durationMinutes', 'satisfaction', 'partnerSatisfaction', 'emotionalCloseness', 'communicationRating'].forEach(key => {
          if (key in data) data[key] = Number(data[key]);
        });
        return data;
      }

      function closeModal() {
        $('#modal-root').innerHTML = '';
      }

      async function onClick(event) {
        const viewButton = event.target.closest('[data-view]');
        if (viewButton) {
          state.view = viewButton.dataset.view;
          render();
          return;
        }
        const filterButton = event.target.closest('[data-filter]');
        if (filterButton) {
          state.filter = filterButton.dataset.filter;
          render();
          return;
        }
        const journalFilter = event.target.closest('[data-journal-filter]');
        if (journalFilter) {
          state.journalFilter = journalFilter.dataset.journalFilter;
          render();
          return;
        }
        const selectButton = event.target.closest('[data-select]');
        if (selectButton) {
          state.selectedId = Number(selectButton.dataset.select);
          state.view = 'overview';
          await loadDetail();
          render();
          return;
        }
        const copy = event.target.closest('[data-copy]');
        if (copy) {
          try {
            await navigator.clipboard.writeText(copy.dataset.copy);
            notify('Copied to clipboard.');
          } catch {
            notify('Clipboard access is unavailable in this browser.', true);
          }
          return;
        }
        const action = event.target.closest('[data-action]')?.dataset.action;
        if (!action) return;
        if (action === 'close-modal') {
          if (event.target.classList.contains('modal-backdrop') || event.target.closest('.icon-button, [type="button"]')) closeModal();
          return;
        }
        if (action === 'new-contact') {
          openContact();
          return;
        }
        if (action === 'toggle-theme') {
          const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
          try {
            await api('settings', 'POST', {
              theme: nextTheme
            });
            document.documentElement.dataset.theme = nextTheme;
            updateThemeButton();
            notify(`${nextTheme === 'dark' ? 'Dark' : 'Light'} mode enabled.`);
            render();
          } catch (error) {
            notify(error.message, true);
          }
          return;
        }
        if (action.startsWith('edit-contact:')) {
          openContact(state.contacts.find(item => Number(item.id) === Number(action.split(':')[1])));
          return;
        }
        if (action.startsWith('delete-contact:')) {
          const id = action.split(':')[1];
          if (!confirm('Delete this contact and all accounts and journal entries?')) return;
          try {
            await api('contacts', 'DELETE', null, id);
            state.selectedId = null;
            await refresh();
            notify('Contact deleted.');
          } catch (error) {
            notify(error.message, true);
          }
          return;
        }
        if (action === 'new-account') {
          openAccount();
          return;
        }
        if (action.startsWith('edit-account:')) {
          openAccount(state.accounts.find(item => Number(item.id) === Number(action.split(':')[1])));
          return;
        }
        if (action.startsWith('delete-account:')) {
          if (!confirm('Delete this account?')) return;
          try {
            await api('accounts', 'DELETE', null, action.split(':')[1]);
            await refresh();
            state.view = 'accounts';
            render();
            notify('Account deleted.');
          } catch (error) {
            notify(error.message, true);
          }
          return;
        }
        if (action === 'new-journal') {
          openJournal();
          return;
        }
        if (action.startsWith('delete-journal:')) {
          if (!confirm('Delete this journal entry?')) return;
          try {
            await api('journal', 'DELETE', null, action.split(':')[1]);
            await refresh();
            state.view = 'journal';
            render();
            notify('Journal entry deleted.');
          } catch (error) {
            notify(error.message, true);
          }
          return;
        }
        if (action.startsWith('toggle-password:')) {
          const id = action.split(':')[1];
          state.showPasswords[id] = !state.showPasswords[id];
          render();
          return;
        }
        if (action === 'view-accounts' || action === 'view-journal') {
          state.view = action === 'view-accounts' ? 'accounts' : 'journal';
          render();
        }
      }

      let isFileLockPresent = false;
      let searchTimer;

      function updateThemeButton() {
        const toggle = $('#theme-toggle');
        if (toggle) toggle.textContent = document.documentElement.dataset.theme === 'dark' ? '☀️ Light mode' : '🌙 Dark mode';
      }
      window.phonebookSettings = {};
      window.phonebookDatabase = {};
      $('#search').addEventListener('input', event => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(async () => {
          state.search = event.target.value.trim();
          try {
            await refresh();
          } catch (error) {
            notify(error.message, true);
          }
        }, 250);
      });
      document.body.addEventListener('click', event => {
        onClick(event).catch(error => notify(error.message, true));
      });
      api('settings').then(settings => {
        window.phonebookSettings = settings.settings || {};
        window.phonebookDatabase = settings.database || {};
        document.documentElement.dataset.theme = settings.settings.theme === 'dark' ? 'dark' : 'light';
        updateThemeButton();
        isFileLockPresent = settings.lockFileExists;
        return refresh();
      }).catch(error => {
        $('#content').innerHTML = `<div class="error-state"><h2>Could not load Phone Book</h2><p>${esc(error.message)}</p><a href="install/">Open installer</a></div>`;
      });
    })();
  </script>
</body>

</html>