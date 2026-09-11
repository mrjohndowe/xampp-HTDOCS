(() => { "use strict"; const splash = document.querySelector("#appSplash"); if (!splash) return; requestAnimationFrame(() => requestAnimationFrame(() => splash.classList.add("is-ready"))); })();
