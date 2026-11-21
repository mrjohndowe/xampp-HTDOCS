document.querySelector("[data-theme-toggle]")?.addEventListener("click", () => {
  const current = document.documentElement.dataset.theme ||
    (matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");
  const next = current === "dark" ? "light" : "dark";
  document.documentElement.dataset.theme = next;
  try { localStorage.setItem("moneycounter-theme", next); } catch { /* Apply for this page. */ }
});
document.querySelector("[data-print]")?.addEventListener("click", () => window.print());
