(() => { "use strict";
  const card = document.querySelector("#analysisCard"), button = document.querySelector("#analyzeVideo"), status = document.querySelector("#analysisStatus"), results = document.querySelector("#analysisResults");
  if (!card || !button || !status || !results) return;
  const field = (name) => document.querySelector(`[name="${name}"]`);
  const escape = (value) => String(value || "").replace(/[&<>\"]/g, (character) => ({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"}[character]));
  const list = (values) => Array.isArray(values) && values.length ? `<ul>${values.map(value => `<li>${escape(value)}</li>`).join("")}</ul>` : "<p>Not suggested.</p>";
  button.addEventListener("click", async () => {
    card.classList.add("is-loading"); button.disabled = true; button.textContent = "Analyzing…"; status.textContent = "Extracting temporary frames and requesting suggestions…"; results.hidden = true;
    try {
      const response = await fetch("video-analysis.php", {method:"POST", headers:{"Content-Type":"application/json"}, body:JSON.stringify({id:card.dataset.videoId})});
      const data = await response.json(); if (!response.ok) throw new Error(data.error || "Analysis could not be completed.");
      const suggestion = data.suggestion; const actors = suggestion.actors || []; const characters = suggestion.characters || []; const productions = suggestion.productions || [];
      results.innerHTML = `<p><strong>Suggested title:</strong> ${escape(suggestion.name)}</p><p><strong>Actors:</strong></p>${list(actors)}<p><strong>Characters:</strong></p>${list(characters)}<p><strong>Productions/studios:</strong></p>${list(productions)}<p><strong>Notes:</strong> ${escape(suggestion.summary || "No additional notes.")}</p><div class="analysis-actions"><button class="button primary" type="button" id="useAnalysis">Use suggestions</button><button class="button subtle" type="button" id="dismissAnalysis">Keep current details</button></div>`;
      results.hidden = false; status.textContent = "Review the suggestions before using them.";
      document.querySelector("#useAnalysis").onclick = () => { const name = field("name"); const actorField = field("actors"); const characterField = field("characters"); if (name && suggestion.name) name.value = suggestion.name; if (actorField && actors.length) actorField.value = actors.join(", "); if (characterField && characters.length) characterField.value = characters.join(", "); document.querySelectorAll('input[name="productionIds[]"]').forEach(input => { const label = input.closest("label")?.innerText?.trim().toLowerCase(); if (label && productions.some(value => value.toLowerCase() === label)) input.checked = true; }); status.textContent = "Suggestions applied to the form. Choose Save video to keep them."; };
      document.querySelector("#dismissAnalysis").onclick = () => { results.hidden = true; status.textContent = "Suggestions dismissed; no changes were made."; };
    } catch (error) { status.textContent = error.message || "Analysis could not be completed."; }
    finally { card.classList.remove("is-loading"); button.disabled = false; button.textContent = "✦ Analyze video"; }
  });
})();
