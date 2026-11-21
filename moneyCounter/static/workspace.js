const root = document.querySelector("#workspace");
const $ = (selector, parent = document) => parent.querySelector(selector);
const $$ = (selector, parent = document) => [...parent.querySelectorAll(selector)];
const view = root.dataset.view;
const canWrite = root.dataset.canWrite === "1";
const url = new URL(location.href);
const drawerSelect = $("#drawer-select");
const status = $("#drawer-status");
const notice = $("#notice");
const countForm = $("#count-form");
const denomRows = $$("[data-code]");
const csrf = $('meta[name="csrf-token"]').content;
const time = new Intl.DateTimeFormat(undefined, {
  dateStyle: "medium", timeStyle: "short", timeZone: root.dataset.timezone,
});
const dollars = new Intl.NumberFormat("en-US");
const modeLabels = {
  opening: "Starting till", current: "Current count", deposit: "Bank deposit", closing: "Closing count",
};
const countLabels = { ...modeLabels, after_deposit: "After deposit" };
const movementLabels = {
  sale: "Sale", deposit: "Deposit", payout: "Payout", withdrawal: "Withdrawal",
  addition: "Addition", adjustment: "Adjustment",
};

const tripParam = url.searchParams.get("shopping_trip");
const amountParam = url.searchParams.get("amount");
let shoppingTrip = /^[1-9]\d{0,19}$/.test(tripParam || "") && /^\d{1,9}\.\d{2}$/.test(amountParam || "")
  ? tripParam : null;
let drawer;
let rollDefaults;
let nextBefore;
let mode = url.searchParams.get("mode");
let pendingRequest;
let loading = false;
let saving = false;

function money(cents) {
  const absolute = Math.abs(cents);
  return (cents < 0 ? "-" : "") + "$" + dollars.format(Math.floor(absolute / 100)) +
    "." + String(absolute % 100).padStart(2, "0");
}

function tell(message = "", kind = "info") {
  notice.hidden = !message;
  notice.textContent = message;
  notice.className = "notice " + kind;
}

function element(tag, content, className) {
  const node = document.createElement(tag);
  if (content !== undefined) node.textContent = content;
  if (className) node.className = className;
  return node;
}

async function request(path = "", { method = "GET", payload } = {}) {
  const headers = { Accept: "application/json" };
  let body;
  if (payload) {
    headers["Content-Type"] = "application/json";
    headers["X-CSRF-Token"] = csrf;
    body = JSON.stringify(payload);
    const fingerprint = JSON.stringify([method, path, body]);
    if (pendingRequest?.fingerprint !== fingerprint) {
      const random = crypto.getRandomValues(new Uint8Array(16));
      const key = Array.from(random, byte => byte.toString(16).padStart(2, "0")).join("");
      pendingRequest = { fingerprint, key };
    }
    headers["Idempotency-Key"] = pendingRequest.key;
  }
  let response;
  try {
    const endpoint = path === "settings" ? root.dataset.settings : root.dataset.api + path;
    response = await fetch(endpoint, { method, headers, body });
  } catch {
    throw new Error("The server could not be reached. Retry the same action to check whether it was saved.");
  }
  const result = await response.json().catch(() => ({}));
  if (!response.ok) {
    if (response.status < 500 && payload) pendingRequest = undefined;
    throw new Error(result.error || "The request could not be completed. Retry shortly.");
  }
  if (payload) pendingRequest = undefined;
  return result;
}

function setBusy() {
  if (drawerSelect) {
    drawerSelect.disabled = loading || saving || !drawerSelect.options.length;
    $("#more-drawers").disabled = loading || saving;
    $("#refresh-drawer").disabled = loading || saving;
  }
  $$("[data-mode]").forEach(button => { button.disabled = loading || saving; });
}

function addDrawerOption(item) {
  const key = String(item.id);
  if ([...drawerSelect.options].some(option => option.value === key)) return;
  const option = element("option", item.name + " · " + item.business_date +
    (item.closed_at ? " · closed" : ""));
  option.value = key;
  drawerSelect.append(option);
}

