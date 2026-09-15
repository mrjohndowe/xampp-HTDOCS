(() => {
 const splash=document.getElementById('siteSplash'); if(!splash)return;
 const params=new URLSearchParams(location.search);
 const splashKey='novelFlipbookSplashShown';
 try {
   if(params.has('nosplash') || sessionStorage.getItem(splashKey)==='1'){splash.remove();return;}
   sessionStorage.setItem(splashKey,'1');
 } catch (_) {
   if(params.has('nosplash')){splash.remove();return;}
 }
 const bar=document.getElementById('loadingBar'), pct=document.getElementById('loadingPercent'), stage=document.getElementById('loadingStage'), track=splash.querySelector('.loading-track');
 const message=document.getElementById('splashMessage');
 const duration=(Math.floor(Math.random()*16)+15)*1000; const started=performance.now(); let loaded=document.readyState==='complete';
 const stages=[
   {name:'Checking the collection', min:0, message:'Preparing your library…'},
   {name:'Placing the first books', min:18, message:'Sliding fresh stories onto the shelves…'},
   {name:'Stacking the shelves', min:40, message:'Building your shelf one book at a time…'},
   {name:'Preparing the page turner', min:68, message:'Getting the reader ready for the next chapter…'},
   {name:'Opening the library', min:90, message:'Almost ready. Opening a world of stories…'}
 ];
 const tick=(now)=>{ const elapsed=now-started; let p=Math.min(100,(elapsed/duration)*100); if(!loaded)p=Math.min(p,96); const rounded=Math.floor(p); bar.style.width=rounded+'%'; pct.textContent=rounded+'%'; track.setAttribute('aria-valuenow',String(rounded)); let current=stages[0]; for(const s of stages){ if(rounded>=s.min) current=s; } stage.textContent=current.name; if(message) message.textContent=current.message; if(rounded>=100&&loaded){ splash.classList.add('hide'); setTimeout(()=>splash.remove(),800); return; } requestAnimationFrame(tick); };
 window.addEventListener('load',()=>{loaded=true;},{once:true}); requestAnimationFrame(tick);
})();
