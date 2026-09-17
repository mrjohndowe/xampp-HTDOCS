<?php declare(strict_types=1);

session_start();

$dbDir = __DIR__ . '/data';
if (!is_dir($dbDir))
  mkdir($dbDir, 0775, true);
$db = new PDO('sqlite:' . $dbDir . '/applications.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS applications (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 name TEXT NOT NULL DEFAULT '',
 form_json TEXT NOT NULL DEFAULT '{}',
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)");

if (isset($_GET['api'])) {
  header('Content-Type: application/json; charset=utf-8');
  $api = $_GET['api'];
  if ($api === 'list') {
    $q = $db->query("SELECT id,name,updated_at FROM applications WHERE trim(name)<>'' ORDER BY name COLLATE NOCASE, updated_at DESC");
    echo json_encode($q->fetchAll(PDO::FETCH_ASSOC));
    exit;
  }
  if ($api === 'load') {
    $id = (int) ($_GET['id'] ?? 0);
    $s = $db->prepare('SELECT * FROM applications WHERE id=?');
    $s->execute([$id]);
    echo json_encode($s->fetch(PDO::FETCH_ASSOC) ?: null);
    exit;
  }
  if ($api === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int) ($payload['id'] ?? 0);
    $form = $payload['form'] ?? [];
    $name = trim((string) ($form['name'] ?? ''));
    if ($id > 0) {
      $s = $db->prepare('UPDATE applications SET name=?,form_json=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
      $s->execute([$name, json_encode($form), $id]);
    } else {
      $s = $db->prepare('INSERT INTO applications(name,form_json) VALUES(?,?)');
      $s->execute([$name, json_encode($form)]);
      $id = (int) $db->lastInsertId();
    }
    echo json_encode(['ok' => true, 'id' => $id]);
    exit;
  }
  exit;
}

function hairColor(): array
{
  $hColor = [
    'BLACK' => 'BLACK',
    'BROWN' => 'BROWN',
    'BLONDE' => 'BLONDE',
    'RED' => 'RED',
    'GRAY' => 'GRAY',
    'BALD' => 'BALD',
    'OTHER' => 'OTHER'
  ];

  return $hColor;
}

function hairColorSelect(): string
{
  $hColor = hairColor();
  $hairSelection = '<select name="hair_color">';
  $hairSelection .= '<option value="">Select Hair Color</option>';
  foreach ($hColor as $key => $value) {
    $hairSelection .= '<option value="' . $key . '">' . $value . '</option>';
  }
  $hairSelection .= '</select>';
  return $hairSelection;
}

function eyeColor(): array
{
  $eColor = [
    'BROWN' => 'BROWN',
    'BLUE' => 'BLUE',
    'GREEN' => 'GREEN',
    'HAZEL' => 'HAZEL',
    'GRAY' => 'GRAY',
    'OTHER' => 'OTHER'
  ];
  return $eColor;
}

function eyeColorSelect(): string
{
  $eColor = eyeColor();
  $eyeSelection = '<select name="eye_color">';
  $eyeSelection .= '<option value="">Select Eye Color</option>';
  foreach ($eColor as $key => $value) {
    $eyeSelection .= '<option value="' . $key . '">' . $value . '</option>';
  }
  $eyeSelection .= '</select>';
  return $eyeSelection;
}

