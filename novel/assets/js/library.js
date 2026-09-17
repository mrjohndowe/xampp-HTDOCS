import * as pdfjsLib from "../vendor/pdf.min.mjs";
pdfjsLib.GlobalWorkerOptions.workerSrc = new URL("../vendor/pdf.worker.min.mjs", import.meta.url).href;

const BASE=window.NOVEL_APP_BASE||'';
const grid=document.getElementById('bookGrid');
const refreshBtn=document.getElementById('refreshBtn');
const readerModal=document.getElementById('readerModal');
const readerTitle=document.getElementById('readerTitle');
const pageIndicator=document.getElementById('pageIndicator');
const scrubber=document.getElementById('pageScrubber');
const closeBtn=document.getElementById('closeReaderBtn');
const prevBtn=document.getElementById('prevBtn');
const nextBtn=document.getElementById('nextBtn');
const zoomIn=document.getElementById('zoomInBtn');
const zoomOut=document.getElementById('zoomOutBtn');
const reader=document.getElementById('bookReader');
const leftCanvas=document.getElementById('leftCanvas');
const rightCanvas=document.getElementById('rightCanvas');
const leftBlank=document.getElementById('leftBlank');
const rightBlank=document.getElementById('rightBlank');
const leftPage=document.getElementById('leftPage');
const rightPage=document.getElementById('rightPage');
const flip=document.getElementById('flipSheet');
const flipFront=document.getElementById('flipFrontCanvas');
const flipBack=document.getElementById('flipBackCanvas');

const previewModal=document.getElementById('bookPreviewModal');
const closePreviewBtn=document.getElementById('closePreviewBtn');
const flipPreviewBtn=document.getElementById('flipPreviewBtn');
const openPreviewBookBtn=document.getElementById('openPreviewBookBtn');
const previewBook3d=document.getElementById('previewBook3d');
const previewInner=document.getElementById('previewBookInner');
const previewCoverImage=document.getElementById('previewCoverImage');
const previewCoverFallback=document.getElementById('previewCoverFallback');
const previewTitle=document.getElementById('previewTitle');
const previewAuthor=document.getElementById('previewAuthor');
const previewBadge=document.getElementById('previewBadge');
const previewBackTitle=document.getElementById('previewBackTitle');
const previewBackAuthor=document.getElementById('previewBackAuthor');
const previewDescription=document.getElementById('previewDescription');
const previewStars=document.getElementById('previewStars');
const previewRatingValue=document.getElementById('previewRatingValue');
const previewReviewCount=document.getElementById('previewReviewCount');
const previewDetailsLink=document.getElementById('previewDetailsLink');
const previewMetaTitle=document.getElementById('previewMetaTitle');
const previewMetaAuthor=document.getElementById('previewMetaAuthor');
const previewMetaStars=document.getElementById('previewMetaStars');
const previewMetaRating=document.getElementById('previewMetaRating');
const previewMetaCount=document.getElementById('previewMetaCount');
const previewReviewsBtn=document.getElementById('previewReviewsBtn');

let books=[]; let pdf=null; let zoom=1; let anchor=1; let busy=false; let currentBook=null; let previewBook=null;
const mobile=()=>window.matchMedia('(max-width: 850px)').matches;

refreshBtn?.addEventListener('click',loadBooks);
closeBtn?.addEventListener('click',closeReader);
prevBtn?.addEventListener('click',()=>turn(-1));
nextBtn?.addEventListener('click',()=>turn(1));
zoomIn?.addEventListener('click',async()=>{zoom=Math.min(1.55,zoom+.1);await renderSpread();});
zoomOut?.addEventListener('click',async()=>{zoom=Math.max(.72,zoom-.1);await renderSpread();});
scrubber?.addEventListener('change',async e=>{if(!pdf)return;const p=Number(e.target.value);anchor=mobile()?p:(p<=1?1:(p%2===0?p:p-1));await renderSpread();});
rightPage?.addEventListener('click',e=>{if(e.offsetX>rightPage.clientWidth*.72)turn(1);});
leftPage?.addEventListener('click',e=>{if(e.offsetX<leftPage.clientWidth*.28)turn(-1);});
window.addEventListener('resize',debounce(async()=>{if(pdf)await renderSpread();},180));
closePreviewBtn?.addEventListener('click',closePreview);
previewModal?.querySelector('[data-close-preview]')?.addEventListener('click',closePreview);
flipPreviewBtn?.addEventListener('click',togglePreviewFlip);
openPreviewBookBtn?.addEventListener('click',()=>{ if(previewBook){ const b=previewBook; closePreview(); openReader(b); } });

document.addEventListener('keydown',e=>{
  if(!readerModal?.classList.contains('hidden')){
    if(e.key==='Escape')closeReader();
    if(e.key==='ArrowRight')turn(1);
    if(e.key==='ArrowLeft')turn(-1);
    return;
  }
  if(!previewModal?.classList.contains('hidden')){
    if(e.key==='Escape')closePreview();
    if(e.key.toLowerCase()==='f')togglePreviewFlip();
  }
});

