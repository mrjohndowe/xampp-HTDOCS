
(function () {

    const form = document.querySelector('form');
    const nameField = document.querySelector('[name="applicant_name"]');
    const ageField = document.querySelector('[name="age"]');

    const status = document.getElementById('autosaveStatus');
    const statusText = document.getElementById('autosaveText');

    if (!form || !nameField || !ageField) {
        return;
    }

    let answerNumber = 0;
    form.querySelectorAll('.answer-line').forEach(function (line) {
        const input = document.createElement('input');
        const question = line.closest('.question');

        input.type = 'text';
        input.className = line.className + ' fillable-answer';
        input.name = 'answer_' + (++answerNumber);
        input.setAttribute(
            'aria-label',
            question ? question.innerText.trim().replace(/\s+/g, ' ') : 'Application answer'
        );
        line.replaceWith(input);
    });

    let choiceNumber = 0;
    let radioGroupNumber = 0;
    const radioGroups = new WeakMap();
    form.querySelectorAll('.check').forEach(function (check) {
        const optionText = check.textContent.trim().replace(/\s+/g, ' ');
        const group = check.closest('.checks');
        const isSingleChoice = group && group.classList.contains('single');
        const label = document.createElement('label');
        const box = check.querySelector('.box');

        label.className = check.className;
        while (check.firstChild) {
            label.appendChild(check.firstChild);
        }

        if (box) {
            const checkbox = document.createElement('input');
            checkbox.type = isSingleChoice ? 'radio' : 'checkbox';
            checkbox.className = 'box';
            if (isSingleChoice) {
                if (!radioGroups.has(group)) {
                    radioGroups.set(group, 'choice_group_' + (++radioGroupNumber));
                }
                checkbox.name = radioGroups.get(group);
            } else {
                checkbox.name = 'choice_' + (++choiceNumber);
            }
            checkbox.value = optionText;
            checkbox.setAttribute('aria-label', optionText);
            box.replaceWith(checkbox);
        }

        check.replaceWith(label);
    });

    let tableCellNumber = 0;
    form.querySelectorAll('.mini-table td').forEach(function (cell) {
        const input = document.createElement('input');
        const table = cell.closest('table');
        const column = table.rows[0].cells[cell.cellIndex].textContent.trim();
        const row = cell.parentElement.rowIndex;

        input.type = 'text';
        input.name = 'friend_' + row + '_' + cell.cellIndex + '_' + (++tableCellNumber);
        input.setAttribute('aria-label', column + ', friend ' + row);
        cell.appendChild(input);
    });

    let signatureNumber = 0;
    form.querySelectorAll('.signature-field .line').forEach(function (line) {
        const input = document.createElement('input');
        const label = line.parentElement.querySelector('strong');

        input.type = 'text';
        input.className = line.className + ' fillable-signature';
        input.name = 'signature_' + (++signatureNumber);
        input.setAttribute('aria-label', label ? label.textContent.trim() : 'Signature');
        line.replaceWith(input);
    });

    let noteNumber = 0;
    form.querySelectorAll('.notes-line').forEach(function (line) {
        const input = document.createElement('input');

        input.type = 'text';
        input.className = line.className;
        input.name = 'interviewer_note_' + (++noteNumber);
        input.setAttribute('aria-label', 'Interviewers note ' + noteNumber);
        line.replaceWith(input);
    });

    let saveTimer = null;
    let loadTimer = null;
    let lastLoadedName = '';
    let isLoading = false;

    function setStatus(type, message) {

        status.classList.remove(
            'saving',
            'saved',
            'error'
        );

        if (type) {
            status.classList.add(type);
        }

        statusText.textContent = message;
    }

    function hasRequiredFields() {

        const name = nameField.value.trim();
        const age = ageField.value.trim();

        return name !== '' && age !== '';
    }

    function getFormData() {

        const data = {};

        const elements = form.querySelectorAll(
            'input[name], textarea[name], select[name]'
        );

        elements.forEach(function (element) {

            if (element.type === 'checkbox') {
                data[element.name] = element.checked;
                return;
            }

            if (element.type === 'radio') {

                if (element.checked) {
                    data[element.name] = element.value;
                }

                return;
            }

            data[element.name] = element.value;
        });

        return data;
    }

    function restoreForm(data) {

        if (!data || typeof data !== 'object') {
            return;
        }

        isLoading = true;

        Object.keys(data).forEach(function (name) {

            const elements = form.querySelectorAll(
                '[name="' + CSS.escape(name) + '"]'
            );

            elements.forEach(function (element) {

                if (element.type === 'checkbox') {

                    element.checked = Boolean(data[name]);

                } else if (element.type === 'radio') {

                    element.checked = element.value === data[name];

                } else {

                    element.value = data[name] ?? '';
                }
            });
        });

        isLoading = false;

        lastLoadedName = nameField.value.trim();

        setStatus(
            'saved',
            'Saved application loaded'
        );
    }

    async function loadApplication() {

        const name = nameField.value.trim();

        if (!name) {
            return;
        }

        if (name === lastLoadedName) {
            return;
        }

        try {

            const response = await fetch(
                '?autosave=1&name=' + encodeURIComponent(name),
                {
                    method: 'GET',
                    cache: 'no-store'
                }
            );

            const result = await response.json();

            if (
                result.success &&
                result.exists &&
                result.application
            ) {

                restoreForm(result.application);

            } else {

                lastLoadedName = name;

                if (hasRequiredFields()) {

                    setStatus(
                        '',
                        'Ready to autosave'
                    );

                }
            }

        } catch (error) {

            setStatus(
                'error',
                'Autosave connection unavailable'
            );
        }
    }

    async function saveApplication() {

        if (isLoading) {
            return;
        }

        if (!hasRequiredFields()) {

            setStatus(
                '',
                'Enter name and age to enable autosave'
            );

            return;
        }

        setStatus(
            'saving',
            'Saving...'
        );

        const data = getFormData();

        data._autosave = true;
        data._timestamp = Date.now();

        try {

            const response = await fetch(
                '?autosave=1',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data),
                    cache: 'no-store'
                }
            );

            const result = await response.json();

            if (result.success && result.saved) {

                setStatus(
                    'saved',
                    'Autosaved ' + new Date().toLocaleTimeString()
                );

            } else {

                setStatus(
                    'error',
                    result.message || 'Autosave failed'
                );
            }

        } catch (error) {

            setStatus(
                'error',
                'Unable to autosave'
            );
        }
    }

    function queueSave() {

        clearTimeout(saveTimer);

        if (!hasRequiredFields()) {

            setStatus(
                '',
                'Enter name and age to enable autosave'
            );

            return;
        }

        setStatus(
            'saving',
            'Changes detected...'
        );

        saveTimer = setTimeout(
            saveApplication,
            800
        );
    }

    function queueNameLoad() {

        clearTimeout(loadTimer);

        loadTimer = setTimeout(
            loadApplication,
            500
        );
    }

    form.addEventListener(
        'input',
        function (event) {

            if (isLoading) {
                return;
            }

            /*
             * When the name changes, first look for an existing
             * application belonging to that name.
             */
            if (event.target === nameField) {

                queueNameLoad();

                if (!hasRequiredFields()) {

                    setStatus(
                        '',
                        'Enter name and age to enable autosave'
                    );

                    return;
                }
            }

            queueSave();
        }
    );

    form.addEventListener(
        'change',
        function () {

            if (!isLoading) {
                queueSave();
            }
        }
    );

    /*
     * Load an existing application when the page opens
     * if the name field already contains a value.
     */
    if (nameField.value.trim() !== '') {
        loadApplication();
    }

    /*
     * Warn if the user attempts to submit normally.
     * Autosave is the persistence mechanism.
     */
    form.addEventListener(
        'submit',
        function (event) {

            event.preventDefault();

            if (!hasRequiredFields()) {

                setStatus(
                    'error',
                    'Name and age are required before saving'
                );

                nameField.focus();

                return;
            }

            saveApplication();
        }
    );

})();
