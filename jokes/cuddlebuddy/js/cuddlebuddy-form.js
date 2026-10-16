(function () {
    'use strict';

    const form = document.getElementById('cuddleForm');
    const historySelect = document.getElementById('historySelect');
    const recordId = document.getElementById('recordId');
    const status = document.getElementById('saveStatus');
    const allowanceTotal = document.getElementById('allowanceTotal');
    const cuddleTypeTotal = document.getElementById('cuddleTypeTotal');
    const endpointUrl = 'index.php';
    let saveTimer = null;
    let saving = false;
    let savePending = false;
    let loading = false;

    function setStatus(message, state) {
        status.textContent = message;
        status.dataset.state = state || '';
    }

    function updateTotals() {
        allowanceTotal.value = form.querySelectorAll('input[name="allowances[]"]:checked').length;
        cuddleTypeTotal.value = form.querySelectorAll('input[name="cuddle_types[]"]:checked').length;
    }

    function readForm() {
        const data = {};
        new FormData(form).forEach(function (value, key) {
            if (key === 'record_id') {
                return;
            }

            if (Object.prototype.hasOwnProperty.call(data, key)) {
                if (!Array.isArray(data[key])) {
                    data[key] = [data[key]];
                }
                data[key].push(value);
            } else {
                data[key] = value;
            }
        });
        return data;
    }

    function addHistoryOption(application) {
        const id = String(application.id);
        let option = Array.from(historySelect.options).find(function (candidate) {
            return candidate.value === id;
        });

        if (!option) {
            option = document.createElement('option');
            option.value = id;
            historySelect.appendChild(option);
        }

        option.textContent = application.full_name + ' — ' + application.application_date;
        option.dataset.name = application.full_name;
        option.dataset.date = application.application_date;
    }

    async function loadHistory() {
        const response = await fetch(endpointUrl + '?action=history', { cache: 'no-store' });
        const result = await response.json();
        if (!response.ok || !result.success || !Array.isArray(result.applications)) {
            throw new Error(result.message || 'Could not retrieve application history.');
        }

        const selectedId = recordId.value;
        historySelect.replaceChildren(new Option('Choose an application to reload or edit', ''));
        result.applications.forEach(addHistoryOption);
        historySelect.value = selectedId;
    }

    function restoreForm(data) {
        loading = true;
        form.reset();

        Object.entries(data).forEach(function (entry) {
            const name = entry[0];
            const value = entry[1];
            const controls = Array.from(form.querySelectorAll('[name]')).filter(function (control) {
                return control.name === name;
            });
            if (!controls.length) {
                return;
            }

            const values = Array.isArray(value) ? value.map(String) : [String(value)];
            controls.forEach(function (control) {
                if (control.type === 'checkbox') {
                    control.checked = values.includes(control.value);
                } else if (control.type === 'radio') {
                    control.checked = values.includes(control.value);
                } else if (control.name !== 'record_id') {
                    control.value = value == null ? '' : String(value);
                }
            });
        });

        updateTotals();
        loading = false;
    }

    async function loadApplication(id) {
        if (!id) {
            return;
        }

        clearTimeout(saveTimer);
        setStatus('Loading saved application…', 'saving');

        try {
            const response = await fetch(endpointUrl + '?action=load&id=' + encodeURIComponent(id), {
                cache: 'no-store'
            });
            const result = await response.json();
            if (!response.ok || !result.success || !result.application) {
                throw new Error(result.message || 'Could not load this application.');
            }

            restoreForm(result.application.form_data);
            recordId.value = String(result.application.id);
            historySelect.value = recordId.value;
            setStatus('Loaded ' + result.application.full_name + ' — ' + result.application.application_date + '. Changes will autosave.', 'saved');
        } catch (error) {
            setStatus(error.message, 'error');
        }
    }

    async function saveApplication() {
        if (loading) {
            return;
        }
        if (saving) {
            savePending = true;
            return;
        }

        const data = readForm();
        const fullName = [data.first_name, data.middle_name, data.last_name]
            .filter(function (part) { return typeof part === 'string' && part.trim() !== ''; })
            .join(' ');

        if (!fullName) {
            setStatus('Enter a name to begin autosaving.', '');
            return;
        }
        if (!data.application_date) {
            setStatus('Enter today’s date to begin autosaving.', 'error');
            return;
        }

        saving = true;
        savePending = false;
        setStatus('Saving changes…', 'saving');

        try {
            const response = await fetch(endpointUrl + '?action=save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: recordId.value || null, form_data: data }),
                cache: 'no-store'
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'The application could not be saved.');
            }

            recordId.value = String(result.id);
            addHistoryOption(result);
            historySelect.value = recordId.value;
            setStatus('Saved ' + result.full_name + ' — ' + result.application_date + ' at ' + new Date().toLocaleTimeString(), 'saved');
        } catch (error) {
            setStatus(error.message, 'error');
        } finally {
            saving = false;
            if (savePending) {
                scheduleSave();
            }
        }
    }

    function scheduleSave() {
        if (loading) {
            return;
        }
        clearTimeout(saveTimer);
        setStatus('Changes detected…', '');
        saveTimer = setTimeout(saveApplication, 650);
    }

    form.addEventListener('input', function () {
        updateTotals();
        scheduleSave();
    });
    form.addEventListener('change', function () {
        updateTotals();
        scheduleSave();
    });

    historySelect.addEventListener('change', function () {
        loadApplication(historySelect.value);
    });

    document.getElementById('newApplication').addEventListener('click', function () {
        clearTimeout(saveTimer);
        loading = true;
        form.reset();
        recordId.value = '';
        historySelect.value = '';
        form.elements.application_date.value = new Date().toISOString().slice(0, 10);
        updateTotals();
        loading = false;
        setStatus('New application. Enter a name to begin autosaving.', '');
        form.elements.first_name.focus();
    });

    document.getElementById('printApplication').addEventListener('click', function () {
        window.print();
    });

    loadHistory().then(function () {
        setStatus('Enter a name to begin autosaving.', '');
    }).catch(function (error) {
        setStatus(error.message, 'error');
    });
    updateTotals();
}());