await loadBooks();
const params=new URLSearchParams(location.search); if(params.get('read')){const b=books.find(x=>String(x.id)===params.get('read'));if(b)openReader(b);}

async function loadBooks(){
  grid.innerHTML='<div class="empty-state">Loading bookshelf…</div>';
  try{
    const r=await fetch(`${BASE}/api/books.php`,{cache:'no-store'});
    books=await r.json();
    if(!Array.isArray(books)||!books.length){grid.innerHTML='<div class="empty-state">No books yet. Use the Upload page to add one.</div>';return;}
    grid.innerHTML=books.map(card).join('');
    grid.querySelectorAll('[data-preview]').forEach(btn=>btn.addEventListener('click',()=>{const b=books.find(x=>String(x.id)===btn.dataset.preview);if(b)openPreview(b);}));
  }catch(e){console.error(e);grid.innerHTML='<div class="empty-state">Could not load bookshelf.</div>';}
}

function card(b){
  const accent=spineStyle(b.id);
  return `<article class="book-card" data-book="${esc(b.id)}"><button class="book-spine-button ${accent}" data-preview="${esc(b.id)}" aria-label="Pull out ${esc(b.title)} from the shelf"><span class="book-spine-ridge"></span><span class="book-spine-title">${esc(b.title)}</span><span class="book-spine-author">${esc(b.author||'Unknown author')}</span></button></article>`;
}

function openPreview(book){
  previewBook=book;
  const rating=Number(book.averageRating||0);
  const reviewCount=Number(book.reviewCount||0);
  previewTitle.textContent=book.title;
  previewAuthor.textContent=book.author||'Unknown author';
  previewBackTitle.textContent=book.title;
  previewBackAuthor.textContent=`by ${book.author||'Unknown author'}`;
  previewDescription.textContent=book.description||'No description yet.';
  previewMetaTitle.textContent=book.title;
  previewMetaAuthor.textContent=book.author||'Unknown author';
  previewBadge.textContent=`★ ${rating.toFixed(1)}`;
  previewStars.textContent=stars(rating);
  previewRatingValue.textContent=rating.toFixed(1);
  previewReviewCount.textContent=`${reviewCount} review${reviewCount===1?'':'s'}`;
  previewMetaStars.textContent=stars(rating);
  previewMetaRating.textContent=rating.toFixed(1);
  previewMetaCount.textContent=`${reviewCount} review${reviewCount===1?'':'s'}`;
  const detailHref=`${BASE}/book.php?id=${encodeURIComponent(book.id)}`;
  previewDetailsLink.href=detailHref;
  previewReviewsBtn.href=detailHref;
  if(book.coverUrl){
    previewCoverImage.src=book.coverUrl;
    previewCoverImage.alt=`${book.title} cover`;
    previewCoverImage.classList.remove('hidden');
    previewCoverFallback.classList.add('hidden');
    previewCoverFallback.textContent='';
  }else{
    previewCoverImage.removeAttribute('src');
    previewCoverImage.classList.add('hidden');
    previewCoverFallback.classList.remove('hidden');
    previewCoverFallback.textContent=book.title;
  }
  previewBook3d.classList.remove('show-back');
  flipPreviewBtn.textContent='Show Back';
  previewModal.classList.remove('hidden');
  previewModal.setAttribute('aria-hidden','false');
  document.body.classList.add('preview-open');
  requestAnimationFrame(()=>previewBook3d.classList.add('entered'));
}

function closePreview(){
  if(previewModal.classList.contains('hidden')) return;
  previewBook3d.classList.remove('entered','show-back');
  previewModal.classList.add('closing');
  setTimeout(()=>{
    previewModal.classList.add('hidden');
    previewModal.classList.remove('closing');
    previewModal.setAttribute('aria-hidden','true');
    document.body.classList.remove('preview-open');
    flipPreviewBtn.textContent='Show Back';
    previewBook=null;
  },260);
}

function togglePreviewFlip(){
  if(!previewBook) return;
  const showingBack=previewBook3d.classList.toggle('show-back');
  flipPreviewBtn.textContent=showingBack?'Show Front':'Show Back';
}

async function openReader(book){
  if(busy)return;
  busy=true;
  currentBook=book;
  readerTitle.textContent=book.title;
  pageIndicator.textContent='Loading PDF…';
  readerModal.classList.remove('hidden');
  readerModal.setAttribute('aria-hidden','false');
  document.body.classList.add('reader-open');
  reader.classList.remove('book-open'); void reader.offsetWidth; reader.classList.add('book-open');
  try{pdf=await pdfjsLib.getDocument(book.fileUrl).promise;anchor=1;zoom=1;scrubber.min=1;scrubber.max=pdf.numPages;scrubber.value=1;await renderSpread();}
  catch(e){console.error(e);pageIndicator.textContent='Failed to load PDF';alert('Could not load PDF: '+e.message);}
  finally{busy=false;}
}