async function loadList(append = false) {
  const selected = drawerSelect.value;
  const list = await request(append ? "?before=" + nextBefore : "");
  if (!append) drawerSelect.replaceChildren();
  list.drawers.forEach(addDrawerOption);
  nextBefore = list.next_before;
  $("#more-drawers").hidden = !nextBefore;
  if (append && selected) drawerSelect.value = selected;
  return list.drawers;
}

function renderSelection() {
  status.hidden = !drawer;
  $("#refresh-drawer").hidden = !drawer;
  if (!drawer) return;
  status.textContent = drawer.closed_at ? "Closed" : "Open";
  status.className = "badge " + (drawer.closed_at ? "closed" : "open");
  for (const link of $$("[data-drawer-link], #count-link, [data-nav='overview'], [data-nav='activity'], [data-nav='count']")) {
    const next = new URL(link.href);
    next.searchParams.set("drawer", drawer.id);
    link.href = next;
  }
  if ($("#count-link")) $("#count-link").hidden = Boolean(drawer.closed_at);
}

function displayTransactions(target, rows, compact) {
  target.replaceChildren();
  if (!rows.length) {
    target.append(element("p", "No cash movements recorded.", "muted"));
    return;
  }
  const list = element("ul", undefined, "event-list");
  for (const row of rows) {
    const item = element("li", undefined, "event");
    const info = element("div", undefined, "event-info");
    info.append(element("strong", movementLabels[row.kind]), element("small",
      time.format(new Date(row.created_at)) + (compact ? "" : " · " + row.username)));
    if (!compact && row.note) info.append(element("p", row.note, "muted"));
    const outflow = ["deposit", "withdrawal", "payout"].includes(row.kind);
    const amount = outflow ? -row.amount_cents : row.amount_cents;
    item.append(element("span", outflow ? "↘" : "↗", "metric-icon " + (outflow ? "amber" : "sage")),
      info, element("strong", (amount > 0 ? "+" : "") + money(amount), amount < 0 ? "negative" : "positive"));
    if (row.quantities && !compact) {
      const details = element("details", undefined, "event-details");
      details.append(element("summary", "Deposit denominations"), breakdown(row.quantities));
      item.append(details);
    }
    list.append(item);
  }
  target.append(list);
}

function breakdown(quantities) {
  const list = element("ul", undefined, "breakdown");
  for (const [code, fields] of Object.entries(quantities)) {
    if (!fields.loose && !fields.rolls) continue;
    const labels = {
      penny: "Penny", nickel: "Nickel", dime: "Dime", quarter: "Quarter", half: "Half dollar",
      dollar_coin: "Dollar coin",
    };
    const label = labels[code] || "$" + code.slice(5) + " bill";
    const pieces = [fields.loose ? fields.loose + " loose" : "",
      fields.rolls ? fields.rolls + " rolls × " + drawer.roll_sizes[code] : ""].filter(Boolean);
    list.append(element("li", label + " · " + pieces.join(" + ")));
  }
  if (!list.children.length) list.append(element("li", "Zero cash counted."));
  return list;
}

function displayCounts(target, rows) {
  target.replaceChildren();
  for (const row of [...rows].reverse()) {
    const details = element("details", undefined, "count-event");
    const title = element("summary");
    title.append(element("strong", countLabels[row.kind]), element("span", money(row.total_cents)));
    details.append(title, element("p", time.format(new Date(row.created_at)) + " · " + row.username, "muted"),
      breakdown(row.quantities));
    target.append(details);
  }
}

