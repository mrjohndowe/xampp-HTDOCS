(function () {
    'use strict';

    const form = document.getElementById('friendForm');
    const historySelect = document.getElementById('historySelect');
    const recordId = document.getElementById('recordId');
    const status = document.getElementById('saveStatus');
    const photoInput = document.getElementById('photoInput');
    const photoPreview = document.getElementById('photoPreview');
    const photoPlaceholder = document.getElementById('photoPlaceholder');
    const endpoint = 'index.php';
    let photoData = '';
    let photoReadError = false;
    let saveTimer = null;
    let isLoading = false;
    let isSaving = false;
    let saveQueued = false;

    function setStatus(message, state) {
        status.textContent = message;
        status.dataset.state = state || '';
    }

    function renderPhoto(data) {
        photoData = data || '';
        photoPreview.hidden = !photoData;
        photoPlaceholder.hidden = Boolean(photoData);
        photoPreview.src = photoData;
    }

    function collectForm() {
        const data = {};
        new FormData(form).forEach(function (value, name) {
            if (name === 'record_id' || name === 'photoInput') {
                return;
            }
            if (Object.prototype.hasOwnProperty.call(data, name)) {
                if (!Array.isArray(data[name])) {
                    data[name] = [data[name]];
                }
                data[name].push(value);
            } else {
                data[name] = value;
            }
        });
        data.photo_data = photoData;
        return data;
    }

    function historyOption(application) {
        const id = String(application.id);
        let option = Array.from(historySelect.options).find(function (item) {
            return item.value === id;
        });
        if (!option) {
            option = document.createElement('option');
            option.value = id;
            historySelect.appendChild(option);
        }
        option.textContent = application.full_name + ' — ' + application.application_date;
    }

    async function loadHistory() {
        const response = await fetch(endpoint + '?action=history', { cache: 'no-store' });
        const result = await response.json();
        if (!response.ok || !result.success || !Array.isArray(result.applications)) {
            throw new Error(result.message || 'Could not load saved application history.');
        }
        const currentId = recordId.value;
        historySelect.replaceChildren(new Option('Choose an application to reload or edit', ''));
        result.applications.forEach(historyOption);
        historySelect.value = currentId;
    }

    function restoreForm(data) {
        isLoading = true;
        form.reset();
        renderPhoto(data.photo_data || '');
        Object.entries(data).forEach(function (entry) {
            const name = entry[0];
            const value = entry[1];
            if (name === 'photo_data') {
                return;
            }
            const controls = Array.from(form.elements).filter(function (control) {
                return control.name === name && control.name !== 'record_id';
            });
            const values = Array.isArray(value) ? value.map(String) : [String(value)];
            controls.forEach(function (control) {
                if (control.type === 'radio') {
                    control.checked = values.includes(control.value);
                } else if (control.tagName === 'SELECT' || control.type !== 'file') {
                    control.value = value == null ? '' : String(value);
                }
            });
        });
        isLoading = false;
    }

    async function loadApplication(id) {
        if (!id) {
            return;
        }
        clearTimeout(saveTimer);
        setStatus('Loading saved application…', 'saving');
        try {
            const response = await fetch(endpoint + '?action=load&id=' + encodeURIComponent(id), {
                cache: 'no-store'
            });
            const result = await response.json();
            if (!response.ok || !result.success || !result.application) {
                throw new Error(result.message || 'Could not load this application.');
            }
            restoreForm(result.application.form_data);
            recordId.value = String(result.application.id);
            historySelect.value = recordId.value;
            photoInput.value = '';
            setStatus('Loaded ' + result.application.full_name + ' — ' + result.application.application_date + '. Changes will autosave.', 'saved');
        } catch (error) {
            setStatus(error.message, 'error');
        }
    }

    async function saveApplication() {
        if (isLoading || photoReadError) {
            return;
        }
        if (isSaving) {
            saveQueued = true;
            return;
        }

        const data = collectForm();
        const fullName = String(data.full_name || '').trim();
        if (!fullName) {
            setStatus('Enter a full name to begin autosaving.', '');
            return;
        }
        if (!data.application_date) {
            setStatus('Enter an application date to begin autosaving.', 'error');
            return;
        }

        isSaving = true;
        saveQueued = false;
        setStatus('Saving changes…', 'saving');
        try {
            const response = await fetch(endpoint + '?action=save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: recordId.value || null,
                    form_data: data
                }),
                cache: 'no-store'
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Could not save this application.');
            }
            recordId.value = String(result.id);
            historyOption(result);
            historySelect.value = recordId.value;
            setStatus('Saved ' + result.full_name + ' — ' + result.application_date + ' at ' + new Date().toLocaleTimeString(), 'saved');
        } catch (error) {
            setStatus(error.message, 'error');
        } finally {
            isSaving = false;
            if (saveQueued) {
                queueSave();
            }
        }
    }

    function queueSave() {
        if (isLoading || photoReadError) {
            return;
        }
        clearTimeout(saveTimer);
        setStatus('Changes detected…', '');
        saveTimer = setTimeout(saveApplication, 650);
    }

    form.addEventListener('input', queueSave);
    form.addEventListener('change', queueSave);

    photoInput.addEventListener('change', function () {
        const file = photoInput.files && photoInput.files[0];
        if (!file) {
            photoReadError = false;
            renderPhoto('');
            queueSave();
            return;
        }
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 8 * 1024 * 1024) {
            photoReadError = true;
            setStatus('Choose a JPG, PNG, or WebP photo no larger than 8 MB.', 'error');
            photoInput.value = '';
            return;
        }

        photoReadError = false;
        setStatus('Preparing photo…', 'saving');
        const reader = new FileReader();
        reader.addEventListener('load', function () {
            const image = new Image();
            image.addEventListener('load', function () {
                const scale = Math.min(1, 900 / Math.max(image.naturalWidth, image.naturalHeight));
                const canvas = document.createElement('canvas');
                canvas.width = Math.round(image.naturalWidth * scale);
                canvas.height = Math.round(image.naturalHeight * scale);
                canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
                renderPhoto(canvas.toDataURL('image/jpeg', 0.82));
                queueSave();
            });
            image.addEventListener('error', function () {
                photoReadError = true;
                setStatus('The selected photo could not be read.', 'error');
            });
            image.src = String(reader.result);
        });
        reader.addEventListener('error', function () {
            photoReadError = true;
            setStatus('The selected photo could not be read.', 'error');
        });
        reader.readAsDataURL(file);
    });

    historySelect.addEventListener('change', function () {
        loadApplication(historySelect.value);
    });

    document.getElementById('newApplication').addEventListener('click', function () {
        clearTimeout(saveTimer);
        isLoading = true;
        form.reset();
        recordId.value = '';
        historySelect.value = '';
        photoInput.value = '';
        renderPhoto('');
        form.elements.application_date.value = new Date().toISOString().slice(0, 10);
        isLoading = false;
        setStatus('New application. Enter a full name to begin autosaving.', '');
        form.elements.full_name.focus();
    });

    document.getElementById('printApplication').addEventListener('click', function () {
        window.print();
    });

    loadHistory().then(function () {
        setStatus('Enter a full name to begin autosaving.', '');
    }).catch(function (error) {
        setStatus(error.message, 'error');
    });
}());