function closeReader(){
  readerModal.classList.add('closing');
  setTimeout(()=>{readerModal.classList.add('hidden');readerModal.classList.remove('closing');readerModal.setAttribute('aria-hidden','true');document.body.classList.remove('reader-open');pdf=null;anchor=1;currentBook=null;clearCanvas(leftCanvas);clearCanvas(rightCanvas);},320);
}

function spreadPages(){if(mobile())return {left:0,right:anchor};if(anchor<=1)return {left:0,right:1};return {left:anchor,right:anchor+1<=pdf.numPages?anchor+1:0};}
async function renderSpread(){if(!pdf)return;const {left,right}=spreadPages();reader.classList.toggle('single-mode',mobile());await Promise.all([renderBase(left,leftCanvas,leftBlank),renderBase(right,rightCanvas,rightBlank)]);const shown=mobile()?right:(left||right);scrubber.value=Math.max(1,shown);pageIndicator.textContent=mobile()?`Page ${right} / ${pdf.numPages}`:(left?`Pages ${left}${right?`–${right}`:''} / ${pdf.numPages}`:`Page ${right} / ${pdf.numPages}`);prevBtn.disabled=anchor<=1;nextBtn.disabled=mobile()?anchor>=pdf.numPages:(anchor===1?pdf.numPages<=1:anchor+1>=pdf.numPages);}
async function turn(dir){if(!pdf||busy)return;let target;if(mobile())target=anchor+dir;else if(dir>0)target=anchor===1?2:anchor+2;else target=anchor<=2?1:anchor-2;target=Math.max(1,Math.min(target,pdf.numPages));if(!mobile()&&target>1&&target%2===1)target-=1;if(target===anchor)return;busy=true;try{await animateTurn(dir,target);anchor=target;await renderSpread();}finally{busy=false;}}
async function animateTurn(dir,target){flip.className='flip-sheet';const current=spreadPages();let frontPage,backPage;if(mobile()){frontPage=anchor;backPage=target;}else if(dir>0){frontPage=current.right||current.left;backPage=target;}else{frontPage=current.left||current.right;backPage=target===1?1:target+1;}await Promise.all([renderToCanvas(frontPage,flipFront),renderToCanvas(backPage,flipBack)]);flip.classList.add(dir>0?'flip-forward':'flip-backward','active');await waitAnimation(flip,650);flip.className='flip-sheet';}
async function renderBase(pageNum,canvas,blank){if(!pageNum){canvas.style.display='none';blank.style.display='grid';blank.textContent='';return;}blank.style.display='none';canvas.style.display='block';await renderToCanvas(pageNum,canvas);}
async function renderToCanvas(pageNum,canvas){if(!pdf||!pageNum){clearCanvas(canvas);return;}const page=await pdf.getPage(pageNum);const vp1=page.getViewport({scale:1});const stage=document.getElementById('readerStage');const one=mobile();const availableW=Math.max(260,(stage.clientWidth-(one?48:110))/(one?1:2));const availableH=Math.max(340,stage.clientHeight-80);const fit=Math.min(availableW/vp1.width,availableH/vp1.height,1.8)*zoom;const vp=page.getViewport({scale:fit});const dpr=Math.min(window.devicePixelRatio||1,2);canvas.width=Math.floor(vp.width*dpr);canvas.height=Math.floor(vp.height*dpr);canvas.style.width=Math.floor(vp.width)+'px';canvas.style.height=Math.floor(vp.height)+'px';const ctx=canvas.getContext('2d');ctx.setTransform(dpr,0,0,dpr,0,0);ctx.fillStyle='#fbf7ed';ctx.fillRect(0,0,vp.width,vp.height);await page.render({canvasContext:ctx,viewport:vp}).promise;}
function stars(n){const x=Math.round(Number(n)||0);return '★'.repeat(x)+'☆'.repeat(5-x);} 
function clearCanvas(c){const x=c.getContext('2d');x.clearRect(0,0,c.width,c.height);} 
function esc(v){return String(v??'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');}
function spineStyle(seed){const classes=['spine-tone-a','spine-tone-b','spine-tone-c','spine-tone-d','spine-tone-e','spine-tone-f']; return classes[Math.abs(Number(seed)||0)%classes.length];}
function waitAnimation(el,timeout){return new Promise(resolve=>{let done=false;const finish=()=>{if(done)return;done=true;el.removeEventListener('animationend',finish);resolve();};el.addEventListener('animationend',finish,{once:true});setTimeout(finish,timeout);});}
function debounce(fn,ms){let t;return(...a)=>{clearTimeout(t);t=setTimeout(()=>fn(...a),ms);};}
