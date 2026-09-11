(() => {
  "use strict";
  const storageKey = "offline-video-wall-last-category";
  const select = document.querySelector("#categoryFilter"),
    grid = document.querySelector("#videoGrid"),
    search = document.querySelector("#search"),
    count = document.querySelector("#resultCount");
  if (!select || !grid) return;
  try {
    const savedCategory = localStorage.getItem(storageKey);
    const categoryStillExists =
      savedCategory &&
      Array.from(select.options).some(
        (option) => option.value === savedCategory,
      );
    if (categoryStillExists) select.value = savedCategory;
    else if (savedCategory) localStorage.removeItem(storageKey);
  } catch (_) {
    // The wall still works when browser storage is disabled.
  }
  function apply() {
    const selected = select.value;
    let visible = 0;
    grid.querySelectorAll(".video-card").forEach((card) => {
      const video = (window.VIDEO_LIBRARY || [])[Number(card.dataset.index)];
      const categories = video?.categories || [];
      const show =
        !selected ||
        (selected === "Uncategorized"
          ? !categories.length
          : categories.includes(selected));
      card.style.display = show ? "" : "none";
      if (show) visible++;
    });
    count.textContent = `${visible} video${visible === 1 ? "" : "s"}`;
    document.querySelector("#emptyState").style.display = visible
      ? "none"
      : "block";
  }
  select.addEventListener("change", () => {
    try {
      select.value
        ? localStorage.setItem(storageKey, select.value)
        : localStorage.removeItem(storageKey);
    } catch (_) {}
    apply();
  });
  search?.addEventListener("input", () => setTimeout(apply));
  apply();
})();
