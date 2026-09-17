(() => {
  "use strict";
  document
    .querySelector("#addCategory")
    ?.addEventListener("click", async () => {
      const input = document.querySelector("#newCategoryName");
      const name = input.value.trim();
      if (!name) return;
      const response = await fetch("category.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "create", name }),
      });
      const result = await response.json();
      if (!response.ok)
        return alert(result.error || "Unable to create category.");
      location.reload();
    });
  document
    .querySelector("#newCategoryName")
    ?.addEventListener("keydown", (event) => {
      if (event.key === "Enter") {
        event.preventDefault();
        document.querySelector("#addCategory").click();
      }
    });
  document.querySelectorAll(".delete-category").forEach((button) =>
    button.addEventListener("click", async () => {
      const row = button.closest(".category-row");
      if (
        !confirm(
          `Delete the category “${row.querySelector("span").textContent}”? It will be removed from assigned videos; their other categories will stay.`,
        )
      )
        return;
      const response = await fetch("category.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "delete", id: Number(row.dataset.id) }),
      });
      const result = await response.json();
      if (!response.ok)
        return alert(result.error || "Unable to delete category.");
      location.reload();
    }),
  );
  document.querySelectorAll(".restore-video").forEach((button) =>
    button.addEventListener("click", async () => {
      const row = button.closest(".removed-row");
      const response = await fetch("video-status.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: row.dataset.id, active: true }),
      });
      const result = await response.json();
      if (!response.ok)
        return alert(result.error || "Unable to restore the video.");
      location.reload();
    }),
  );
})();
