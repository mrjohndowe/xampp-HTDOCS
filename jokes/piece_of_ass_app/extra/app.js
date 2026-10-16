
            let currentId = 0,
              timer = null;
            const form = document.getElementById('appForm'),
              statusEl = document.getElementById('status'),
              records = document.getElementById('records'),
              search = document.getElementById('search');

            function data() {
              const o = {};
              new FormData(form).forEach((v, k) => o[k] = v);
              form.querySelectorAll('input[type=checkbox]').forEach(x => o[x.name] = x.checked);
              return o
            }

            function fill(o) {
              form.reset();
              Object.entries(o || {}).forEach(([k, v]) => {
                const e = form.elements[k];
                if (!e) return;
                if (e.type === 'checkbox') e.checked = !!v;
                else e.value = v ?? ''
              })
            }
            async function save() {
              statusEl.textContent = 'Saving...';
              const r = await fetch('?api=save', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                  id: currentId,
                  form: data()
                })
              });
              const j = await r.json();
              currentId = j.id;
              statusEl.textContent = 'Saved';
              await list();
              records.value = String(currentId)
            }

            function queue() {
              clearTimeout(timer);
              statusEl.textContent = 'Unsaved';
              timer = setTimeout(save, 700)
            }
            form.addEventListener('input', queue);
            form.addEventListener('change', queue);
            async function list() {
              const r = await fetch('?api=list');
              const a = await r.json(),
                term = search.value.toLowerCase();
              const old = records.value;
              records.innerHTML = '<option value="">New application</option>';
              a.filter(x => x.name.toLowerCase().includes(term)).forEach(x => {
                const op = document.createElement('option');
                op.value = x.id;
                op.textContent = x.name + ' — ' + x.updated_at;
                records.appendChild(op)
              });
              if ([...records.options].some(o => o.value === old)) records.value = old
            }
            records.addEventListener('change', async () => {
              if (!records.value) {
                currentId = 0;
                fill({});
                return
              }
              const r = await fetch('?api=load&id=' + records.value),
                j = await r.json();
              currentId = +j.id;
              fill(JSON.parse(j.form_json || '{}'));
              statusEl.textContent = 'Loaded'
            });
            search.addEventListener('input', list);
            document.getElementById('newBtn').onclick = () => {
              currentId = 0;
              records.value = '';
              fill({});
              statusEl.textContent = 'New'
            };
            list();
