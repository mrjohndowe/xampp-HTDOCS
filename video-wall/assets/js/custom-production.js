(() => {
  "use strict";
  document
    .querySelector("#addProduction")
    ?.addEventListener("click", async () => {
      const input = document.querySelector("#newProductionName");
      const name = input.value.trim();
      if (!name) return;
      const response = await fetch("production.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "create", name }),
      });
      const result = await response.json();
      if (!response.ok)
        return alert(result.error || "Unable to create production.");
      location.reload();
    });
  document
    .querySelector("#newProductionName")
    ?.addEventListener("keydown", (event) => {
      if (event.key === "Enter") {
        event.preventDefault();
        document.querySelector("#addProduction").click();
      }
    });
  document.querySelectorAll(".delete-production").forEach((button) =>
    button.addEventListener("click", async () => {
      const row = button.closest(".production-row");
      if (
        !confirm(
          `Delete the production “${row.querySelector("span").textContent}”? It will be removed from assigned videos; their other Production/Studios will stay.`,
        )
      )
        return;
      const response = await fetch("production.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "delete", id: Number(row.dataset.id) }),
      });
      const result = await response.json();
      if (!response.ok)
        return alert(result.error || "Unable to delete production/studio.");
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
