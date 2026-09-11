(() => {
  "use strict";
  async function post(url, data) {
    const response = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || "The operation failed.");
    return result;
  }
  document
    .querySelector("#addCategory")
    ?.addEventListener("click", async () => {
      const input = document.querySelector("#newCategoryName"),
        name = input.value.trim();
      if (!name) return;
      try {
        await post("category.php", { action: "create", name });
        location.reload();
      } catch (error) {
        alert(error.message);
      }
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
          `Delete “${row.querySelector("span").textContent}”? It will be removed from assigned videos; their other categories will stay.`,
        )
      )
        return;
      try {
        await post("category.php", {
          action: "delete",
          id: Number(row.dataset.id),
        });
        location.reload();
      } catch (error) {
        alert(error.message);
      }
    }),
  );

    //Production / Studios Section

    document
      .querySelector("#addProduction")
      ?.addEventListener("click", async () => {
        const input = document.querySelector("#newProductionName"),
          name = input.value.trim();
        if (!name) return;
        try {
          await post("production.php", { action: "create", name });
          location.reload();
        } catch (error) {
          alert(error.message);
        }
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
            `Delete “${row.querySelector("span").textContent}”? It will be removed from assigned videos; their other productions/studios will stay.`,
          )
        )
          return;
        try {
          await post("production.php", {
            action: "delete",
            id: Number(row.dataset.id),
          });
          location.reload();
        } catch (error) {
          alert(error.message);
        }
      }),
    );

  document.querySelectorAll(".restore-video").forEach((button) =>
    button.addEventListener("click", async () => {
      try {
        await post("video-status.php", {
          id: button.closest(".removed-row").dataset.id,
          active: true,
        });
        location.reload();
      } catch (error) {
        alert(error.message);
      }
    }),
  );
})();