function box(string $name, string $label = ''): string
{
  return '<label class="check"><input type="checkbox" name="' . $name . '"> <span>' . $label . '</span></label>';
}
?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Application for a Piece of Ass</title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
        <div class="toolbar">
            <strong>Saved Applications</strong>
            <input id="search" placeholder="Search name...">
            <select id="records">
                <option value="">New application</option>
            </select>
            <button type="button" id="newBtn">New</button>
            <span class="status" id="status">Ready</span>
        </div>
        <div class="wrap">
            <form class="paper" id="appForm" autocomplete="off">
                <div class="title">APPLICATION FOR A PIECE OF ASS</div>
                <div class="row c2">
                    <div class="cell">NAME: <input name="name" type="text"> </div>
                    <div class="cell">ADDRESS: <input name="address" type="text"> </div>
                </div>
                <div class="row c4">
                    <div class="cell">AGE: <input name="age" type="text"> </div>
                    <div class="cell">HOME PHONE: <input name="home_phone" type="text"> </div>
                    <div class="cell">BUSINESS PHONE:<input name="business_phone" type="text"></div>
                    <div class="cell">S.S. NUMBER:<input name="ss_number" type="text"></div>
                </div>
                <div class="row c5">
                    <div class="cell"> HAIR COLOR <br><br><?= hairColorSelect() ?> </div>
                    <div class="cell">COLOR OF EYES <br><br><?= eyeColorSelect() ?></div>
                    <div class="cell">DENTURES<input name="dentures" type="text"></div>
                    <div class="cell">CHEST SIZE<br>BRA SIZE<input name="chest_bra" type="text"></div>
                    <div class="cell">DO YOU PLAN TO USE ENLARGEMENT<input name="enlargement" type="text"></div>
                </div>
                <div class="row" style="grid-template-columns:270px 1fr 200px">
                    <div class="cell">ARE YOUR BREASTS/BALLS REAL:<div class="checks"><?= box('real_yes', 'YES') ?><?= box('real_no', 'NO') ?></div> </div>
                    <div class="cell">DO YOU LIKE THEM:<div class="checks"><?= box('like_sucked', 'SUCKED') ?><?= box('like_chewed', 'CHEWED') ?><?= box('like_kissed', 'KISSED') ?><?= box('like_caressed', 'CARESSED') ?><?= box('like_squeezed', 'SQUEEZED') ?><?= box('like_none', 'NONE OF THE ABOVE') ?></div><input name="like_remarks" placeholder="REMARKS"></div>
                    <div class="cell">CAN YOU STAY OUT LATE:<div class="checks"><?= box('late_yes', 'YES') ?><?= box('late_no', 'NO') ?></div> </div>
                </div>
                <div class="row" style="grid-template-columns:270px 1fr">
                    <div class="cell">HOW LATE:<div class="checks"><?= box('late_all', 'ALL NIGHT') ?><?= box('late_days', 'SEVERAL DAYS') ?></div> </div>
                    <div class="cell remarks">REMARKS<textarea name="remarks1"></textarea></div>
                </div>
                <div class="section">PENIS OR PUSSY SIZE?</div>
                <div class="row" style="grid-template-columns:1fr 230px 200px">
                    <div class="cell">
                        <div class="checks">
                            <?= box('size_small', 'SMALL') ?>
                            <?= box('size_medium', 'MEDIUM') ?>
                            <?= box('size_large', 'LARGE') ?>
                            <?= box('size_xlarge', 'X-LARGE') ?>
                            <?= box('size_XX', 'XX-LARGE') ?>
                            <?= box('size_average', 'AVERAGE') ?>
                        </div>
                    </div>
                    <div class="cell">DO YOU LIKE ORAL SEX:<div class="checks"><?= box('oral_yes', 'YES') ?><?= box('oral_no', 'NO') ?></div> </div>
                    <div class="cell">REMARKS<input name="oral_remarks"></div>
                </div>
                <div class="section">WHILE SCREWING, DO YOU PREFER ALL THAT APPLY</div>
                <div class="row" style="grid-template-columns:1fr 200px">
                    <div class="cell">
                        <div class="checks">
                            <?= box('pref_sleep', 'GO TO SLEEP') ?>
                            <?= box('pref_moan', 'MOAN') ?>
                            <?= box('pref_faint', 'FAINT') ?>
                            <?= box('pref_lay', 'JUST LAY THERE') ?>
                            <?= box('pref_cry', 'CRY') ?>
                            <?= box('pref_fart', 'FART') ?>
                            <?= box('pref_scratch', 'SCRATCH') ?>
                            <?= box('pref_scream', 'SCREAM') ?>
                            <?= box('pref_whimper', 'WHIMPER') ?>
                            <?= box('pref_all', 'ALL OF THE ABOVE') ?>
                            <?= box('pref_none', 'NONE OF THE ABOVE') ?>
                        </div>
                    </div>
                    <div class="cell">OTHER<input name="pref_other"></div>
                </div>
                <div class="row">
                    <div class="cell tall">LIST THE TOP THREE POSITIONS YOU LIKE BEST:<br>1. <input name="pos1"><br>2. <input name="pos2"><br>3. <input name="pos3"></div>
                </div>
                <div class="section">WHEN YOU CLIMAX DO YOU</div>
                <div class="row" style="grid-template-columns:1fr 200px">
                    <div class="cell">
                        <div class="checks">
                            <?= box('climax_wiggle', 'WIGGLE') ?>
                            <?= box('climax_wobble', 'WOBBLE') ?>
                            <?= box('climax_twist', 'TWIST') ?>
                            <?= box('climax_jerk', 'JERK') ?>
                            <?= box('climax_scream', 'SCREAM') ?>
                            <?= box('climax_cry', 'CRY') ?>
                            <?= box('climax_fart', 'START FARTING LIKE HELL') ?>
                        </div>
                    </div>
                    <div class="cell">OTHER<input name="climax_other"></div>
                </div>
                <div class="row">
                    <div class="cell">WHAT KIND OF SCREW DO YOU LIKE? <div class="checks"><?= box('screw_superfast', 'SUPER FAST') ?><?= box('screw_fast', 'FAST') ?><?= box('screw_slow', 'SLOW') ?> <label>HOW MANY TIMES? <input name="how_many" style="width:90px"></label><label>FOR HOW LONG? <input name="how_long" style="width:90px"></label><label>OTHER <input name="screw_other" style="width:180px"></label></div> </div>
                </div>
                <div class="section">IF YOU HAVE SCREWED BEFORE, GIVE TWO (2) REFERENCES<br>(NOT IMMEDIATE FAMILY)</div>
                <div class="refgrid">
                    <div class="cell">NAME<input name="ref1_name"></div>
                    <div class="cell">ADDRESS<input name="ref1_address"></div>
                    <div class="cell">PHONE<input name="ref1_phone"></div>
                    <div class="cell">NAME<input name="ref2_name"></div>
                    <div class="cell">ADDRESS<input name="ref2_address"></div>
                    <div class="cell">PHONE<input name="ref2_phone"></div>
                </div>
                <div class="section">IF APPLICATION IS FAVORABLE WHAT ARE YOUR CHANCES FOR?</div>
                <div class="row">
                    <div class="cell"> <div class="checks"><?= box('chance_one_night', 'ONE NIGHT') ?><?= box('chance_one_hour', 'ONE HOUR') ?><?= box('chance_much', 'MUCH HIGHER') ?><?= box('chance_blow', 'BLOW JOB') ?><?= box('chance_other', 'OTHER') ?></div>REMARKS<input name="chance_remarks"> </div>
                </div>
                <div class="section">WHAT CREDIT CARDS WILL YOU ACCEPT</div>
                <div class="row">
                    <div class="cell">
                        <div class="checks">
                            <?= box('cc_visa', 'VISA') ?>
                            <?= box('cc_discover', 'DISCOVER') ?>
                            <?= box('cc_mastercard', 'MASTERCARD') ?>
                            <?= box('cc_amex', 'AMERICAN EXPRESS') ?>
                            <?= box('cc_sears', 'SEARS') ?>
                            <?= box('cc_diners', 'DINERS CLUB') ?>
                            <?= box('cc_rebel', 'REBEL') ?>
                            <?= box('cc_jcp', 'JC PENNY') ?>
                            <?= box('cc_firestone', 'FIRESTONE') ?>
                            <?= box('cc_goodyear', 'GOODYEAR') ?>
                            <?= box('cc_other', 'OTHER') ?>
                            <?= box('cc_none', 'N/A or FREE') ?>
                        </div>
                    </div>
                </div>
                <div class="section">LIST EXTRACURRICULAR ACTIVITIES AND PERSONAL PREFERENCES</div>
                <div class="row">
                    <div class="cell lines">
                        <textarea name="activities"></textarea>
                    </div>
                </div>
            </form>
        </div>
        <script>
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
        </script>
    </body>
</html>
