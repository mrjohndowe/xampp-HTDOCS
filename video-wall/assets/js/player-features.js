(() => {
  "use strict";
  const player = document.querySelector("#player");
  if (!player) return;
  const library = window.VIDEO_LIBRARY || [];
  const preferences = Object.assign(
    { autoNext: true, startMuted: false },
    window.PLAYER_SETTINGS || {},
  );
  const save = () => {};
  const autoNext = document.querySelector("#autoNextSetting");
  const startMuted = document.querySelector("#startMutedSetting");

  if (autoNext) {
    autoNext.checked = preferences.autoNext;
    autoNext.addEventListener("change", () => {
      preferences.autoNext = autoNext.checked;
      save();
    });
  }
  if (startMuted) {
    startMuted.checked = preferences.startMuted;
    startMuted.addEventListener("change", () => {
      preferences.startMuted = startMuted.checked;
      save();
    });
  }

  function currentIndex() {
    const cards = [...document.querySelectorAll("#filmstrip .strip-card")];
    const active = document.querySelector("#filmstrip .strip-card.active");
    return Math.max(0, cards.indexOf(active));
  }
  function playNext() {
    const cards = [...document.querySelectorAll("#filmstrip .strip-card")];
    if (!cards.length) return;
    const next = (currentIndex() + 1) % cards.length;
    cards[next].click();
  }
  function showVideoInformation() {
    const video = library[currentIndex()];
    if (!video) return;
    const fields = {
      playerActors: [video.actors, video.characters].filter(Boolean).join(", "),
      playerNotes: video.notes,
      playerPublishDate: video.publishDate,
      playerProduction: video.production,
    };
    Object.entries(fields).forEach(([id, value]) => {
      const element = document.querySelector("#" + id);
      if (element) {
        element.textContent = value || "Not added";
        element.title = value || "";
      }
    });
  }

  document.querySelector("#nextPlayer")?.addEventListener("click", playNext);
  document
    .querySelector("#homePlayer")
    ?.addEventListener("click", () =>
      document.querySelector("#closePlayer")?.click(),
    );
  player.addEventListener("loadstart", () => {
    player.muted = preferences.startMuted;
    setTimeout(showVideoInformation);
  });
  player.addEventListener(
    "ended",
    (event) => {
      event.stopImmediatePropagation();
      if (preferences.autoNext) playNext();
    },
    true,
  );

  document.querySelector("#renameCurrent")?.addEventListener("click", () => {
    const video = library[currentIndex()];
    if (video)
      location.href = "edit-video.php?id=" + encodeURIComponent(video.id);
  });

  document.querySelector("#deactivateCurrent")?.addEventListener("click", async (event) => {
    const video = library[currentIndex()];
    if (!video) return;
    const button = event.currentTarget;
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = "Deactivating…";
    try {
      const response = await fetch("video-status.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: video.id, active: false }),
      });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || "Unable to deactivate this video.");
      localStorage.removeItem("video-wall:" + video.id);
      location.reload();
    } catch (error) {
      button.disabled = false;
      button.textContent = originalText;
      alert(error.message || "Unable to deactivate this video.");
    }
  });
})();
