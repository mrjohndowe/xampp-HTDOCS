(function () {
    const form = document.getElementById('reportForm');
    const status = document.getElementById('saveStatus');
    const clearButton = document.getElementById('clearDraft');
    const storageKey = 'hurtFeelingsReportDraft';
    let saveTimer;

    if (!form || !status || !clearButton) {
        throw new Error('Hurt feelings report controls were not found.');
    }

    function saveDraft() {
        const draft = {};
        for (const control of form.elements) {
            if (!control.name) continue;
            if (control.type === 'checkbox') {
                if (!Array.isArray(draft[control.name])) draft[control.name] = [];
                if (control.checked) draft[control.name].push(control.value);
            } else if (control.type === 'radio') {
                if (control.checked) draft[control.name] = control.value;
            } else {
                draft[control.name] = control.value;
            }
        }

        try {
            localStorage.setItem(storageKey, JSON.stringify(draft));
            status.textContent = 'Draft saved in this browser.';
        } catch (error) {
            status.textContent = 'Unable to save draft in this browser.';
            console.error('Unable to save hurt feelings report draft.', error);
        }
    }

    try {
        const draft = JSON.parse(localStorage.getItem(storageKey) || '{}');
        for (const control of form.elements) {
            if (!control.name || !(control.name in draft)) continue;
            if (control.type === 'checkbox') {
                control.checked = draft[control.name].includes(control.value);
            } else if (control.type === 'radio') {
                control.checked = draft[control.name] === control.value;
            } else {
                control.value = draft[control.name];
            }
        }
        if (Object.keys(draft).length) status.textContent = 'Saved draft restored.';
    } catch (error) {
        status.textContent = 'Unable to restore saved draft.';
        console.error('Unable to restore hurt feelings report draft.', error);
    }

    form.addEventListener('input', function () {
        clearTimeout(saveTimer);
        status.textContent = 'Saving draft…';
        saveTimer = setTimeout(saveDraft, 300);
    });

    form.addEventListener('change', function () {
        clearTimeout(saveTimer);
        saveDraft();
    });

    clearButton.addEventListener('click', function () {
        if (!window.confirm('Clear this report and its saved draft from this browser?')) return;
        clearTimeout(saveTimer);
        form.reset();
        try {
            localStorage.removeItem(storageKey);
            status.textContent = 'Draft cleared.';
        } catch (error) {
            status.textContent = 'Unable to clear the saved draft.';
            console.error('Unable to clear hurt feelings report draft.', error);
        }
    });
})();
