import { people, rules, driverFor, signed, calculate } from './scoring.mjs';

const el = id => document.getElementById(id);
const key = 'delivery-game-challenge-v2';
const status = el('status');
const parked = el('parked');
const shift = el('shift');
const editor = el('editor');
const confirmation = el('confirmation');
let events = [];
let state;
let editIndex;
let saved = true;
let readFailed = false;

function load() {
  try {
    const raw = localStorage.getItem(key);
    const data = raw === null ? [] : JSON.parse(raw);
    if (!Array.isArray(data) || !data.every(event => event && (
      event.type === 'order' || (event.type === 'score' && people.includes(event.player) && Object.hasOwn(rules, event.reason))
    ))) throw new Error('Invalid shift');
    events = data;
  } catch {
    readFailed = true;
    status.textContent = 'The saved shift could not be read. Use Reset shift to start over.';
  }
}

function save(next, message, reset = false) {
  if (!parked.checked || (readFailed && !reset)) return;
  try {
    localStorage.setItem(key, JSON.stringify(next));
    saved = true;
    readFailed = false;
  } catch {
    saved = false;
    if (readFailed) {
      status.textContent = 'Reset failed. This browser could not save the shift.';
      render();
      return;
    }
  }
  events = next;
  status.textContent = saved ? message : message + ' · Local save failed';
  render();
}

const scoreButtons = Object.entries(rules).map(([reason, points]) => {
  const button = document.createElement('button');
  const value = document.createElement('span');
  button.append(document.createTextNode(reason + ' '), value);
  button.onclick = () => {
    const player = driverFor(state.completed);

    save([...events, { type: 'score', player, reason }], player + ' ' + signed(points));
  };
  el(reason === 'Something genuinely impressive' ? 'chaos' : points < 0 ? 'penalties' : 'bonuses').append(button);
  el('edit-reason').add(new Option(reason + ' (' + signed(points) + ')', reason));
  value.textContent = signed(points);
  return button;
});

function renderReport() {
  el('summary').textContent = state.completed + ' completed · ' + people.map(player =>
    player + ' ' + state.totals[player] + ' pts / ' + state.deliveries[player] + ' orders'
  ).join(' · ') + (saved && !readFailed ? '' : ' · Local save unavailable');
  const leader = [...people].sort((a, b) => state.totals[b] - state.totals[a])[0];
  if (state.completed) {
    el('summary').textContent += state.totals.John === state.totals.Sierra
      ? ' · Delivery Champion: tied'
      : ' · Delivery Champion if the shift ends now: ' + leader;
  }
  const history = el('history');
  history.replaceChildren();
  if (!state.entries.length) history.textContent = 'No activity yet.';
  for (let index = state.entries.length - 1; index >= 0; index -= 1) {
    const entry = state.entries[index];
    const row = document.createElement('div');
    row.className = 'entry';
    const description = document.createElement('div');
    const meta = document.createElement('small');
    description.textContent = entry.label;
    meta.textContent = 'Order ' + entry.order + ' · ' + entry.player + (entry.points ? ' · ' + signed(entry.points) : '');
    description.append(meta);
    row.append(description);
    if (entry.type === 'score') {
      const edit = document.createElement('button');
      edit.textContent = 'Edit';
      edit.disabled = !parked.checked || readFailed;
      edit.setAttribute('aria-label', 'Edit ' + entry.label + ', order ' + entry.order);
      edit.onclick = () => {
        editIndex = index;
        el('edit-player').value = entry.player;
        el('edit-reason').value = entry.reason;
        editor.showModal();
      };
      row.append(edit);
    }
    history.append(row);
  }
}

function render() {
  el('scoresheet').hidden = !parked.checked;
  state = calculate(events);
  const player = driverFor(state.completed);
  const switchOrder = state.completed + 4 - state.completed % 3;
  const canScore = parked.checked && !readFailed;
  el('order').textContent = 'Order ' + (state.completed + 1) + ' · ' + state.completed + ' completed';
  el('driver').textContent = player + ' is driving';
  el('next').textContent = 'Next driver: ' + driverFor(switchOrder - 1) + ' on order ' + switchOrder;
  for (const name of people) el(name.toLowerCase()).textContent = state.totals[name];
  for (const button of scoreButtons) button.disabled = !canScore;
  el('complete').disabled = !canScore;
  el('undo').disabled = !canScore || !events.length;
  el('reset').disabled = !parked.checked;
  if (shift.open) renderReport();
}

parked.onchange = () => {
  status.textContent = parked.checked ? 'Scoring unlocked' : 'Check the box to unlock scoring.';
  render();
};
document.addEventListener('visibilitychange', () => {
  if (document.hidden) {
    parked.checked = false;
    editor.close();
    confirmation.close();
    shift.close();
    render();
  }
});
window.addEventListener('storage', event => {
  if (event.key === key || event.key === null) {
    readFailed = false;
    saved = true;
    load();
    editor.close();
    confirmation.close();
    shift.close();
    render();
  }
});
el('complete').onclick = () => {
  save([...events, { type: 'order' }], 'Delivery completed · ' + driverFor(state.completed) + ' +10');
  parked.checked = false;
  render();
};
el('undo').onclick = () => save(events.slice(0, -1), 'Last action undone');
el('report').onclick = () => { renderReport(); shift.showModal(); };
el('close').onclick = () => shift.close();
el('cancel').onclick = () => editor.close();
el('edit-form').onsubmit = event => {
  event.preventDefault();
  const next = events.slice();
  next[editIndex] = { type: 'score', player: el('edit-player').value, reason: el('edit-reason').value };
  editor.close();
  save(next, 'Score updated');
};
el('reset').onclick = () => confirmation.showModal();
el('keep').onclick = () => confirmation.close();
el('erase').onclick = () => {
  confirmation.close();
  save([], 'Shift reset', true);
  shift.close();
};
load();
render();
if ('serviceWorker' in navigator) navigator.serviceWorker.register('./sw.js').catch(error => console.error('Offline setup failed', error));