async function renderDrawer() {
  renderSelection();
  const empty = $("#no-drawer");
  if (empty) empty.hidden = Boolean(drawer) || (view === "count" && mode === "opening");
  if (view === "count") {
    renderCount();
    return;
  }
  const panel = view === "overview" ? $("#dashboard") : $("#activity");
  panel.hidden = !drawer;
  if (!drawer) return;
  if (view === "overview") {
    $("#drawer-name").textContent = drawer.name;
    $("#last-count").textContent = "Last counted " + time.format(new Date(drawer.counted_at));
    for (const node of $$("[data-value]")) {
      const key = node.dataset.value;
      const value = drawer[key] ?? drawer.totals[key];
      node.textContent = money(value);
      if (key === "variance_cents") setVariance(node, value);
    }
    if ($("#movement-fields")) {
      $("#movement-form").hidden = Boolean(drawer.closed_at);
      $("#closed-message").hidden = !drawer.closed_at;
    }
    const target = $("#recent-activity");
    target.textContent = "Loading activity…";
    const { items } = await request("/" + drawer.id + "/transactions");
    displayTransactions(target, items.slice(-5).reverse(), true);
  } else {
    const txTarget = $("#full-activity");
    const countTarget = $("#count-history");
    txTarget.textContent = "Loading movements…";
    countTarget.textContent = "Loading counts…";
    const [transactions, counts] = await Promise.all([
      request("/" + drawer.id + "/transactions"), request("/" + drawer.id + "/counts"),
    ]);
    displayTransactions(txTarget, transactions.items.reverse(), false);
    displayCounts(countTarget, counts.items);
  }
}

function setVariance(node, value) {
  node.classList.toggle("negative", value < 0);
  node.classList.toggle("positive", value > 0);
}

function renderCount() {
  if (!canWrite || (drawer?.closed_at && mode !== "opening")) mode = "current";
  if (!Object.hasOwn(modeLabels, mode)) mode = drawer || !canWrite ? "current" : "opening";
  const opening = mode === "opening";
  const deposit = mode === "deposit";
  const closing = mode === "closing";
  const readOnly = !canWrite || (!opening && drawer?.closed_at);
  $("#no-drawer").hidden = Boolean(drawer) || opening;
  countForm.hidden = !opening && !drawer;
  for (const button of $$("[data-mode]")) {
    button.setAttribute("aria-pressed", String(button.dataset.mode === mode));
    button.hidden = Boolean(drawer?.closed_at) && !["current", "opening"].includes(button.dataset.mode);
  }
  $("#opening-fields").hidden = !opening;
  $("#deposit-note").hidden = !deposit;
  $("#close-confirm").hidden = !closing;
  $("#save-count").hidden = Boolean(readOnly);
  $("#denomination-fields").disabled = Boolean(readOnly);
  countForm.elements.name.required = opening;
  countForm.elements.business_date.required = opening;
  countForm.elements.confirmed.required = closing;
  countForm.elements.confirmed.checked = false;
  $("#count-heading").textContent = modeLabels[mode];
  $("#save-count").textContent = {
    opening: "Open drawer", current: "Save count", deposit: "Record deposit", closing: "Close drawer",
  }[mode];
  $("#count-reference").hidden = !(deposit || closing);
  $("#reference-label").textContent = deposit ? "Available cash" : "Expected cash";
  $("#reference-total").textContent = drawer ? money(deposit ? drawer.actual_cents : drawer.expected_cents) : "";
  $("#variance-row").hidden = !closing;
  $("#count-help").textContent = {
    opening: "This count becomes the opening balance for the drawer session.",
    current: "Save a fresh count after cash moves to update the physical balance.",
    deposit: "Enter cash being removed for deposit. Amounts are checked against the latest count.",
    closing: "Verify the remaining cash. Closing locks this session and records the final difference.",
  }[mode];
  if (readOnly) $("#count-help").textContent = "Showing the latest saved denomination count.";
  const rolls = opening ? rollDefaults : drawer?.roll_sizes;
  for (const row of denomRows) {
    const code = row.dataset.code;
    const selected = !opening && !deposit ? drawer?.quantities[code] : undefined;
    row.dataset.rollSize = rolls?.[code] || 0;
    for (const input of $$("[data-quantity]", row)) {
      input.value = selected?.[input.dataset.quantity] || "";
      input.max = deposit ? drawer?.quantities[code][input.dataset.quantity] ?? 0 : 999999;
    }
    const rollLabel = $("[data-roll-label]", row);
    if (rollLabel) rollLabel.textContent = row.dataset.rollSize + " / roll";
  }
  updateDraft();
}

