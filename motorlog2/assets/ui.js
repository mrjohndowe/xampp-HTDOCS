import {icon} from './icons.js';
export {icon};
export const boot=JSON.parse(document.getElementById('boot').textContent);
export const ui={data:null,page:'dashboard',filters:{},render:null,refresh:null,actions:{}};
export const $=(selector,scope=document)=>scope.querySelector(selector);
export const esc=x=>String(x??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
export const num=(v,max=3)=>v===null||v===undefined||v===''?'—':new Intl.NumberFormat('en-US',{maximumFractionDigits:max}).format(Number(v));
export function money(v,c=ui.data?.settings?.currency||'USD',digits){if(v===null||v===undefined||v===''||!Number.isFinite(Number(v)))return '—'; try{return new Intl.NumberFormat('en-US',{style:'currency',currency:c,...(digits===undefined?{}:{maximumFractionDigits:digits})}).format(Number(v));}catch{return `${c} ${num(v,2)}`;}}
export function ticks(input,digits){const s=String(input??'').trim();const m=/^(\d*)(?:\.(\d+))?$/.exec(s);if(!s||!m||s.length>25)return null;const fraction=(m[2]||'').replace(/0+$/,'');if(fraction.length>digits)return null;return BigInt(m[1]||'0')*10n**BigInt(digits)+BigInt(fraction.padEnd(digits,'0')||'0');}
export function liveFuel(gallons,rate,currency){const g=ticks(gallons,3),p=ticks(rate,4),digits=ui.data?.options.currencies[currency]??2;if(g===null||p===null||g<=0||g>10000000n||p>100000000n)return null;const factor=10n**BigInt(7-digits);return Number((g*p+factor/2n)/factor)/10**digits;}
export const unit=()=>ui.data?.settings.unit||'mi';
export const shortDate=d=>d?new Intl.DateTimeFormat('en-US',{day:'numeric',month:'short',year:'numeric',timeZone:'UTC'}).format(new Date(`${d.slice(0,10)}T12:00:00Z`)):'—';
export const day=()=>ui.data?.today||boot.today;
export const vehicle=id=>ui.data?.vehicles.find(v=>String(v.id)===String(id));
export const driver=id=>ui.data?.drivers.find(v=>String(v.id)===String(id));
export const yn=v=>v===true||Number(v)===1;
export const imageURL=id=>url('image',{id});
export function url(route,params={}){const out=new URL(location.pathname,location.origin);out.searchParams.set('r',route);for(const [k,v] of Object.entries(params))if(v!==''&&v!==null&&v!==undefined)out.searchParams.set(k,v);return `${out.pathname}${out.search}`;}
export function localGet(key){try{return localStorage.getItem(`motorlog.${key}`);}catch{return null;}}
export function localSet(key,value){try{localStorage.setItem(`motorlog.${key}`,value);}catch{}}
export function themeToggle(){const mode=document.documentElement.dataset.theme==='dark'?'light':'dark';document.documentElement.dataset.theme=mode;localSet('theme',mode);const button=$('[data-action="theme"]');if(button){button.innerHTML=icon(mode==='dark'?'sun':'moon',19);button.setAttribute('aria-label',mode==='dark'?'Switch to light mode':'Switch to dark mode');}return mode;}

export class ApiFault extends Error{constructor(message,code='',status=0){super(message);this.code=code;this.status=status;}}
export async function api(route,params={},options={}){
 const {data,key,signal}=options;const headers={Accept:'application/json'};
 if(data!==undefined){headers['Content-Type']='application/json';headers['X-CSRF-Token']=boot.csrf;if(key)headers['Idempotency-Key']=key;}
 let response;
 try{response=await fetch(url(route,params),{method:data===undefined?'GET':'POST',cache:'no-store',headers,body:data===undefined?undefined:JSON.stringify(data),signal:signal||AbortSignal.timeout(22000),credentials:'same-origin'});}catch(e){if(signal?.aborted)throw e;throw new ApiFault('Could not reach the workspace. Your input is still here; check the connection and retry.','offline');}
 let body;try{body=await response.json();}catch{throw new ApiFault('The server did not return a Motorlog response. Check the connection and try again.','bad_response',response.status);}
 if(!response.ok||!body.ok)throw new ApiFault(body.message||'The request could not be completed.',body.code,response.status);
 return body;
}
const retries=new Map();
function keygen(){if(globalThis.crypto?.randomUUID)return crypto.randomUUID();const b=new Uint8Array(18);globalThis.crypto.getRandomValues(b);return Array.from(b,v=>v.toString(16).padStart(2,'0')).join('');}
export async function save(entity,action,data,scope='root'){
 const payload={entity,action,data};const content=JSON.stringify(payload);const prev=retries.get(scope);const key=prev?.content===content?prev.key:keygen();retries.set(scope,{key,content});
 const result=await api('save',{}, {data:payload,key});if(retries.get(scope)?.key===key)retries.delete(scope);return result;
}
let toastTimer;
export function toast(message,kind='good'){
 let target=$('#toast');if(!target){target=document.createElement('div');target.id='toast';target.setAttribute('role','status');document.body.append(target);}
 target.innerHTML=`<div class="toast-box ${kind}">${icon(kind==='good'?'checkCircle':'alert',19)}<span>${esc(message)}</span></div>`;clearTimeout(toastTimer);toastTimer=setTimeout(()=>target.replaceChildren(),4200);
}
export function btn(label,action='',{iconName='',style='secondary',data='',small=false,title='',type='button',disabled=false}={}){
 return `<button type="${type}" class="btn ${esc(style)}${small?' sm':''}"${action?` data-action="${esc(action)}"`:''}${data!==''?` data-id="${esc(data)}"`:''}${title?` title="${esc(title)}"`:''}${disabled?' disabled':''}>${iconName?icon(iconName,small?15:17):''}${label?`<span>${esc(label)}</span>`:''}</button>`;
}
export function link(label,href,{iconName='',style='secondary',small=false,blank=false,download=false}={}){
 return `<a class="btn ${esc(style)}${small?' sm':''}" href="${esc(href)}"${blank?' target="_blank" rel="noopener"':''}${download?' download':''}>${iconName?icon(iconName,small?15:17):''}<span>${esc(label)}</span></a>`;
}
export function iconButton(name,action,label,data=''){return `<button type="button" class="icon-btn" data-action="${esc(action)}" ${data!==''?`data-id="${esc(data)}"`:''} aria-label="${esc(label)}" title="${esc(label)}">${icon(name,18)}</button>`;}
export const chip=(label,tone='neutral',sym='')=>`<span class="chip ${esc(tone)}">${sym?icon(sym,13):'<i></i>'}${esc(label)}</span>`;
export const face=(name='')=>name.trim().split(/\s+/).slice(0,2).map(w=>w[0]||'').join('').toUpperCase()||'•';
export const glyph=(name,tone='purple',size=22)=>`<span class="glyph ${esc(tone)}">${icon(name,size)}</span>`;
export const empty=(name,title,description,action='')=>`<div class="empty-state">${glyph(name,'purple',24)}<strong>${esc(title)}</strong><p>${esc(description)}</p>${action}</div>`;
export const banner=(message,tone='info')=>`<div class="banner ${esc(tone)}">${icon(tone==='danger'?'alert':tone==='success'?'checkCircle':'shield',17)}<span>${esc(message)}</span></div>`;
export const heading=(title,subtitle,actions='',tag='')=>`<section class="page-heading"><div>${tag?`<div class="eyebrow">${tag}</div>`:''}<h1>${esc(title)}</h1><p>${esc(subtitle)}</p></div><div class="buttons">${actions}</div></section>`;
export const block=(title,sub,content,right='')=>`<section class="panel"><div class="panel-head"><div><h2>${esc(title)}</h2>${sub?`<p>${esc(sub)}</p>`:''}</div>${right?`<div class="buttons">${right}</div>`:''}</div>${content}</section>`;
export const table=(headers,body,otherwise)=>body?`<div class="table-wrap"><table><thead><tr>${headers.map(h=>`<th scope="col">${esc(h)}</th>`).join('')}</tr></thead><tbody>${body}</tbody></table></div>`:otherwise;
export const td=(...parts)=>`<tr>${parts.map(h=>`<td>${h}</td>`).join('')}</tr>`;
export const titleCell=(title,sub='')=>`<span class="table-title">${esc(title)}</span>${sub?`<span class="table-sub">${esc(sub)}</span>`:''}`;

export function field(name,label,value='',options={}){
 const {type='text',choices,placeholder='',help='',required=false,wide=false,rows=3,attrs='',first='',readOnly=false}=options;
 const extra=`${required?' required':''}${readOnly?' readonly':''}${attrs?' '+attrs:''}`;
 const caption=`${esc(label)}${required?' <span class="star" aria-hidden="true">*</span>':''}`;
 if(type==='check')return `<label class="check-field${wide?' wide':''}"><input name="${esc(name)}" type="checkbox"${yn(value)?' checked':''}${extra}><span>${caption}${help?`<small>${esc(help)}</small>`:''}</span></label>`;
 let input;
 if(choices){input=`<select name="${esc(name)}"${extra}>${first!==null?`<option value="">${esc(first||'Select…')}</option>`:''}${choices.map(c=>{const [opt,label]=Array.isArray(c)?c:[c,c];return `<option value="${esc(opt)}"${String(opt)===String(value??'')?' selected':''}>${esc(label)}</option>`;}).join('')}</select>`;}
 else if(type==='textarea')input=`<textarea name="${esc(name)}" rows="${rows}" placeholder="${esc(placeholder)}"${extra}>${esc(value)}</textarea>`;
 else input=`<input type="${type==='decimal'?'text':esc(type)}" ${type==='decimal'?'inputmode="decimal" ':''}name="${esc(name)}" value="${type==='file'?'':esc(value)}" placeholder="${esc(placeholder)}"${extra}>`;
 return `<label class="field${wide?' wide':''}"><span>${caption}</span>${input}${help?`<small>${esc(help)}</small>`:''}</label>`;
}
export function values(form){const result={};for(const [k,v] of new FormData(form))if(typeof v==='string')result[k]=v;for(const el of form.querySelectorAll('input[type="checkbox"][name]')) result[el.name]=el.checked;return result;}
export const vehicleChoices=()=>ui.data?.vehicles.map(v=>[v.id,v.name])||[];
export const driverChoices=(selected=null,all=false)=>ui.data?.drivers.filter(d=>all||yn(d.active)||String(d.id)===String(selected)).map(d=>[d.id,`${d.name}${yn(d.active)?'':' (inactive)'}`])||[];
export const formGrid=body=>`<div class="form-grid">${body}</div>`;
export const section=(title,description,body)=>`<section class="form-section"><h3>${esc(title)}</h3>${description?`<p>${esc(description)}</p>`:''}${body}</section>`;
let dialogCount=0;
export function dialog(title,subtitle,html,{wide=false,className='',beforeClose}={}){
 const d=document.createElement('dialog');d.className=`modal${wide?' wide-modal':''} ${className}`;d.dataset.scope=`dialog-${++dialogCount}`;
 const name=`modal-title-${dialogCount}`;d.setAttribute('aria-labelledby',name);
 d.innerHTML=`<div class="modal-box"><div class="modal-head"><div><h2 id="${name}">${esc(title)}</h2>${subtitle?`<p>${esc(subtitle)}</p>`:''}</div><button type="button" class="icon-btn" data-close aria-label="Close dialog">${icon('close',20)}</button></div>${html}</div>`;
 document.body.append(d);d.addEventListener('close',()=>{d.remove();if(!$('.modal[open]'))document.body.classList.remove('modal-open');});
 const close=()=>{if(!beforeClose||beforeClose())d.close();};d.closeAction=close;
 d.addEventListener('click',e=>{if(e.target===d||e.target.closest('[data-close]'))close();});d.addEventListener('cancel',e=>{e.preventDefault();close();});
 document.body.classList.add('modal-open');d.showModal();return d;
}
export function inlineError(scope,error=''){const el=$('[data-error]',scope);if(el){el.classList.toggle('on',Boolean(error));el.innerHTML=error?banner(error,'danger'):'';}}
export function simpleForm({title,sub='',fields,entity,action='create',id,submit='Save changes',initializer,after,transform,wide=false}){
 const d=dialog(title,sub,`<form><div class="modal-body"><div data-error class="form-error" role="alert"></div>${fields}</div><div class="modal-foot"><button type="button" class="btn secondary" data-close>Cancel</button>${btn(submit,'',{style:'primary',iconName:'check',type:'submit'})}</div></form>`,{wide});
 const form=$('form',d);if(initializer)initializer(form,d);
 form.addEventListener('submit',async e=>{e.preventDefault();inlineError(d);const send=$('button[type="submit"]',form);send.disabled=true;
  try{const raw=values(form);const data=transform?await transform(raw,form):raw;if(id!==undefined)data.id=id;const result=await save(entity,action,data,d.dataset.scope);d.close();toast(result.message||'Saved');if(after)await after(result);else await ui.refresh?.();}
  catch(err){inlineError(d,err.message);}finally{send.disabled=false;}
 });return d;
}
export function confirmation(title,message,submit,onConfirm){const d=dialog(title,'',`<div class="modal-body"><p class="confirm-text">${esc(message)}</p><div data-error role="alert"></div></div><div class="modal-foot"><button type="button" class="btn secondary" data-close>Cancel</button><button class="btn danger" data-confirm>${esc(submit)}</button></div>`);const b=$('[data-confirm]',d);b.onclick=async()=>{b.disabled=true;try{await onConfirm(d);d.close();}catch(e){inlineError(d,e.message);}finally{b.disabled=false;}};return d;}

export function vehicleFields(v={},baseline=true){
 const make=v.make||'',model=v.model||'';
 return formGrid(field('kind','Vehicle type',v.kind||'car',{choices:[['car','Passenger automobile'],['motorcycle','Motorcycle']],first:null})+
 field('model_year','Model year',v.model_year,{type:'number',attrs:'min="1885" max="2100"',placeholder:'e.g. 2022'})+
 `<label class="field catalog-select"><span>Make</span><select data-cmake aria-label="Catalog make"><option value="">Loading makes…</option></select></label>`+
 `<label class="field catalog-select"><span>Model</span><select data-cmodel aria-label="Catalog model" disabled><option value="">Select a make first</option></select></label>`+
 `<div class="catalog-manual" data-manual-make>${field('make','Make name',make,{required:true,placeholder:'Enter a make'})}</div>`+
 `<div class="catalog-manual" data-manual-model>${field('model','Model name',model,{required:true,placeholder:'Enter a model'})}</div>`+
 `<small data-cstatus class="field-help wide">The built-in catalog works without internet. Select Other to enter a custom name.</small>`+
 field('vin','VIN / chassis number',v.vin,{placeholder:'Optional'})+field('plate','License plate',v.plate,{placeholder:'Optional'})+field('color','Color',v.color,{placeholder:'e.g. Graphite gray'})+
 (baseline?field('initial_odometer','Vehicle baseline odometer',v.initial_odometer??'',{type:'decimal',required:true,placeholder:'e.g. 120000'}):'')+
 field('notes','Vehicle notes',v.notes,{type:'textarea',wide:true}))+
 (baseline?section('Engine baseline','Keep the original or replacement engine’s distance separate from the vehicle odometer.',formGrid(field('at_odometer','Vehicle odometer at engine installation',v.at_odometer??'',{type:'decimal',help:'Leave blank to use the vehicle baseline.'})+field('at_engine','Engine mileage at installation',v.at_engine??'0',{type:'decimal',required:true})+field('installed_date','Installation date',v.installed_date,{type:'date'})+field('serial_number','Serial number',v.serial_number)+field('engine_notes','Engine notes',v.engine_notes,{type:'textarea',wide:true}))):'');
}
const localCatalog=new Map();
async function cat(type,make=''){
 const k=type+':'+make;if(localCatalog.has(k))return localCatalog.get(k);
 const data=await api('catalog',{type,make});localCatalog.set(k,data);return data;
}
export function setupCatalog(form){
 const kind=$('[name="kind"]',form),make=$('[data-cmake]',form),model=$('[data-cmodel]',form),fm=$('[name="make"]',form),fmo=$('[name="model"]',form);
 if(!kind||!make||!model)return;const cm=$('[data-manual-make]',form),cmo=$('[data-manual-model]',form),note=$('[data-cstatus]',form);
 let makes=[],models=[],version=0;
 const equal=(a,b)=>String(a).trim().toLocaleLowerCase()===String(b).trim().toLocaleLowerCase();
 const pk=(items,first)=>`<option value="">${esc(first)}</option>${items.map(m=>`<option value="${esc(m.id)}">${esc(m.name)}</option>`).join('')}<option value="custom">Other / enter manually</option>`;
 function mode(el,input,manual){el.hidden=!manual;input.required=manual;}
 async function loadModels(keep=false){
  const current=++version,id=make.value,type=kind.value;const old=keep?fmo.value:'';
  if(!keep)fmo.value='';model.value='';model.disabled=true;model.innerHTML='<option value="">Select a make first</option>';mode(cmo,fmo,false);
  if(!id||id==='custom'){mode(cmo,fmo,true);return;}
  model.innerHTML='<option value="">Loading models…</option>';
  try{const r=await cat(type,id);if(current!==version||type!==kind.value||id!==make.value)return;
   models=r.models;model.innerHTML=pk(models,models.length?'Select a model…':'No models available');model.disabled=false;
   const match=old&&models.find(m=>equal(m.name,old));if(match){model.value=String(match.id);fmo.value=old;}
   else if(old||!models.length){model.value='custom';mode(cmo,fmo,true);fmo.value=old;}
   note.textContent=models.length?'Built-in vPIC catalog · Select Other for a custom model.':'No catalog models for this make and type. Enter the model manually.';
  }catch(e){if(current!==version)return;model.innerHTML=pk([],'Model lookup unavailable');model.value='custom';model.disabled=false;mode(cmo,fmo,true);note.textContent=e.message;}
 }
 async function loadMakes(keep=false){
  const current=++version,type=kind.value,old=keep?fm.value:'';if(!keep){fm.value='';fmo.value='';}
  make.disabled=true;model.disabled=true;make.innerHTML='<option>Loading makes…</option>';model.innerHTML='<option>Select a make first</option>';
  try{const r=await cat(type);if(current!==version||type!==kind.value)return;makes=r.makes;make.innerHTML=pk(makes,'Select a make…');make.disabled=false;
   const match=old&&makes.find(m=>equal(m.name,old));if(match){make.value=String(match.id);mode(cm,fm,false);await loadModels(true);}
   else{if(old){make.value='custom';mode(cm,fm,true);}else mode(cm,fm,false);mode(cmo,fmo,Boolean(old));note.textContent=`Built-in vPIC catalog · ${num(makes.length)} ${type==='car'?'automobile':'motorcycle'} makes. Select Other to enter a custom name.`;}
  }catch(e){if(current!==version)return;make.innerHTML=pk([],'Catalog unavailable');make.value='custom';make.disabled=false;mode(cm,fm,true);mode(cmo,fmo,true);note.textContent=e.message;}
 }
 kind.addEventListener('change',()=>loadMakes(false));
 make.addEventListener('change',()=>{const match=makes.find(m=>String(m.id)===make.value);fm.value=match?.name||'';mode(cm,fm,make.value==='custom');loadModels(false);});
 model.addEventListener('change',()=>{const m=models.find(m=>String(m.id)===model.value);fmo.value=m?.name||'';mode(cmo,fmo,model.value==='custom');});
 loadMakes(true);
}

export async function photoDialog({owner_kind,owner_id,item_key='',after}){
 const isVehicle=owner_kind==='vehicle';let objectURL='';
 const d=simpleForm({title:isVehicle?'Upload vehicle photo':'Attach inspection photo',sub:'JPEG or PNG · Up to 5 MB. Images stay private to this workspace.',entity:'photo',action:'upload',submit:'Upload photo',fields:
  formGrid(field('file','Image','',{type:'file',attrs:'accept="image/png,image/jpeg"',required:true,wide:true})+`<div class="upload-preview wide" data-upload-preview>${icon('photo',26)}<span>Selected image preview</span></div>`+
  field('caption','Caption','',{wide:true,placeholder:'Describe what this photo shows'})+(isVehicle?field('view_label','View','Front',{choices:ui.data.options.views,first:null})+field('cover','Set as vehicle cover',false,{type:'check'}):'')),
  initializer(form){$('[name="file"]',form).onchange=e=>{if(objectURL)URL.revokeObjectURL(objectURL);const file=e.target.files[0];if(file){objectURL=URL.createObjectURL(file);$('[data-upload-preview]',form).innerHTML=`<img src="${esc(objectURL)}" alt="Upload preview">`;}};},
  async transform(raw,form){const file=$('[name="file"]',form).files[0];if(!file||file.size>5242880)throw new Error('Select a JPEG or PNG up to 5 MB.');const b64=await new Promise((resolve,reject)=>{const r=new FileReader();r.onload=()=>resolve(String(r.result).split(',')[1]);r.onerror=()=>reject(new Error('The selected file could not be read.'));r.readAsDataURL(file);});const id=typeof owner_id==='function'?await owner_id():owner_id;return {...raw,owner_kind,owner_id:id,item_key,image:b64};},after});
 d.addEventListener('close',()=>{if(objectURL)URL.revokeObjectURL(objectURL);});return d;
}
export function viewPhoto(p){dialog(p.caption||'Photo evidence',p.view_label||'',`<div class="modal-body photo-view"><img src="${esc(imageURL(p.id))}" alt="${esc(p.caption||'Private workspace photo')}"></div>`);}
