(() => {
  "use strict";
  const autoNext = document.querySelector("#autoNextPreference");
  const startMuted = document.querySelector("#startMutedPreference");
  const status = document.querySelector("#preferenceSaveStatus");
  const debugEnabled = document.querySelector("#debugEnabledPreference");
  const ffmpegPath = document.querySelector("#ffmpegPathPreference");
  if (!autoNext || !startMuted || !debugEnabled || !status) return;

  let saveSequence = 0;
  async function save() {
    const sequence = ++saveSequence;
    status.textContent = "Saving…";
    status.classList.remove("is-error");
    try {
      const response = await fetch("player-preferences.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ autoNext: autoNext.checked, startMuted: startMuted.checked, debugEnabled: debugEnabled.checked, ffmpegPath: ffmpegPath ? ffmpegPath.value.trim() : undefined }),
      });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || "Unable to save playback preferences.");
      if (sequence === saveSequence) status.textContent = "Saved.";
    } catch (error) {
      if (sequence === saveSequence) {
        status.textContent = error.message || "Unable to save playback preferences.";
        status.classList.add("is-error");
      }
    }
  }

  autoNext.addEventListener("change", save);
  startMuted.addEventListener("change", save);
  debugEnabled.addEventListener("change", save);
  ffmpegPath?.addEventListener("change", save);
})();