function quantityValue(input) {
  return input && input.validity.valid ? Number(input.value) : 0;
}

function updateDraft() {
  let coin = 0;
  let bill = 0;
  for (const row of denomRows) {
    const size = Number(row.dataset.rollSize);
    const loose = quantityValue($('[data-quantity="loose"]', row));
    const rolls = quantityValue($('[data-quantity="rolls"]', row));
    const subtotal = (loose + rolls * size) * Number(row.dataset.cents);
    $(".subtotal", row).value = money(subtotal);
    if (size) coin += subtotal;
    else bill += subtotal;
  }
  const total = coin + bill;
  $("#coin-total").textContent = money(coin);
  $("#bill-total").textContent = money(bill);
  $("#draft-total").value = money(total);
  if (mode === "closing" && drawer) {
    const difference = total - drawer.expected_cents;
    const output = $("#draft-variance");
    output.textContent = money(difference);
    setVariance(output, difference);
  }
}

function readCount() {
  return Object.fromEntries(denomRows.map(row => [
    row.dataset.code, Object.fromEntries($$("[data-quantity]", row).map(input =>
      [input.dataset.quantity, Number(input.value)])),
  ]));
}

async function loadDrawer(id) {
  loading = true;
  setBusy();
  try {
    drawer = id ? await request("/" + id) : undefined;
    if (drawer) {
      addDrawerOption(drawer);
      drawerSelect.value = String(drawer.id);
      url.searchParams.set("drawer", drawer.id);
    } else {
      url.searchParams.delete("drawer");
    }
    history.replaceState(null, "", url);
    await renderDrawer();
  } catch (error) {
    drawerSelect.value = drawer?.id ?? "";
    tell(error.message, "error");
  } finally {
    loading = false;
    setBusy();
  }
}

function bindMutation(form, action) {
  form?.addEventListener("submit", async event => {
    event.preventDefault();
    if (saving || loading) return;
    const button = event.submitter || $('button[type="submit"]', form);
    const oldText = button.textContent;
    button.disabled = true;
    button.textContent = button.dataset.busy || "Saving…";
    form.setAttribute("aria-busy", "true");
    saving = true;
    setBusy();
    tell();
    try {
      await action(form);
    } catch (error) {
      tell(error.message, "error");
    } finally {
      button.disabled = false;
      if (button.textContent === button.dataset.busy) button.textContent = oldText;
      form.removeAttribute("aria-busy");
      saving = false;
      setBusy();
    }
  });
}

function amountInCents(value) {
  const match = /^(-?)\$?(\d{1,9}|\d{1,3}(?:,\d{3}){1,2})?(?:\.(\d{1,2}))?$/.exec(value.trim());
  if (!match || (!match[2] && !match[3])) throw new Error("Enter an amount like 12.34, with up to two decimal places.");
  const cents = Number((match[2] || "").replaceAll(",", "")) * 100 + Number((match[3] || "").padEnd(2, "0"));
  return match[1] ? -cents : cents;
}

