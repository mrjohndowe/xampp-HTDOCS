(() => {
  const $ = (selector, root=document) => root.querySelector(selector);
  const app = $('#app');
  const csrfMeta = $('meta[name="pv-csrf"]');
  let csrf = csrfMeta?.content || '';
  let user = null;
  let counts = {};
  let page = 'dashboard';
  let module = 'contacts';
  let items = [];
  let contacts = [];
  let current = null;
  let currentData = {};
  let dirtyTimer = null;
  let saving = false;
  let pendingSave = false;
  let saveError = '';
  let searchText = '';
  let typeFilter = '';
  let sortMode = 'updated';
  let dateFrom = '';
  let dateTo = '';
  let ratingFilter = '';
  let tagFilter = '';
  let pageNumber = 0;
  let trashMode = false;
  const pageSize = 30;
  const moduleMeta = {
    contacts:{title:'Contacts',singular:'Contact',icon:'◉',description:'Your private directory and relationship details.',plural:'contacts'},
    accounts:{title:'Accounts & passwords',singular:'Account',icon:'▣',description:'Encrypted credentials, recovery details, and financial records.',plural:'managed accounts'},
    journal:{title:'Personal journal',singular:'Entry',icon:'▤',description:'Your private notes, reflections, and personal history.',plural:'journal entries'},
    intimacy:{title:'Relationship journal',singular:'Event',icon:'♡',description:'Optional private relationship and event notes for consenting adults.',plural:'private events'}
  };
  const relationships=['Mother','Father','Brother','Sister','Son','Daughter','Friend','Best-Friend','Family','Work','Girl-Friend','Boy-Friend','Partner','Ex','Fling','Friends with Benefits','One Night Stand','Other'];
  const accountTypes=['Website or online service','Email account','Social media account','Gaming account','Subscription','Banking account','Credit or debit card record','Investment account','Utility account','Other'];
  const contactField = {key:'contact_id',label:'Linked contact',type:'contact',hint:'Optional. This remains separate from application login accounts.'};
  const schemas = {
    contacts:[
      {key:'first_name',label:'First name',required:true},{key:'middle_name',label:'Middle name'},{key:'last_name',label:'Last name'},
      {key:'display_name',label:'Preferred / display name'},{key:'date_of_birth',label:'Date of birth',type:'date',hint:'Age is calculated from this date and is never stored.'},
      {key:'email',label:'Email',type:'email'},{key:'phone',label:'Phone'},{key:'secondary_phone',label:'Secondary phone'},
      {key:'relationship',label:'Relationship',type:'datalist',options:relationships},{key:'company',label:'Company or organization'},{key:'job_title',label:'Job title'},
      {key:'street',label:'Street address',full:true},{key:'city',label:'City'},{key:'state',label:'State / region'},{key:'zip_code',label:'ZIP / postal code'},
      {key:'notes',label:'General notes',type:'textarea',full:true},{key:'tags',label:'Tags or categories',hint:'Separate tags with commas.',full:true}
    ],
    accounts:[
      contactField,{key:'service_name',label:'Account or service name',required:true},{key:'account_type',label:'Account type',type:'datalist',options:accountTypes},
      {key:'website',label:'Website or service URL',type:'url',full:true},{key:'username',label:'Username'},{key:'login_email',label:'Login email',type:'email'},
      {key:'password',label:'Password or secret',type:'secret',sensitive:true},{key:'secret',label:'Other secret',type:'secret',sensitive:true},
      {key:'recovery_email',label:'Recovery email',type:'email'},{key:'security_answers',label:'Security questions and answers',type:'secretarea',sensitive:true,full:true},
      {key:'recovery_codes',label:'Recovery codes',type:'secretarea',sensitive:true,full:true},
      {key:'bank_name',label:'Bank or institution',bank:true},{key:'account_holder',label:'Account holder',bank:true},
      {key:'routing_number',label:'Routing number',type:'secret',sensitive:true,bank:true},{key:'account_number',label:'Account number',type:'secret',sensitive:true,bank:true},
      {key:'bank_account_type',label:'Bank account type',type:'select',options:['Checking','Savings','Credit','Investment','Other'],bank:true},
      {key:'notes',label:'Account notes',type:'textarea',full:true},{key:'tags',label:'Tags or categories',hint:'Separate tags with commas.',full:true}
    ],
    journal:[
      {key:'title',label:'Title',required:true},{key:'entry_date',label:'Entry date and time',type:'datetime-local'},
      {key:'category',label:'Category',type:'select',options:['Personal','Reflection','Gratitude','Health','Work','Goals','Other']},contactField,
      {key:'body',label:'Journal entry',type:'textarea',full:true,hint:'Plain text is stored encrypted and displayed as text.'},
      {key:'mood',label:'Mood or rating',type:'select',options:['','1 · Very low','2','3','4','5 · Neutral','6','7','8','9','10 · Very positive']},
      {key:'private_notes',label:'Private notes',type:'textarea',full:true},{key:'tags',label:'Tags',hint:'Separate tags with commas.',full:true}
    ],
    intimacy:[
      contactField,{key:'event_date',label:'Event date',type:'date',required:true},{key:'start_time',label:'Start time',type:'time'},{key:'end_time',label:'End time',type:'time'},
      {key:'event_type',label:'Event type',type:'datalist',options:['Date','Romantic encounter','Affectionate activity','Intimate encounter','Sexual activity','Other']},
      {key:'location',label:'Location or setting'},{key:'privacy',label:'Privacy level',type:'select',options:['Private','Highly private']},
      {key:'description',label:'General description',type:'textarea',full:true},{key:'personal_notes',label:'Personal notes',type:'textarea',full:true},
      ...[
        'What type of encounter or activity occurred?',
        'Was the encounter consensual and mutually agreed upon?',
        'Did everyone involved feel comfortable participating?',
        'How would you rate the overall experience?',
        'How would you rate your emotional connection?',
        'How would you rate the communication?',
        'How would you rate the level of comfort and trust?',
        'How satisfied were you with the experience?',
        'How would you describe your mood before the event?',
        'How would you describe your mood afterward?',
        'What did you particularly enjoy?',
        'Was there anything you disliked or would change?',
        'Were any boundaries discussed or respected?',
        'Was there anything new you learned about your preferences?',
        'Is there anything you would like to remember for next time?',
        'Are there any additional notes you want to record?'
      ].map((label,i)=>({key:`question_${i+1}`,label,type:i===1||i===2?'consent':i===3||i===4||i===5||i===6||i===7?'rating':'textarea',full:true})),
      {key:'activity_details',label:'Activity details',type:'textarea',full:true},{key:'preferences',label:'Preferences',type:'textarea',full:true},
      {key:'boundaries',label:'Boundaries',type:'textarea',full:true},{key:'communication_notes',label:'Communication notes',type:'textarea',full:true},
      {key:'emotional_reactions',label:'Emotional reactions',type:'textarea',full:true},{key:'relationship_context',label:'Relationship context',type:'textarea',full:true},
      {key:'follow_up',label:'Follow-up notes',type:'textarea',full:true},{key:'custom_qa',label:'Additional custom questions and answers',type:'textarea',full:true},
      {key:'rating_overall',label:'Overall experience rating',type:'rating'},{key:'rating_enjoyment',label:'Enjoyment rating',type:'rating'},
      {key:'rating_emotional_connection',label:'Emotional connection rating',type:'rating'},{key:'rating_communication',label:'Communication rating',type:'rating'},
      {key:'rating_comfort',label:'Comfort rating',type:'rating'},{key:'rating_trust',label:'Trust rating',type:'rating'},
      {key:'rating_satisfaction',label:'Satisfaction rating',type:'rating'},{key:'rating_after_event',label:'Personal after-event feelings',type:'rating'},
      {key:'tags',label:'Tags',hint:'Separate tags with commas.',full:true}
    ]
  };

  async function request(action, body={}) {
    const response=await fetch(`api.php?action=${encodeURIComponent(action)}`, {method:action==='bootstrap'?'GET':'POST',headers:action==='bootstrap'?{}:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:action==='bootstrap'?undefined:JSON.stringify(body),credentials:'same-origin'});
    const data=await response.json().catch(()=>({error:'The server returned an unreadable response.'}));
    if(response.status===419){location.reload();throw new Error(data.error||'Reloading your session.');}
    if(!response.ok)throw new Error(data.error||'The request could not be completed.');
    if(data.csrf){csrf=data.csrf;if(csrfMeta)csrfMeta.content=csrf;}
    return data;
  }
  function el(tag, cls, text){const node=document.createElement(tag);if(cls)node.className=cls;if(text!==undefined)node.textContent=text;return node;}
  function button(label, cls, onClick, title){const b=el('button',`button ${cls||'secondary'}`,label);b.type='button';if(title)b.title=title;b.addEventListener('click',onClick);return b;}
  function applyTheme(theme){document.documentElement.dataset.theme=theme;}
  function toast(message){const t=el('div','toast',message);document.body.append(t);setTimeout(()=>t.remove(),2800);}
  function renderLogin(){
    app.replaceChildren();const shell=el('main','login-shell'),card=el('section','login-card');
    const brand=el('div','brand');brand.append(el('span','brand-mark','P'));const word=el('span','','PersonVault');word.append(el('small','','Private by design'));brand.append(word);
    card.append(brand,el('p','eyebrow','YOUR PRIVATE SPACE'),el('h1','','Sign in to your vault'),el('p','','Your personal records stay encrypted and tied to your account.'));
    const notice=el('div','notice error hidden');notice.setAttribute('role','alert');card.append(notice);
    const form=el('form');form.autocomplete='on';
    const email=fieldControl({key:'email',label:'Email',type:'email'},'');email.input.autocomplete='username';email.input.required=true;
    const pass=fieldControl({key:'password',label:'Password',type:'password'},'');pass.input.autocomplete='current-password';pass.input.required=true;
    form.append(email.wrap,pass.wrap);const submit=button('Unlock my vault','primary',()=>{});submit.type='submit';form.append(submit);form.addEventListener('submit',async event=>{event.preventDefault();notice.classList.add('hidden');submit.disabled=true;try{const result=await request('login',{email:email.input.value,password:pass.input.value});csrf=result.csrf;const start=await request('bootstrap');user=start.user;counts=start.counts||{};applyTheme(user.theme||'system');page='dashboard';render();}catch(error){notice.textContent=error.message;notice.classList.remove('hidden');submit.disabled=false;}});
    card.append(form,el('div','login-foot','Private, self-hosted storage · Sessions are protected'));shell.append(card);app.append(shell);
  }
  function navItem(key,label,icon,count){const b=el('button','nav-link');b.type='button';if(page===key||(module===key&&page==='module'))b.classList.add('active');b.append(el('span','nav-icon',icon),el('span','',label));if(count!==undefined)b.append(el('span','nav-count',String(count)));b.addEventListener('click',()=>{if(key==='dashboard'){page='dashboard';}else if(moduleMeta[key]){page='module';module=key;}else{page=key;}pageNumber=0;trashMode=false;searchText='';typeFilter='';current=null;render();if(page==='module')loadItems();closeMobile();});return b;}
  function renderShell(){
    app.replaceChildren();const shell=el('div','app-shell'),side=el('aside','sidebar');side.id='sidebar';
    const brand=el('a','brand');brand.href='#';brand.addEventListener('click',e=>{e.preventDefault();page='dashboard';render();});brand.append(el('span','brand-mark','P'));const name=el('span','', 'PersonVault');name.append(el('small','','Private by design'));brand.append(name);side.append(brand);
    side.append(el('div','nav-caption','YOUR VAULT'));const nav=el('nav','nav-list');nav.append(navItem('dashboard','Overview','⌂'));
    for(const key of Object.keys(moduleMeta))nav.append(navItem(key,moduleMeta[key].title,moduleMeta[key].icon,counts[key]||0));side.append(nav);
    side.append(el('div','nav-caption','PREFERENCES'));const prefs=el('nav','nav-list');prefs.append(navItem('settings','Settings & backup','⚙'));if(user.role==='admin')prefs.append(navItem('users','User management','♙'));side.append(prefs);
    const bottom=el('div','sidebar-bottom');const profile=el('div','profile-mini');profile.append(el('span','avatar',(user.email||'U').slice(0,1).toUpperCase()));const who=el('div');who.append(el('strong','',user.email||''),el('small','',user.role==='admin'?'Administrator':'Personal account'));profile.append(who);bottom.append(profile,button('Sign out','ghost small-button',logout));side.append(bottom);
    const overlay=el('div','menu-overlay');overlay.addEventListener('click',closeMobile);
    const column=el('div','main-column'),top=el('header','topbar'),left=el('div','topbar-left');const menu=button('☰','secondary small-button',openMobile);menu.classList.add('mobile-menu');left.append(menu);left.append(el('h2','',pageTitle()));top.append(left);const right=el('div','topbar-right');const status=el('span','muted install-status');status.append(el('span','status-dot'),el('span','','Encrypted storage active'));right.append(status);top.append(right);
    const content=el('main','content');content.id='content';column.append(top,content);shell.append(side,overlay,column);app.append(shell);
  }
  function pageTitle(){if(page==='module')return trashMode?`${moduleMeta[module].title} · Trash`:moduleMeta[module].title;if(page==='settings')return'Settings & backup';if(page==='users')return'User management';return'Overview';}
  function closeMobile(){$('#sidebar')?.classList.remove('open');$('.menu-overlay')?.classList.remove('show');}
  function openMobile(){$('#sidebar')?.classList.add('open');$('.menu-overlay')?.classList.add('show');}
  async function logout(){try{await request('logout',{});}catch{}user=null;current=null;renderLogin();}
  function render(){if(!user){renderLogin();return;}renderShell();const content=$('#content');if(page==='dashboard')renderDashboard(content);else if(page==='module')renderModule(content);else if(page==='settings')renderSettings(content);else if(page==='users')renderUsers(content);}
  function heading(title,subtitle,action){const row=el('div','page-heading');const copy=el('div');copy.append(el('h1','',title),el('p','',subtitle));row.append(copy);if(action)row.append(action);return row;}
  function renderDashboard(target){
    const card=el('section','welcome-card');card.append(el('p','eyebrow','YOUR PRIVATE SPACE'),el('h1','',`Welcome back, ${user.email.split('@')[0]}`),el('p','','A calm, private place for the people, memories, accounts, and moments you choose to keep.'));card.append(button('Open your contacts','',()=>openModule('contacts')));target.append(card);
    const stats=el('div','stats-grid');for(const [key,meta] of Object.entries(moduleMeta)){const c=el('div','stat-card');c.append(el('span','stat-icon',meta.icon));const words=el('div');words.append(el('strong','',String(counts[key]||0)),el('small','',meta.plural));c.append(words);stats.append(c);}target.append(stats);
    const title=el('div','section-title');title.append(el('h2','','Your modules'));title.append(el('span','muted','Private to your account'));target.append(title);
    const grid=el('div','module-grid');for(const [key,meta] of Object.entries(moduleMeta)){const a=el('a','module-card');a.href='#';a.addEventListener('click',event=>{event.preventDefault();openModule(key);});a.append(el('span','stat-icon',meta.icon));const text=el('div');text.append(el('strong','',meta.title),el('p','',meta.description));a.append(text);grid.append(a);}target.append(grid);
    const note=el('p','footer-note','Records are encrypted with authenticated encryption before they are written to the database. The SQLite database file itself is not encrypted.');target.append(note);
  }
  function openModule(key){module=key;page='module';pageNumber=0;searchText='';typeFilter='';trashMode=false;current=null;currentData={};render();loadItems();}
  function moduleFieldFilter(){const options=module==='contacts'?relationships:module==='accounts'?accountTypes:module==='journal'?['Personal','Reflection','Gratitude','Health','Work','Goals','Other']:['Date','Romantic encounter','Affectionate activity','Intimate encounter','Sexual activity','Other'];const filter=el('select');filter.setAttribute('aria-label',`Filter ${moduleMeta[module].title}`);const any=el('option','','All types');any.value='';filter.append(any);options.forEach(value=>{const o=el('option','',value);o.value=value;filter.append(o);});filter.value=typeFilter;filter.addEventListener('change',()=>{typeFilter=filter.value;pageNumber=0;loadItems();});return filter;}
  function renderModule(target){
    const meta=moduleMeta[module];const action=button(`＋ New ${meta.singular.toLowerCase()}`,'primary',newRecord);target.append(heading(meta.title,meta.description,action));
    if(module==='intimacy'){const notice=el('div','notice info','Private relationship journal for consensual adult events. Every question is optional; unknown or unanswered does not establish consent.');target.append(notice);}
    const layout=el('div','workspace-grid'),listPanel=el('section','panel');const toolbar=el('div','toolbar'),search=el('div','search-wrap');search.append(el('span','search-glyph','⌕'));const searchInput=el('input');searchInput.type='search';searchInput.placeholder=`Search ${meta.plural}…`;searchInput.value=searchText;searchInput.setAttribute('aria-label',`Search ${meta.plural}`);search.addEventListener('input',()=>{});searchInput.addEventListener('input',()=>{searchText=searchInput.value;pageNumber=0;clearTimeout(window.pvSearchTimer);window.pvSearchTimer=setTimeout(loadItems,250);});search.append(searchInput);toolbar.append(search);
    const filterRow=el('div','filter-row');filterRow.append(moduleFieldFilter());const sort=el('select');sort.setAttribute('aria-label','Sort records');[['updated','Recently updated'],['name','Name A–Z'],['date','Date newest']].forEach(([value,label])=>{const option=el('option','',label);option.value=value;sort.append(option);});sort.value=sortMode;sort.addEventListener('change',()=>{sortMode=sort.value;renderItems();});filterRow.append(sort);const trash=button(trashMode?'Back to active':'Trash','ghost small-button',()=>{trashMode=!trashMode;current=null;pageNumber=0;render();loadItems();});filterRow.append(trash);toolbar.append(filterRow);
    const filterDetails=el('div','filter-row');const from=el('input');from.type='date';from.value=dateFrom;from.setAttribute('aria-label','From date');from.addEventListener('change',()=>{dateFrom=from.value;pageNumber=0;loadItems();});const to=el('input');to.type='date';to.value=dateTo;to.setAttribute('aria-label','To date');to.addEventListener('change',()=>{dateTo=to.value;pageNumber=0;loadItems();});filterDetails.append(from,to);
    if(module==='intimacy'){const rating=el('select');rating.setAttribute('aria-label','Filter by rating');const anyRating=el('option','','Any rating');anyRating.value='';rating.append(anyRating);for(let n=1;n<=10;n++){const option=el('option','',String(n));option.value=String(n);rating.append(option);}rating.value=ratingFilter;rating.addEventListener('change',()=>{ratingFilter=rating.value;pageNumber=0;loadItems();});filterDetails.append(rating);}
    const tag=el('input');tag.type='search';tag.placeholder='Tag';tag.value=tagFilter;tag.setAttribute('aria-label','Filter by tag');tag.addEventListener('input',()=>{tagFilter=tag.value;pageNumber=0;clearTimeout(window.pvTagTimer);window.pvTagTimer=setTimeout(loadItems,250);});filterDetails.append(tag);toolbar.append(filterDetails);listPanel.append(toolbar);
    const recordList=el('div','record-list');recordList.id='record-list';listPanel.append(recordList);const pagination=el('div','pagination');pagination.id='pagination';listPanel.append(pagination);
    const editor=el('section','editor-card');editor.id='editor';if(current)renderEditor(editor);else{const empty=el('div','empty-state');empty.append(el('span','','✎'),el('strong','',`Select a ${meta.singular.toLowerCase()} or start a new one`),el('p','','Your changes save automatically as you type.'));editor.append(empty);}
    layout.append(listPanel,editor);target.append(layout);renderItems();
  }
  async function loadItems(){if(page!=='module')return;const offset=pageNumber*pageSize;try{const result=await request(trashMode?'trash':'list',{module,q:searchText,filter:typeFilter,from:dateFrom,to:dateTo,rating:ratingFilter,tag:tagFilter,offset,limit:pageSize});items=result.items||[];renderItems(result.total||0,result.truncated);}catch(error){toast(error.message);}}
  async function loadContacts(){try{const result=await request('list',{module:'contacts',limit:100,offset:0});contacts=result.items||[];}catch{contacts=[];}}
  function formatDate(value){if(!value)return'';const parts=String(value).slice(0,10).split('-');if(parts.length!==3)return String(value);const [year,month,day]=parts;const format=user?.date_format||'Y-m-d';return format==='m/d/Y'?`${month}/${day}/${year}`:format==='d/m/Y'?`${day}/${month}/${year}`:`${year}-${month}-${day}`;}
  function titleFor(item){const d=item.data||{};if(module==='contacts')return d.display_name||[d.first_name,d.middle_name,d.last_name].filter(Boolean).join(' ')||'Untitled contact';if(module==='accounts')return d.service_name||d.account_type||'Untitled account';if(module==='journal')return d.title||'Untitled entry';return `${d.event_type||'Relationship event'}${d.event_date?' · '+formatDate(d.event_date):''}`;}
  function subtitleFor(item){const d=item.data||{};if(module==='contacts'){const age=d.date_of_birth?calculateAge(d.date_of_birth):null;return[d.relationship,age===null?'':`Age ${age}`,d.email,d.phone].filter(Boolean).join(' · ')||'No contact details';}if(module==='accounts')return[d.account_type,d.username||d.login_email].filter(Boolean).join(' · ')||'Private account';if(module==='journal')return[d.category,d.entry_date?formatDate(d.entry_date):''].filter(Boolean).join(' · ')||'Personal entry';return[d.location,d.privacy].filter(Boolean).join(' · ')||'Private event';}
  function renderItems(total=items.length,truncated=false){const list=$('#record-list');if(!list)return;list.replaceChildren();if(!items.length){const e=el('div','empty-state');e.append(el('span','','⌕'),el('strong','',trashMode?'Trash is empty':'Nothing here yet'),el('p','',trashMode?'Deleted items can be restored from this list.':'Create your first record to get started.'));list.append(e);}else{const shown=[...items];if(sortMode==='name')shown.sort((a,b)=>titleFor(a).localeCompare(titleFor(b)));if(sortMode==='date')shown.sort((a,b)=>String(b.data.event_date||b.data.entry_date||b.data.date_of_birth||b.updated_at).localeCompare(String(a.data.event_date||a.data.entry_date||a.data.date_of_birth||a.updated_at)));shown.forEach(item=>{const card=el('article',`record-card ${current?.id===item.id?'active':''}`);card.tabIndex=0;card.setAttribute('role','button');card.setAttribute('aria-label',`Open ${titleFor(item)}`);card.append(el('strong','',titleFor(item)),el('small','',subtitleFor(item)));card.addEventListener('click',()=>selectItem(item));card.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();selectItem(item);}});list.append(card);});}
    const pager=$('#pagination');if(!pager)return;pager.replaceChildren();const start=total?Math.min(pageNumber*pageSize+1,total):0,end=Math.min((pageNumber+1)*pageSize,total);pager.append(el('span','',`${start}–${end} of ${total}${truncated?' · first 5,000 shown':''}`));const controls=el('span');const prev=button('‹','secondary small-button',()=>{if(pageNumber>0){pageNumber--;loadItems();}});prev.disabled=pageNumber===0;const next=button('›','secondary small-button',()=>{if((pageNumber+1)*pageSize<total){pageNumber++;loadItems();}});next.disabled=(pageNumber+1)*pageSize>=total;controls.append(prev,document.createTextNode(' '),next);pager.append(controls);
  }
  async function selectItem(item){if(module==='accounts')await loadContacts();current=item;currentData={...item.data};saveError='';render();}
  async function newRecord(){if(module==='accounts'||module==='journal'||module==='intimacy')await loadContacts();current={id:'',data:{}};currentData={};saveError='';render();const focus=$('#editor input:not([type=hidden]),#editor textarea,#editor select');focus?.focus();}
  function fieldControl(spec,value){const wrap=el('label',`field ${spec.full?'full':''}`);wrap.append(el('span','',spec.label+(spec.required?' *':'')));let input;
    if(spec.type==='textarea'||spec.type==='secretarea'){input=el('textarea');input.rows=spec.type==='secretarea'?3:4;input.value=spec.sensitive?'':(value??'');if(spec.type==='secretarea'&&spec.sensitive)input.placeholder='Enter a new value to replace the saved value';}
    else if(spec.type==='datalist'){input=el('input');input.type='text';const listId=`options-${spec.key}`;input.setAttribute('list',listId);const list=el('datalist');list.id=listId;for(const option of spec.options){const item=el('option');item.value=option;list.append(item);}wrap.append(list);input.value=value??'';}
    else if(spec.type==='select'||spec.type==='consent'||spec.type==='contact'){
      input=el('select');const opts=spec.type==='contact'?[['','No linked contact'],...contacts.map(c=>[c.id,c.data.display_name||[c.data.first_name,c.data.last_name].filter(Boolean).join(' ')||'Unnamed contact'])]:spec.type==='consent'?[['','Not recorded'],['yes','Yes'],['no','No'],['unknown','Unknown']]:[['','Select…'],...spec.options.map(v=>[v,v])];opts.forEach(([v,label])=>{const o=el('option','',label);o.value=v;input.append(o);});input.value=value??'';
    }else{input=el('input');input.type=spec.type==='secret'?'password':spec.type==='rating'?'number':spec.type||'text';input.value=spec.sensitive?'':(value??'');if(spec.type==='rating'){input.min='1';input.max='10';input.step='1';input.placeholder='1–10, optional';}if(spec.required)input.required=true;if(spec.key==='date_of_birth')input.max=new Date().toISOString().slice(0,10);if(spec.type==='secret')input.autocomplete='new-password';}
    input.dataset.key=spec.key;input.setAttribute('aria-label',spec.label);if(spec.required)input.required=true;
    if(spec.key==='tags'&&typeof value==='object'&&Array.isArray(value))input.value=value.join(', ');
    if(spec.sensitive){const box=el('div','secret-control');input.id=`field-${spec.key}`;box.append(input);const flags=currentData[`has_${spec.key}`]||currentData[`masked_${spec.key}`];if(flags){const reveal=button('Reveal','secondary small-button',()=>revealSecret(spec.key,reveal,input));reveal.setAttribute('aria-label',`Reveal ${spec.label}, requires your password`);box.append(reveal);const saved=el('span','field-hint',currentData[`masked_${spec.key}`]||'Saved encrypted');box.append(saved);}if(spec.type==='secret'&&(spec.key==='password'||spec.key==='secret'))box.append(button('Generate','ghost small-button',()=>{input.value=generatePassword(20);markDirty();}));wrap.append(box);
    }else wrap.append(input);
    if(spec.hint)wrap.append(el('small','field-hint',spec.hint));return {wrap,input};
  }
  function schemaVisible(){if(module!=='accounts')return schemas[module];const type=(currentData.account_type||'').toLowerCase();const banking=type.includes('bank')||type.includes('credit')||type.includes('investment');return schemas.accounts.filter(f=>!f.bank||banking);}
  function renderEditor(target){target.replaceChildren();const head=el('div','editor-top'),copy=el('div');const label=current.id?'Autosaving record':'New record';copy.append(el('h2','',label),el('p','',`${moduleMeta[module].singular} details · Changes save without reloading`));const actions=el('div','editor-actions');const saveStatus=el('span','autosave');saveStatus.id='save-status';saveStatus.setAttribute('role','status');saveStatus.textContent=current.id?'Saved':'Not saved yet';actions.append(saveStatus);if(current.id&&!trashMode)actions.append(button('Delete','danger small-button',deleteCurrent));if(current.id&&trashMode)actions.append(button('Restore','secondary small-button',restoreCurrent));head.append(copy,actions);target.append(head);
    if(module==='accounts'){const typeWrap=el('p','notice info','Passwords, recovery data, and bank identifiers remain encrypted. Reveal is recorded only in this active screen and requires your application password.');target.append(typeWrap);}
    if(module==='contacts'&&current.id){const dob=currentData.date_of_birth;const age=dob?calculateAge(dob):null;if(age!==null){const ageBadge=el('span','chip',`Age ${age}`);const ageLine=el('p');ageLine.append(ageBadge);target.append(ageLine);}}
    if(module==='journal'){const tools=el('div','editor-actions markdown-tools');const body=()=>$('#editor [data-key="body"]');for(const [label,marker] of [['Bold','**'],['Italic','*'],['Heading','# '],['Code','`']])tools.append(button(label,'secondary small-button',()=>{const input=body();if(!input)return;const start=input.selectionStart,end=input.selectionEnd,selected=input.value.slice(start,end)||'text';const insert=marker==='# '?`${marker}${selected}`:`${marker}${selected}${marker}`;input.setRangeText(insert,start,end,'select');input.dispatchEvent(new Event('input',{bubbles:true}));input.focus();}));target.append(tools,el('p','field-hint','Formatting shortcuts insert plain-text Markdown markers; journal content is never interpreted as HTML.'));}
    const grid=el('div','field-grid');for(const spec of schemaVisible()){const control=fieldControl(spec,currentData[spec.key]);if((spec.type==='select'||spec.type==='datalist')&&spec.key==='account_type'){control.input.addEventListener('change',()=>{const snapshot=collectForm();snapshot.account_type=control.input.value;currentData=snapshot;render();markDirty();});}control.input.addEventListener('input',markDirty);control.input.addEventListener('change',markDirty);grid.append(control.wrap);}target.append(grid);
    const foot=el('p','footer-note','Created and updated times are maintained by the application. Confidential fields are encrypted at rest with AES-256-GCM.');target.append(foot);
  }
  function calculateAge(date){const birth=new Date(`${date}T00:00:00`);if(Number.isNaN(birth.valueOf()))return null;const now=new Date();let age=now.getFullYear()-birth.getFullYear();if(now.getMonth()<birth.getMonth()||(now.getMonth()===birth.getMonth()&&now.getDate()<birth.getDate()))age--;return age>=0?age:null;}
  function collectForm(){const data={...currentData};$('#editor')?.querySelectorAll('[data-key]').forEach(input=>{let value=input.value;if(input.dataset.key==='tags')value=value.split(',').map(t=>t.trim()).filter(Boolean);if(!input.closest('.secret-control')||value!=='')data[input.dataset.key]=value;});return data;}
  function markDirty(){if(!current)return;const status=$('#save-status');if(status){status.className='autosave saving';status.textContent='Saving…';}clearTimeout(dirtyTimer);dirtyTimer=setTimeout(saveRecord,500);}
  async function saveRecord(){if(!current||page!=='module'||trashMode)return;if(saving){pendingSave=true;return;}const values=collectForm();if(!Object.values(values).some(value=>Array.isArray(value)?value.length>0:String(value??'').trim()!==''))return;saving=true;pendingSave=false;const status=$('#save-status');if(status){status.className='autosave saving';status.textContent='Saving…';}
    const persisted={...values};for(const key of Object.keys(persisted))if(key.startsWith('has_')||key.startsWith('masked_'))delete persisted[key];
    try{const result=await request('save',{module,id:current.id||undefined,data:persisted});current.id=result.id;current.updated_at=result.updated_at;const idx=items.findIndex(x=>x.id===current.id);const safeValues={...values};if(module==='accounts'){for(const key of ['password','secret','routing_number','account_number','security_answers','recovery_codes']){if(Object.prototype.hasOwnProperty.call(safeValues,key)){const value=safeValues[key];safeValues[`has_${key}`]=value!==''&&value!==null; if(['routing_number','account_number'].includes(key)&&value){const digits=String(value).replace(/\D/g,'');safeValues[`masked_${key}`]=digits?`•••• ${digits.slice(-4)}`:'Saved';}delete safeValues[key];}}}current.data=safeValues;currentData=safeValues;if(idx>=0)items[idx]={...items[idx],data:safeValues,updated_at:result.updated_at};else items.unshift({id:current.id,data:safeValues,updated_at:result.updated_at,created_at:result.updated_at});counts[module]=(counts[module]||0)+(idx<0?1:0);if($('#editor'))renderEditor($('#editor'));const updatedStatus=$('#save-status');if(updatedStatus){updatedStatus.className='autosave saved';updatedStatus.textContent='Saved';}renderItems();}
    catch(error){saveError=error.message;if(status){status.className='autosave error';status.textContent='Save failed';status.title=error.message;}toast(error.message);}
    finally{saving=false;if(pendingSave){pendingSave=false;saveRecord();}}
  }
  async function deleteCurrent(){if(!current?.id)return;if(!confirm('Move this record to Trash? You can restore it later.'))return;const id=current.id;try{await request('delete',{module,id});current=null;currentData={};items=items.filter(item=>item.id!==id);await loadItems();await updateCounts();render();toast('Moved to Trash.');}catch(error){toast(error.message);}}
  async function restoreCurrent(){if(!current?.id)return;const id=current.id;try{await request('restore',{module,id});current=null;currentData={};await loadItems();await updateCounts();render();toast('Record restored.');}catch(error){toast(error.message);}}
  async function updateCounts(){try{const boot=await request('bootstrap');counts=boot.counts||counts;}catch{}}
  async function revealSecret(field,toggle,input){if(toggle.dataset.revealed==='true'){input.value='';input.type='password';toggle.dataset.revealed='false';toggle.textContent='Reveal';return;}const password=prompt('Re-enter your PersonVault password to reveal this value.');if(password===null)return;try{const result=await request('reveal',{id:current.id,field,password});input.value=result.value||'';input.type='text';toggle.dataset.revealed='true';toggle.textContent='Hide';if(navigator.clipboard&&result.value){const copy=button('Copy','ghost small-button',async()=>{try{await navigator.clipboard.writeText(input.value);toast('Copied. Clear your clipboard when finished.');}catch{toast('Clipboard access was unavailable.');}});input.parentElement.append(copy);setTimeout(()=>{if(toggle.dataset.revealed==='true'){input.value='';input.type='password';toggle.dataset.revealed='false';toggle.textContent='Reveal';copy.remove();}},30000);}}catch(error){toast(error.message);}}
  function generatePassword(length){const chars='ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*_-+=';const random=new Uint32Array(length);crypto.getRandomValues(random);return [...random].map(n=>chars[n%chars.length]).join('');}
  function renderSettings(target){
    target.append(heading('Settings & backup','Theme, secure recovery, and your private data.'));
    const themePanel=el('section','panel settings-panel');themePanel.append(el('h2','','Appearance'),el('p','muted','Choose how PersonVault looks on this device. Your preference is stored with your account.'));const row=el('div','setting-row');const description=el('div');description.append(el('strong','','Theme'),el('p','','Light uses white and blue. Dark uses navy and crimson.'));const select=el('select');select.setAttribute('aria-label','Theme preference');[['system','System'],['light','Light'],['dark','Dark']].forEach(([value,label])=>{const o=el('option','',label);o.value=value;select.append(o);});select.value=user.theme||'system';select.addEventListener('change',async()=>{try{await request('preferences',{theme:select.value,date_format:user.date_format||'Y-m-d'});user.theme=select.value;applyTheme(select.value);toast('Theme saved.');}catch(error){toast(error.message);}});row.append(description,select);themePanel.append(row);
    const dateRow=el('div','setting-row');const dateText=el('div');dateText.append(el('strong','','Date format'),el('p','','Choose how dates appear in record lists.'));const dateSelect=el('select');dateSelect.setAttribute('aria-label','Date format');[['Y-m-d','Year-month-day'],['m/d/Y','Month/day/year'],['d/m/Y','Day/month/year']].forEach(([value,label])=>{const o=el('option','',label);o.value=value;dateSelect.append(o);});dateSelect.value=user.date_format||'Y-m-d';dateSelect.addEventListener('change',async()=>{try{await request('preferences',{theme:user.theme||'system',date_format:dateSelect.value});user.date_format=dateSelect.value;toast('Date format saved.');if(page==='module')renderItems();}catch(error){toast(error.message);}});dateRow.append(dateText,dateSelect);themePanel.append(dateRow);target.append(themePanel);
    const passwordPanel=el('section','panel settings-panel');passwordPanel.append(el('h2','','Change your sign-in password'),el('p','muted','This password protects your application account. Managed service passwords are stored separately in Accounts & passwords.'));const passwordForm=el('form','field-grid');const currentPassword=fieldControl({key:'current_password',label:'Current password',type:'password'});currentPassword.input.required=true;currentPassword.input.autocomplete='current-password';const newPassword=fieldControl({key:'new_password',label:'New password',type:'password'});newPassword.input.required=true;newPassword.input.minLength=12;newPassword.input.autocomplete='new-password';const confirmPassword=fieldControl({key:'confirm_password',label:'Confirm new password',type:'password'});confirmPassword.input.required=true;confirmPassword.input.minLength=12;confirmPassword.input.autocomplete='new-password';passwordForm.append(currentPassword.wrap,newPassword.wrap,confirmPassword.wrap);const changeButton=button('Update password','primary',()=>{});changeButton.type='submit';passwordForm.append(changeButton);passwordForm.addEventListener('submit',async event=>{event.preventDefault();changeButton.disabled=true;try{await request('change_password',{current_password:currentPassword.input.value,new_password:newPassword.input.value,confirm_password:confirmPassword.input.value});passwordForm.reset();toast('Sign-in password updated.');}catch(error){toast(error.message);}finally{changeButton.disabled=false;}});passwordPanel.append(passwordForm);target.append(passwordPanel);
    const backup=el('section','panel settings-panel');backup.append(el('h2','','Backup and recovery'),el('p','muted','Download your own encrypted records as JSON, or merge a backup into this account. The export contains ciphertext, never the encryption key.'));const actions=el('div','editor-actions');actions.append(button('Download encrypted backup','primary',downloadBackup));const file=el('input');file.type='file';file.accept='.json,application/json';file.className='hidden';const importButton=button('Restore from file','secondary',()=>file.click());file.addEventListener('change',()=>restoreBackup(file));actions.append(importButton,file);backup.append(actions,el('div','notice warning','A data backup alone cannot decrypt your records. Back up the separate encryption key securely. SQLite backups are application-level exports; to back up the SQLite file, stop writes and copy the private database file.'));target.append(backup);
    const status=el('section','panel settings-panel');status.append(el('h2','','Installation status'));const statusRow=el('div','setting-row');const left=el('div');left.append(el('strong','','Protected installation'),el('p','','Installer access is disabled by a lock outside the public web folder.'));statusRow.append(left,el('span','chip','LOCKED'));status.append(statusRow,el('p','footer-note','PHP and extension checks run in the installer. Sensitive fields use authenticated encryption. This application does not encrypt the SQLite file itself.'));target.append(status);
  }
  async function downloadBackup(){try{const data=await request('backup',{});const blob=new Blob([JSON.stringify(data,null,2)],{type:'application/json'});const link=el('a');link.href=URL.createObjectURL(blob);link.download=`personvault-backup-${new Date().toISOString().slice(0,10)}.json`;link.click();setTimeout(()=>URL.revokeObjectURL(link.href),1000);toast('Encrypted backup downloaded.');}catch(error){toast(error.message);}}
  async function restoreBackup(input){if(!input.files?.length)return;try{const file=input.files[0];if(file.size>25*1024*1024)throw new Error('Backup file must be 25 MB or smaller.');const backup=JSON.parse(await file.text());if(!confirm('Merge records from this backup into your account? Existing record IDs will be skipped.'))return;const result=await request('restore_backup',{backup});toast(`${result.restored} records restored.`);await updateCounts();if(page==='module')loadItems();}catch(error){toast(error.message);}finally{input.value='';}}
  async function renderUsers(target){target.append(heading('User management','Create sign-in accounts. Administrators cannot open another user’s private records.'));const panel=el('section','panel settings-panel');panel.append(el('h2','','Add a user'));const form=el('form','field-grid');const email=fieldControl({key:'email',label:'Email',type:'email'});email.input.required=true;const password=fieldControl({key:'password',label:'Temporary password',type:'password'});password.input.required=true;password.input.minLength=12;const role=fieldControl({key:'role',label:'Role',type:'select',options:['user','admin']},'user');form.append(email.wrap,password.wrap,role.wrap);const submit=button('Create account','primary',()=>{});submit.type='submit';form.append(submit);form.addEventListener('submit',async event=>{event.preventDefault();try{await request('create_user',{email:email.input.value,password:password.input.value,role:role.input.value});form.reset();role.input.value='user';toast('User account created.');loadUserList(tableBody);}catch(error){toast(error.message);}});panel.append(form);target.append(panel);
    const listPanel=el('section','panel settings-panel');listPanel.append(el('h2','','Application users'));const table=el('table','user-table');const thead=el('thead'),tr=el('tr');['Email','Role','Status','Created','Actions'].forEach(text=>tr.append(el('th','',text)));thead.append(tr);table.append(thead);const tbody=el('tbody');table.append(tbody);listPanel.append(table);target.append(listPanel);await loadUserList(tbody);
  }
  async function loadUserList(target){try{const result=await request('users',{});target.replaceChildren();for(const u of result.users){const tr=el('tr');tr.append(el('td','',u.email),el('td','',u.role),el('td','',Number(u.disabled)?'Disabled':'Active'),el('td','',String(u.created_at||'').slice(0,10)));const actions=el('td');if(Number(u.id)!==Number(user.id)){const toggle=button(Number(u.disabled)?'Enable':'Disable',Number(u.disabled)?'secondary small-button':'danger small-button',async()=>{if(!Number(u.disabled)&&!confirm(`Disable sign in for ${u.email}? Their private records will remain stored.`))return;try{await request('set_user_status',{user_id:Number(u.id),disabled:Number(u.disabled)?0:1});await loadUserList(target);toast('User status updated.');}catch(error){toast(error.message);}});actions.append(toggle);}tr.append(actions);target.append(tr);}}catch(error){toast(error.message);}}
  async function boot(){try{const result=await request('bootstrap');csrf=result.csrf||csrf;user=result.user;counts=result.counts||{};if(user)applyTheme(user.theme||'system');render();}catch(error){app.textContent=error.message;}}
  boot();
})();
