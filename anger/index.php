<?php
declare(strict_types=1);
session_start();
header('X-Content-Type-Options: nosniff');
$docs = json_decode(file_get_contents(__DIR__.'/documents.json'), true);
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = getenv('DB_DSN') ?: 'sqlite:'.__DIR__.'/data/anger_records.sqlite';
    if (str_starts_with($dsn, 'sqlite:')) {
        if (!is_dir(__DIR__.'/data')) mkdir(__DIR__.'/data', 0770, true);
    }
    $pdo = new PDO($dsn, getenv('DB_USER') ?: null, getenv('DB_PASS') ?: null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS records (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            person_name VARCHAR(200) NOT NULL,
            record_date DATE NOT NULL,
            document_id VARCHAR(80) NOT NULL,
            responses LONGTEXT NOT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            person_name VARCHAR(200) NOT NULL,
            record_date DATE NOT NULL,
            document_id VARCHAR(80) NOT NULL,
            responses TEXT NOT NULL DEFAULT '{}',
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
    }
    return $pdo;
}
function json_out($v, int $status=200): never {
    http_response_code($status); header('Content-Type: application/json; charset=utf-8');
    echo json_encode($v, JSON_UNESCAPED_UNICODE); exit;
}
if (isset($_GET['api'])) {
    try {
        $pdo=db(); $action=$_GET['api'];
        if ($action==='history') {
            $rows=$pdo->query("SELECT id, person_name, record_date, document_id, updated_at FROM records ORDER BY record_date DESC, updated_at DESC")->fetchAll();
            json_out($rows);
        }
        if ($action==='load') {
            $id=(int)($_GET['id']??0);
            $st=$pdo->prepare("SELECT * FROM records WHERE id=?"); $st->execute([$id]); $r=$st->fetch();
            if(!$r) json_out(['error'=>'Record not found'],404);
            $r['responses']=json_decode($r['responses'],true) ?: [];
            json_out($r);
        }
        if ($action==='save') {
            if ($_SERVER['REQUEST_METHOD']!=='POST') json_out(['error'=>'POST required'],405);
            $input=json_decode(file_get_contents('php://input'),true) ?: [];
            $id=(int)($input['id']??0); $name=trim((string)($input['name']??''));
            $date=(string)($input['date']??date('Y-m-d')); $doc=(string)($input['document_id']??'');
            $responses=$input['responses']??[];
            if($name==='') json_out(['error'=>'Enter a name before saving.'],422);
            if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) json_out(['error'=>'Invalid date.'],422);
            global $docs; $valid=array_column($docs,'id');
            if(!in_array($doc,$valid,true)) json_out(['error'=>'Invalid document.'],422);
            $payload=json_encode($responses,JSON_UNESCAPED_UNICODE);
            if($id>0) {
                $st=$pdo->prepare("UPDATE records SET person_name=?, record_date=?, document_id=?, responses=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
                $st->execute([$name,$date,$doc,$payload,$id]);
                if(!$st->rowCount()) { $check=$pdo->prepare("SELECT id FROM records WHERE id=?");$check->execute([$id]);if(!$check->fetch())$id=0; }
            }
            if($id===0) {
                $st=$pdo->prepare("INSERT INTO records(person_name,record_date,document_id,responses) VALUES(?,?,?,?)");
                $st->execute([$name,$date,$doc,$payload]); $id=(int)$pdo->lastInsertId();
            }
            json_out(['ok'=>true,'id'=>$id,'saved_at'=>date('Y-m-d H:i:s')]);
        }
        if ($action==='delete') {
            if ($_SERVER['REQUEST_METHOD']!=='POST') json_out(['error'=>'POST required'],405);
            $in=json_decode(file_get_contents('php://input'),true)?:[];
            $st=$pdo->prepare("DELETE FROM records WHERE id=?");$st->execute([(int)($in['id']??0)]);
            json_out(['ok'=>true]);
        }
        json_out(['error'=>'Unknown action'],404);
    } catch(Throwable $e) { json_out(['error'=>'Database error. Check PHP database permissions and configuration.','detail'=>$e->getMessage()],500); }
}
$selected = $_GET['doc'] ?? $docs[0]['id'];
if(!in_array($selected,array_column($docs,'id'),true)) $selected=$docs[0]['id'];
$activeDoc=$docs[array_search($selected,array_column($docs,'id'),true)];
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Anger Management — Fillable Records</title>
<style>
:root{--ink:#202733;--muted:#667085;--line:#d7dce3;--blue:#2457a7;--paper:#fff;--bg:#f3f5f8}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.45 system-ui,-apple-system,Segoe UI,sans-serif}
header{background:#172b4d;color:#fff;padding:16px 22px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}header h1{font-size:20px;margin:0}header small{color:#d8e2f2}
main{max-width:1500px;margin:auto;padding:18px}.sticky-area{position:sticky;top:0;z-index:100;background:var(--bg);padding:0 0 8px;box-shadow:0 5px 10px #172b4d12}.toolbar{display:grid;grid-template-columns:2fr 1fr 1fr 2fr auto auto;gap:10px;background:#fff;padding:14px;border:1px solid var(--line);border-radius:10px;align-items:end}
label{display:block;font-size:12px;font-weight:700;color:#475467;margin-bottom:5px}input,select,textarea{font:inherit;color:inherit;background:#fff;border:1px solid #cbd2dc;border-radius:6px;padding:9px;width:100%}button{border:0;border-radius:6px;padding:10px 13px;font-weight:700;cursor:pointer;background:var(--blue);color:#fff}button.secondary{background:#e9eef6;color:#243b5a}button:disabled{opacity:.5;cursor:wait}.status{font-size:12px;color:var(--muted);padding-top:6px;min-height:23px;background:var(--bg)}.layout{display:grid;grid-template-columns:minmax(0,1fr) 310px;gap:18px;margin-top:18px}.document-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0 0 12px}.document-head h2{font-size:18px;margin:0}.pages{display:grid;gap:18px}.page{background:white;border:1px solid var(--line);border-radius:8px;padding:12px;box-shadow:0 2px 6px #172b4d0b}.page-canvas{position:relative;width:100%;line-height:0;cursor:crosshair}.page-canvas img{display:block;width:100%;height:auto;border:1px solid #e5e7eb}.page-hint{font-size:12px;color:var(--muted);margin:6px 0 10px}.overlay-tool{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0 0 12px;padding:10px;background:#eef3fb;border:1px solid #d5dfef;border-radius:8px}.overlay-tool label{margin:0}.page-overlay{position:absolute;z-index:2;line-height:1.2;box-sizing:border-box}.page-overlay.text,.page-overlay.other{width:24%;padding:3px 5px;border:1px solid #2457a7;border-radius:3px;background:#ffffffeb;font:14px/1.25 Arial,sans-serif;resize:both;overflow:auto}.page-overlay.other{border-style:dashed}.page-overlay.check{width:24px;height:24px;min-width:24px;padding:0;accent-color:#2457a7;cursor:pointer}.overlay-remove{position:absolute;top:-9px;right:-9px;width:18px;height:18px;padding:0;border-radius:50%;font-size:12px;line-height:18px;background:#a12c2c;color:white;z-index:3}.page-label{display:flex;justify-content:space-between;color:var(--muted);font-size:12px;margin:0 0 8px}.answer{margin-top:12px}.answer textarea{min-height:95px;resize:vertical}.side{background:#fff;border:1px solid var(--line);border-radius:10px;padding:14px;height:max-content;position:sticky;top:12px}.side h3{margin:0 0 6px;font-size:16px}.side p{color:var(--muted);font-size:13px;margin:0 0 12px}.history{display:grid;gap:8px;margin-top:12px}.history button{background:#f3f6fa;color:#243b5a;border:1px solid #dbe2ec;text-align:left;font-weight:500}.history button strong{display:block}.history button span{font-size:12px;color:#667085}.score{margin-top:18px;border-top:1px solid var(--line);padding-top:14px}.score .total{font-size:24px;font-weight:800}.score-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}.score-grid input{padding:7px}.empty{color:var(--muted);font-size:13px;padding:10px 0}.print-only{display:none}
@media(max-width:1050px){.toolbar{grid-template-columns:1fr 1fr}.layout{grid-template-columns:1fr}.side{position:static;grid-row:1}.history{grid-template-columns:1fr 1fr}}
@media(max-width:600px){main{padding:10px}.toolbar{grid-template-columns:1fr}.history{grid-template-columns:1fr}}
@media print{body{background:#fff;font-size:11pt}header,.toolbar,.side,.document-head,.answer .status,.no-print,.overlay-tool,.overlay-remove{display:none!important}main{max-width:none;margin:0;padding:0}.layout{display:block;margin:0}.pages{gap:0}.page{border:0;border-radius:0;box-shadow:none;padding:0;margin:0;break-after:page;page-break-after:always}.page-canvas img{width:100%;max-height:9.1in;object-fit:contain;border:0}.page-hint{display:none!important}.answer{margin:8px 0 0;break-inside:avoid}.answer label{font-size:10pt}.answer textarea{min-height:1.15in;border:1px solid #888;white-space:pre-wrap}.print-only{display:block}}
</style></head><body>
<header><div><h1>Anger Management — Fillable Workbook</h1><small>Original worksheet pages • autosaved records • print-ready</small></div><button class="secondary no-print" onclick="window.print()">Print</button></header>
<main>
<div class="sticky-area">
<div class="toolbar no-print">
 <div><label for="personName">Client / participant name</label><input id="personName" autocomplete="name" placeholder="Enter name"></div>
 <div><label for="recordDate">Record date</label><input id="recordDate" type="date" value="<?=htmlspecialchars(date('Y-m-d'))?>"></div>
 <div><label for="docSelect">Worksheet</label><select id="docSelect"><?php foreach($docs as $d):?><option value="<?=htmlspecialchars($d['id'])?>" <?=$d['id']===$selected?'selected':''?>><?=htmlspecialchars($d['title'])?></option><?php endforeach;?></select></div>
 <div><label for="historySelect">History — name and date</label><select id="historySelect"><option value="">Loading saved records…</option></select></div>
 <button id="newBtn" class="secondary">New record</button><button id="saveBtn">Save now</button>
</div>
<div class="status no-print" id="saveStatus" role="status">Enter a name to begin. Changes save automatically.</div>
<div class="document-head"><h2><?=htmlspecialchars($activeDoc['title'])?></h2><span class="no-print" id="pageCount"><?=count($activeDoc['pages'])?> pages</span></div>
<div class="overlay-tool no-print"><strong>On-page tool:</strong><label for="overlayMode">Choose what to place</label><select id="overlayMode" style="width:auto;min-width:170px"><option value="text">Text box</option><option value="check">Checkmark box</option><option value="other">Other</option></select><span>Click and drag on a worksheet page to size a text box or Other item. Click to place a checkmark. Drag placed items to move them; use × to remove.</span></div>
</div>
<div class="layout">
 <section>
  <div class="pages" id="pages">
  <?php foreach($activeDoc['pages'] as $p):?>
   <article class="page" data-page="<?=$p['number']?>">
    <div class="page-label"><span><?=htmlspecialchars($activeDoc['title'])?></span><span>Page <?=$p['number']?> of <?=count($activeDoc['pages'])?></span></div>
    <div class="page-canvas" data-canvas-page="<?=$p['number']?>"><img src="<?=htmlspecialchars($p['image'])?>" alt="<?=htmlspecialchars($activeDoc['title'].' page '.$p['number'])?>"></div>
    <div class="answer"><label for="responsePage<?=$p['number']?>">Responses for page <?=$p['number']?></label><textarea id="responsePage<?=$p['number']?>" data-response-page="<?=$p['number']?>" placeholder="Enter your answers for this page..."></textarea></div>
   </article>
  <?php endforeach;?>
  </div>
 </section>
 <aside class="side no-print">
  <h3>Saved record history</h3><p>Select a record to reload and continue editing. Records are grouped by worksheet, with name and date shown.</p>
  <div id="historyList" class="history"><div class="empty">Loading…</div></div>
  <div class="score">
   <h3>Anger Styles — auto-calculated score</h3><p>For the 10 Anger Styles statements, rate each item from 1 (Never) to 5 (Almost always). Total and average update automatically. Use this when working on the Anger Styles worksheet.</p>
   <div class="score-grid" id="styleRatings"><?php for($i=1;$i<=10;$i++):?><div><label for="style<?=$i?>">Statement <?=$i?></label><select id="style<?=$i?>" data-rating="<?=$i?>"><option value="">—</option><option value="1">1 — Never</option><option value="2">2 — Rarely</option><option value="3">3 — Sometimes</option><option value="4">4 — Often</option><option value="5">5 — Almost always</option></select></div><?php endfor;?></div>
   <p style="margin-top:12px">Completed ratings: <strong id="ratingCount">0 / 10</strong></p><div class="total" id="ratingTotal">Total: 0 / 50</div><p id="ratingAverage">Average: —</p>
   <div style="margin-top:12px"><label for="dirtyTotal">Dirty Fighting Techniques (Workbook page 25) — total of 5 ratings, each 0–3</label><div class="score-grid"><?php for($i=1;$i<=5;$i++):?><div><label for="dirty<?=$i?>">Technique <?=$i?></label><select id="dirty<?=$i?>" data-dirty="<?=$i?>"><option value="">—</option><option value="0">0 — Never</option><option value="1">1 — Rarely</option><option value="2">2 — Sometimes</option><option value="3">3 — Often</option></select></div><?php endfor;?></div><div class="total" id="dirtyTotal">Total: 0 / 15</div></div>
  </div>
 </aside>
</div>
</main>
<script>
const DOCS = <?=json_encode(array_map(fn($d)=>['id'=>$d['id'],'title'=>$d['title'],'pages'=>$d['pages']],$docs),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
let recordId=0, responses={}, saveTimer=null, saving=false, dirty=false;
const $=id=>document.getElementById(id);
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function status(s){$('saveStatus').textContent=s}
function currentDoc(){return $('docSelect').value}
function collect(){
 document.querySelectorAll('[data-response-page]').forEach(el=>responses['page_'+el.dataset.responsePage]=el.value);
 document.querySelectorAll('[data-canvas-page]').forEach(c=>{responses['overlays_page_'+c.dataset.canvasPage]=[...c.querySelectorAll('.page-overlay')].map(el=>({type:el.dataset.type,x:parseFloat(el.dataset.x),y:parseFloat(el.dataset.y),w:parseFloat(el.dataset.w||24),h:parseFloat(el.dataset.h||4),value:el.type==='checkbox'?el.checked:el.value}));});
 document.querySelectorAll('[data-rating]').forEach(el=>responses['style_'+el.dataset.rating]=el.value);
 document.querySelectorAll('[data-dirty]').forEach(el=>responses['dirty_'+el.dataset.dirty]=el.value);
}
function applyResponses(data){
 responses=data||{};
 document.querySelectorAll('[data-response-page]').forEach(el=>el.value=responses['page_'+el.dataset.responsePage]||'');
 document.querySelectorAll('[data-canvas-page]').forEach(c=>{c.querySelectorAll('.page-overlay,.overlay-remove').forEach(el=>el.remove());(responses['overlays_page_'+c.dataset.canvasPage]||[]).forEach(o=>addOverlay(c,o,false));});
 document.querySelectorAll('[data-rating]').forEach(el=>el.value=responses['style_'+el.dataset.rating]||'');
 document.querySelectorAll('[data-dirty]').forEach(el=>el.value=responses['dirty_'+el.dataset.dirty]||'');
 updateTotals(); dirty=false;
}
function updateTotals(){
 const vals=[...document.querySelectorAll('[data-rating]')].map(e=>e.value===''?null:Number(e.value)).filter(v=>v!==null);
 const sum=vals.reduce((a,b)=>a+b,0);
 $('ratingCount').textContent=`${vals.length} / 10`;
 $('ratingTotal').textContent=`Total: ${sum} / 50`;
 $('ratingAverage').textContent='Average: '+(vals.length?(sum/vals.length).toFixed(2):'—');
 const d=[...document.querySelectorAll('[data-dirty]')].map(e=>e.value===''?null:Number(e.value)).filter(v=>v!==null);
 $('dirtyTotal').textContent=`Total: ${d.reduce((a,b)=>a+b,0)} / 15 (${d.length}/5 rated)`;
}
function addOverlay(canvas,o,markDirty=true){
 const type=['check','other'].includes(o.type)?o.type:'text';const el=document.createElement(type==='check'?'input':'textarea');
 el.className='page-overlay '+type;el.dataset.type=type;el.dataset.x=Number(o.x)||0;el.dataset.y=Number(o.y)||0;el.dataset.w=Number(o.w)||(type==='check'?2.5:24);el.dataset.h=Number(o.h)||(type==='check'?2.5:4);
 el.style.left=el.dataset.x+'%';el.style.top=el.dataset.y+'%';
 if(type==='check'){el.type='checkbox';el.checked=!!o.value;el.setAttribute('aria-label','Checkmark');}
 else {el.value=typeof o.value==='string'?o.value:'';el.placeholder=type==='other'?'Other':'Type here';el.style.width=el.dataset.w+'%';el.style.height=Math.max(2.5,Number(el.dataset.h))+'%';el.rows=1;}
 canvas.appendChild(el);
 const rm=document.createElement('button');rm.type='button';rm.className='overlay-remove no-print';rm.textContent='×';rm.title='Remove item';rm.addEventListener('click',e=>{e.stopPropagation();el.remove();rm.remove();scheduleSave()});canvas.appendChild(rm);
 const positionRemove=()=>{rm.style.left=(el.offsetLeft+el.offsetWidth-7)+'px';rm.style.top=(el.offsetTop-7)+'px'};requestAnimationFrame(positionRemove);
 el.addEventListener('input',()=>scheduleSave());el.addEventListener('change',()=>scheduleSave());
 let dragging=false,startX=0,startY=0,origX=0,origY=0;
 el.addEventListener('pointerdown',e=>{if(e.target!==el)return;dragging=true;startX=e.clientX;startY=e.clientY;origX=parseFloat(el.dataset.x);origY=parseFloat(el.dataset.y);el.setPointerCapture(e.pointerId);});
 el.addEventListener('pointermove',e=>{if(!dragging)return;const r=canvas.getBoundingClientRect();const nx=Math.max(0,Math.min(100-el.offsetWidth/r.width*100,origX+(e.clientX-startX)/r.width*100));const ny=Math.max(0,Math.min(100-el.offsetHeight/r.height*100,origY+(e.clientY-startY)/r.height*100));el.dataset.x=nx;el.dataset.y=ny;el.style.left=nx+'%';el.style.top=ny+'%';positionRemove()});
 el.addEventListener('pointerup',()=>{if(dragging){dragging=false;scheduleSave()}});
 if(markDirty)scheduleSave();return el;
}
let drawing=null;
document.addEventListener('pointerdown',e=>{
 const canvas=e.target.closest('[data-canvas-page]');if(!canvas||e.target!==canvas&&e.target.tagName!=='IMG')return;
 const mode=$('overlayMode').value;const r=canvas.getBoundingClientRect();
 const x=Math.max(0,Math.min(100,(e.clientX-r.left)/r.width*100));const y=Math.max(0,Math.min(100,(e.clientY-r.top)/r.height*100));
 if(mode==='check'){addOverlay(canvas,{type:mode,x,y,w:2.5,h:2.5,value:false});return;}
 e.preventDefault();const el=addOverlay(canvas,{type:mode,x,y,w:24,h:4,value:''},false);drawing={canvas,el,startX:x,startY:y,pointerId:e.pointerId};canvas.setPointerCapture(e.pointerId);
});
document.addEventListener('pointermove',e=>{
 if(!drawing||drawing.pointerId!==e.pointerId)return;const r=drawing.canvas.getBoundingClientRect();
 const x=Math.max(0,Math.min(100,(e.clientX-r.left)/r.width*100));const y=Math.max(0,Math.min(100,(e.clientY-r.top)/r.height*100));
 const left=Math.min(drawing.startX,x),top=Math.min(drawing.startY,y),width=Math.max(2,Math.abs(x-drawing.startX)),height=Math.max(2.5,Math.abs(y-drawing.startY));
 Object.assign(drawing.el.dataset,{x:left,y:top,w:width,h:height});Object.assign(drawing.el.style,{left:left+'%',top:top+'%',width:width+'%',height:height+'%'});
});
function finishDrawing(e){if(!drawing||drawing.pointerId!==e.pointerId)return;drawing=null;scheduleSave();}
document.addEventListener('pointerup',finishDrawing);document.addEventListener('pointercancel',finishDrawing);
function renderDoc(id){
 const d=DOCS.find(x=>x.id===id); if(!d)return;
 const root=$('pages');root.innerHTML='';
 d.pages.forEach(p=>{
  const article=document.createElement('article');article.className='page';article.dataset.page=p.number;
  article.innerHTML=`<div class="page-label"><span>${esc(d.title)}</span><span>Page ${p.number} of ${d.pages.length}</span></div><div class="page-canvas" data-canvas-page="${p.number}"><img src="${esc(p.image)}" alt="${esc(d.title)} page ${p.number}"></div><div class="answer"><label for="responsePage${p.number}">Responses for page ${p.number}</label><textarea id="responsePage${p.number}" data-response-page="${p.number}" placeholder="Enter your answers for this page..."></textarea></div>`;
  root.appendChild(article);
 });
 $('pageCount').textContent=d.pages.length+' pages';
 document.querySelector('.document-head h2').textContent=d.title;
 responses={}; recordId=0; applyResponses({}); dirty=false;
 history.replaceState(null,'','?doc='+encodeURIComponent(id));
}
async function api(url,opts={}){const r=await fetch(url,opts);const j=await r.json();if(!r.ok)throw Error(j.error||'Request failed');return j}
async function refreshHistory(){
 try{
  const rows=await api('?api=history');const sel=$('historySelect');
  sel.innerHTML='<option value="">— Select saved record —</option>'+rows.map(r=>`<option value="${r.id}">${esc(r.person_name)} — ${esc(r.record_date)} — ${esc(DOCS.find(d=>d.id===r.document_id)?.title||r.document_id)}</option>`).join('');
  $('historyList').innerHTML=rows.length?rows.map(r=>`<button type="button" data-load-id="${r.id}"><strong>${esc(r.person_name)}</strong><span>${esc(r.record_date)} · ${esc(DOCS.find(d=>d.id===r.document_id)?.title||r.document_id)}<br>Updated ${esc(r.updated_at||'')}</span></button>`).join(''):'<div class="empty">No saved records yet.</div>';
 }catch(e){status('Could not load history: '+e.message)}
}
async function loadRecord(id){
 if(dirty&&!confirm('You have unsaved changes. Load another record and discard them?'))return;
 try{
  const r=await api('?api=load&id='+encodeURIComponent(id));
  $('personName').value=r.person_name;$('recordDate').value=r.record_date;
  if(currentDoc()!==r.document_id){$('docSelect').value=r.document_id;renderDoc(r.document_id)}
  recordId=Number(r.id);applyResponses(r.responses);$('historySelect').value=String(recordId);
  status('Loaded record #'+recordId+' — edits will auto-save.');
 }catch(e){status('Could not load record: '+e.message)}
}
async function save(){
 collect();
 const name=$('personName').value.trim();
 if(!name){status('Enter a client / participant name before saving.');return}
 if(saving)return;saving=true;$('saveBtn').disabled=true;status('Saving…');
 try{
  const r=await api('?api=save',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:recordId,name,date:$('recordDate').value,document_id:currentDoc(),responses})});
  recordId=r.id;dirty=false;status('Saved automatically at '+r.saved_at+' · Record #'+recordId);
  await refreshHistory();$('historySelect').value=String(recordId);
 }catch(e){status('Save failed: '+e.message)}
 finally{saving=false;$('saveBtn').disabled=false}
}
function scheduleSave(){dirty=true;status('Changes pending…');clearTimeout(saveTimer);saveTimer=setTimeout(save,800)}
document.addEventListener('input',e=>{if(e.target.matches('[data-response-page]'))scheduleSave()});
document.addEventListener('change',e=>{if(e.target.matches('[data-rating],[data-dirty]')){updateTotals();scheduleSave()}});
$('personName').addEventListener('input',scheduleSave);$('recordDate').addEventListener('change',scheduleSave);
$('saveBtn').addEventListener('click',save);
$('historySelect').addEventListener('change',e=>{if(e.target.value)loadRecord(e.target.value)});
$('historyList').addEventListener('click',e=>{const b=e.target.closest('[data-load-id]');if(b)loadRecord(b.dataset.loadId)});
$('docSelect').addEventListener('change',e=>{
 if(dirty&&!confirm('Switch worksheets and discard the current unsaved edits?')){e.target.value=currentDoc();return}
 renderDoc(e.target.value);scheduleSave();
});
$('newBtn').addEventListener('click',()=>{
 if(dirty&&!confirm('Start a new record and discard unsaved changes?'))return;
 recordId=0;responses={};$('personName').value='';$('recordDate').value=new Date().toISOString().slice(0,10);
 $('historySelect').value='';applyResponses({});document.querySelectorAll('[data-response-page]').forEach(e=>e.value='');dirty=false;status('New record. Enter a name; edits will autosave.');
});
document.querySelectorAll('[data-rating],[data-dirty]').forEach(el=>el.addEventListener('change',updateTotals));
window.addEventListener('beforeprint',()=>collect());
refreshHistory();
</script></body></html>