function bindDrawer() {
  drawerSelect.addEventListener("change", () => {
    tell();
    if (mode === "opening") {
      mode = "current";
      url.searchParams.set("mode", mode);
    }
    loadDrawer(drawerSelect.value);
  });
  $("#more-drawers").addEventListener("click", async () => {
    loading = true;
    setBusy();
    try { await loadList(true); } catch (error) { tell(error.message, "error"); }
    finally { loading = false; setBusy(); }
  });
  $("#refresh-drawer").addEventListener("click", () => {
    tell();
    loadDrawer(drawerSelect.value);
  });

  const movementForm = $("#movement-form");
  if (shoppingTrip && movementForm) {
    movementForm.elements.kind.value = "payout";
    movementForm.elements.amount.value = amountParam;
    movementForm.elements.note.value = "Shopping Manager trip #" + shoppingTrip;
    movementForm.elements.kind.disabled = true;
    tell("Review the cash amount and select the drawer that paid for this shopping trip.");
  }

  bindMutation(movementForm, async form => {
    const payload = {
      kind: form.elements.kind.value, amount_cents: amountInCents(form.elements.amount.value),
      note: form.elements.note.value, version: drawer.version,
    };
    if (shoppingTrip) payload.shopping_trip = shoppingTrip;
    drawer = await request("/" + drawer.id + "/transactions", { method: "POST", payload });
    if (shoppingTrip) {
      shoppingTrip = null;
      url.searchParams.delete("shopping_trip");
      url.searchParams.delete("amount");
      history.replaceState(null, "", url);
      form.elements.kind.disabled = false;
    }
    form.reset();
    tell("Cash movement recorded.", "success");
    await renderDrawer();
  });

  for (const button of $$("[data-mode]")) {
    button.addEventListener("click", () => {
      mode = button.dataset.mode;
      url.searchParams.set("mode", mode);
      history.replaceState(null, "", url);
      tell();
      renderCount();
    });
  }
  countForm?.addEventListener("input", updateDraft);
  bindMutation(countForm, async form => {
    const payload = { quantities: readCount() };
    let path = "";
    let method = "POST";
    const savedMode = mode;
    if (mode === "opening") {
      payload.name = form.elements.name.value;
      payload.business_date = form.elements.business_date.value;
    } else {
      payload.version = drawer.version;
      path = "/" + drawer.id + { current: "/count", deposit: "/deposits", closing: "/close" }[mode];
      if (mode === "current") method = "PATCH";
      if (mode === "deposit") payload.note = form.elements.deposit_note.value;
    }
    drawer = await request(path, { method, payload });
    mode = "current";
    url.searchParams.set("mode", mode);
    url.searchParams.set("drawer", drawer.id);
    history.replaceState(null, "", url);
    if (savedMode === "opening") form.elements.name.value = "";
    form.elements.deposit_note.value = "";
    renderSelection();
    renderCount();
    tell({ opening: "Drawer opened.", current: "Count saved.", deposit: "Deposit recorded.",
      closing: "Drawer closed. Final difference: " + money(drawer.variance_cents) + "." }[savedMode], "success");
    try {
      await loadList();
    } catch {
      tell("Saved. The drawer list could not be refreshed.", "success");
    }
    addDrawerOption(drawer);
    drawerSelect.value = String(drawer.id);
  });
}

async function init() {
  if (view === "settings") {
    const form = $("#settings-form");
    const result = await request("settings");
    const rows = $$(".roll-setting", form);
    function showRollValues() {
      for (const row of rows) {
        $("output", row).value = money(quantityValue($("input", row)) * Number(row.dataset.cents));
      }
    }
    for (const [code, size] of Object.entries(result.roll_sizes)) form.elements[code].value = size;
    showRollValues();
    form.hidden = false;
    form.addEventListener("input", showRollValues);
    bindMutation(form, async () => {
      const roll_sizes = Object.fromEntries(rows.map(row => {
        const field = $("input", row);
        return [field.name, Number(field.value)];
      }));
      await request("settings", { method: "PATCH", payload: { roll_sizes } });
      tell("Roll sizes saved for new drawers.", "success");
    });
    return;
  }
  loading = true;
  bindDrawer();
  setBusy();
  try {
    const drawers = await loadList();
    if (view === "count") {
      const settings = await request("settings");
      rollDefaults = settings.roll_sizes;
    }
    const requested = url.searchParams.get("drawer");
    const id = requested && /^[1-9]\d{0,14}$/.test(requested) ? requested :
      (drawers.find(item => !item.closed_at) || drawers[0])?.id;
    $("#refresh-drawer").hidden = !id;
    await loadDrawer(id);
  } finally {
    loading = false;
    setBusy();
  }
}

init().catch(error => tell(error.message, "error"));
