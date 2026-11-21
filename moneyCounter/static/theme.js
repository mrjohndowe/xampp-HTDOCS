try {
  const saved = localStorage.getItem("moneycounter-theme");
  if (saved === "light" || saved === "dark") document.documentElement.dataset.theme = saved;
} catch {
  // The browser can block storage in embedded contexts.
}
