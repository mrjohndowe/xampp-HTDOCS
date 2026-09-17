document.addEventListener('DOMContentLoaded', () => {
  const splash = document.querySelector('.splash-screen');
  if (splash) setTimeout(() => splash.classList.add('hide'), 1100);
});
