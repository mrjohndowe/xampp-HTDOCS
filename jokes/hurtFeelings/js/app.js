(function () {
    const form = document.getElementById('reportForm');
    const status = document.getElementById('saveStatus');
    const clearButton = document.getElementById('clearDraft');
    const savedReportsSelect = document.getElementById('savedReports');
    const saveButton = document.getElementById('saveReport');
    const deleteButton = document.getElementById('deleteReport');
    const draftStorageKey = 'hurtFeelingsReportDraft';
    const reportsStorageKey = 'hurtFeelingsReports';
    let saveTimer;

    if (!form || !status || !clearButton || !savedReportsSelect || !saveButton || !deleteButton) {
        throw new Error('Hurt feelings report controls were not found.');
    }

    function getFormData() {
        const data = {};
        for (const control of form.elements) {
            if (!control.name) continue;
            if (control.type === 'checkbox') {
                if (!Array.isArray(data[control.name])) data[control.name] = [];
                if (control.checked) data[control.name].push(control.value);
            } else if (control.type === 'radio') {
                if (control.checked) data[control.name] = control.value;
            } else {
                data[control.name] = control.value;
            }
        }
        return data;
    }

    function setFormData(data) {
        for (const control of form.elements) {
            if (!control.name || !(control.name in data)) continue;
            if (control.type === 'checkbox') {
                control.checked = data[control.name].includes(control.value);
            } else if (control.type === 'radio') {
                control.checked = data[control.name] === control.value;
            } else {
                control.value = data[control.name];
            }
        }
    }

    function saveDraft() {
        const draft = getFormData();
        try {
            localStorage.setItem(draftStorageKey, JSON.stringify(draft));
            status.textContent = 'Draft saved in this browser.';
        } catch (error) {
            status.textContent = 'Unable to save draft in this browser.';
            console.error('Unable to save hurt feelings report draft.', error);
        }
    }

    function getSavedReports() {
        try {
            return JSON.parse(localStorage.getItem(reportsStorageKey) || '{}');
        } catch (error) {
            console.error('Unable to get saved reports.', error);
            return {};
        }
    }

    function saveSavedReports(reports) {
        try {
            localStorage.setItem(reportsStorageKey, JSON.stringify(reports));
        } catch (error) {
            console.error('Unable to save reports.', error);
            throw error;
        }
    }

    function populateReportsDropdown() {
        const reports = getSavedReports();
        savedReportsSelect.innerHTML = '<option value="">-- Load saved report --</option>';
        for (const [id, report] of Object.entries(reports)) {
            const option = document.createElement('option');
            option.value = id;
            option.textContent = report.name;
            savedReportsSelect.appendChild(option);
        }
    }

    function saveReport() {
        const reportName = window.prompt('Enter a name for this report:');
        if (!reportName) return;

        const reports = getSavedReports();
        const id = Date.now().toString();
        reports[id] = {
            name: reportName,
            data: getFormData(),
            savedAt: new Date().toISOString()
        };

        try {
            saveSavedReports(reports);
            populateReportsDropdown();
            savedReportsSelect.value = id;
            status.textContent = 'Report saved.';
        } catch (error) {
            status.textContent = 'Unable to save report.';
            console.error('Unable to save hurt feelings report.', error);
        }
    }

    function loadReport(id) {
        if (!id) return;

        const reports = getSavedReports();
        if (!reports[id]) {
            status.textContent = 'Report not found.';
            return;
        }

        try {
            setFormData(reports[id].data);
            status.textContent = 'Report loaded: ' + reports[id].name;
        } catch (error) {
            status.textContent = 'Unable to load report.';
            console.error('Unable to load hurt feelings report.', error);
        }
    }

    function deleteReport() {
        const id = savedReportsSelect.value;
        if (!id) {
            status.textContent = 'No report selected.';
            return;
        }

        if (!window.confirm('Delete this saved report?')) return;

        const reports = getSavedReports();
        delete reports[id];

        try {
            saveSavedReports(reports);
            populateReportsDropdown();
            status.textContent = 'Report deleted.';
        } catch (error) {
            status.textContent = 'Unable to delete report.';
            console.error('Unable to delete hurt feelings report.', error);
        }
    }

    try {
        const draft = JSON.parse(localStorage.getItem(draftStorageKey) || '{}');
        setFormData(draft);
        if (Object.keys(draft).length) status.textContent = 'Saved draft restored.';
    } catch (error) {
        status.textContent = 'Unable to restore saved draft.';
        console.error('Unable to restore hurt feelings report draft.', error);
    }

    populateReportsDropdown();

    form.addEventListener('input', function () {
        clearTimeout(saveTimer);
        status.textContent = 'Saving draft…';
        saveTimer = setTimeout(saveDraft, 300);
    });

    form.addEventListener('change', function () {
        clearTimeout(saveTimer);
        saveDraft();
    });

    saveButton.addEventListener('click', saveReport);

    savedReportsSelect.addEventListener('change', function () {
        loadReport(this.value);
    });

    deleteButton.addEventListener('click', deleteReport);

    clearButton.addEventListener('click', function () {
        if (!window.confirm('Clear this report and its saved draft from this browser?')) return;
        clearTimeout(saveTimer);
        form.reset();
        savedReportsSelect.value = '';
        try {
            localStorage.removeItem(draftStorageKey);
            status.textContent = 'Draft cleared.';
        } catch (error) {
            status.textContent = 'Unable to clear the saved draft.';
            console.error('Unable to clear hurt feelings report draft.', error);
        }
    });
})();
