(() => { "use strict";
  const card = document.querySelector("#analysisCard"), button = document.querySelector("#analyzeVideo"), status = document.querySelector("#analysisStatus"), results = document.querySelector("#analysisResults"), progress = document.querySelector("#analysisProgress"), progressBar = document.querySelector("#analysisProgressBar"), progressFill = document.querySelector("#analysisProgressFill"), progressStage = document.querySelector("#analysisProgressStage"), progressPercent = document.querySelector("#analysisProgressPercent");
  if (!card || !button || !status || !results || !progress || !progressBar || !progressFill || !progressStage || !progressPercent) return;
  const field = (name) => document.querySelector(`[name="${name}"]`);
  const escape = (value) => String(value || "").replace(/[&<>\"]/g, (character) => ({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"}[character]));
const capitalizeTag = (value) => { value = String(value || "").trim();
    return value ? value.charAt(0).toUpperCase() + value.slice(1) : "";
  };
  const list = (values) => Array.isArray(values) && values.length ? `<ul>${values.map(value => `<li>${escape(value)}</li>`).join("")}</ul>` : "<p>Not suggested.</p>";
  const setProgress = (value, stage) => { const rounded = Math.max(0, Math.min(100, Math.round(value))); progress.hidden = false; progressFill.style.width = `${rounded}%`; progressBar.setAttribute("aria-valuenow", String(rounded)); progressStage.textContent = stage; progressPercent.textContent = `${rounded}%`; };

  function getExistingCategories() {
    const existing = new Set();
    document.querySelectorAll('input[name="categoryIds[]"]').forEach(input => {
      const label = input.closest("label")?.innerText?.trim();
      if (label) existing.add(label.toLowerCase());
    });
    return existing;
  }

  function getExistingProductions() {
    const existing = new Set();
    document.querySelectorAll('input[name="productionIds[]"]').forEach(input => {
      const label = input.closest("label")?.innerText?.trim();
      if (label) existing.add(label.toLowerCase());
    });
    return existing;
  }

  function addCheckboxToForm(name, value, inputName) {
    const fieldset = inputName === "categoryIds[]" ? document.querySelector(".category-choices div") : document.querySelector(".production-choices div");
    if (!fieldset) return;

    const label = document.createElement("label");
    label.innerHTML = `<input type="checkbox" name="${inputName}" value="${value}"><span>${escape(name)}</span>`;
    fieldset.appendChild(label);
  }

  function selectCheckboxesByNames(names, inputName) {
    const normalizedNames = names.map(name => name.toLowerCase().trim());
    let selectedCount = 0;

    document.querySelectorAll(`input[name="${inputName}"]`).forEach(input => {
      const label = input.closest("label");
      const span = label?.querySelector("span");
      const text = span?.textContent?.trim().toLowerCase();
      if (text && normalizedNames.includes(text)) {
        input.checked = true;
        selectedCount++;
      }
    });

    return selectedCount;
  }

  button.addEventListener("click", async () => {
    card.classList.add("is-loading"); button.disabled = true; button.textContent = "Analyzing…"; status.textContent = "Preparing local Ollama analysis…"; results.hidden = true;
    let progressValue = 12; setProgress(progressValue, "Preparing video frames…");
    const progressTimer = setInterval(() => { if (progressValue < 88) progressValue += progressValue < 35 ? 12 : progressValue < 62 ? 8 : 4; const stage = progressValue < 35 ? "Extracting local still frames…" : progressValue < 62 ? "Ollama is reading the frames…" : "Ollama is thinking about the scene…"; setProgress(progressValue, stage); }, 1800);
    try {
      const response = await fetch("video-analysis.php", {method:"POST", headers:{"Content-Type":"application/json"}, body:JSON.stringify({id:card.dataset.videoId})});
      const data = await response.json(); if (!response.ok) throw new Error(data.error || "Analysis could not be completed.");
      const suggestion = data.suggestion; const actors = suggestion.actors || []; const characters = suggestion.characters || []; const studios = suggestion.studios || []; const productions = suggestion.productions || []; const categories = suggestion.categories || []; const genres = suggestion.genres || [];
      const productionSuggestions = [...studios, ...productions]
        .map(capitalizeTag)
        .filter(Boolean);

      const categorySuggestions = [...categories, ...genres]
        .map(capitalizeTag)
        .filter(Boolean);

      // Check for new categories and productions that need to be added
      const existingCategories = getExistingCategories();
      const existingProductions = getExistingProductions();

      const newCategories = categorySuggestions.filter(cat => !existingCategories.has(cat.toLowerCase()));
      const newProductions = productionSuggestions.filter(prod => !existingProductions.has(prod.toLowerCase()));

      // Add new categories and productions to database if needed
      let addedTags = {categories: [], productions: []};
      if (newCategories.length > 0 || newProductions.length > 0) {
        setProgress(95, "Adding new categories and productions…");
        const tagsResponse = await fetch("add-analysis-tags.php", {
          method: "POST",
          headers: {"Content-Type": "application/json"},
          body: JSON.stringify({categories: newCategories, productions: newProductions})
        });
        const tagsData = await tagsResponse.json();
        if (tagsData.success && tagsData.added) {
          addedTags = tagsData.added;
          // Add new checkboxes to the form and mark them for auto-selection
          tagsData.added.categories.forEach(cat => {
            addCheckboxToForm(cat.name, cat.id, "categoryIds[]");
            // Auto-select newly added category if it was in the suggestions
            if (categorySuggestions.some(s => s.toLowerCase() === cat.name.toLowerCase())) {
              setTimeout(() => {
                const newCheckbox = document.querySelector(`input[name="categoryIds[]"][value="${cat.id}"]`);
                if (newCheckbox) newCheckbox.checked = true;
              }, 10);
            }
          });
          tagsData.added.productions.forEach(prod => {
            addCheckboxToForm(prod.name, prod.id, "productionIds[]");
            // Auto-select newly added production if it was in the suggestions
            if (productionSuggestions.some(s => s.toLowerCase() === prod.name.toLowerCase())) {
              setTimeout(() => {
                const newCheckbox = document.querySelector(`input[name="productionIds[]"][value="${prod.id}"]`);
                if (newCheckbox) newCheckbox.checked = true;
              }, 10);
            }
          });
        }
      }

      results.innerHTML = `<p><strong>Suggested title:</strong> ${escape(suggestion.name)}</p><p><strong>Actors:</strong></p>${list(actors)}<p><strong>Characters:</strong></p>${list(characters)}<p><strong>Studios:</strong></p>${list(studios)}<p><strong>Productions/franchises:</strong></p>${list(productions)}<p><strong>Categories:</strong></p>${list(categories)}<p><strong>Genres:</strong></p>${list(genres)}<p><strong>Notes:</strong> ${escape(suggestion.summary || "No additional notes.")}</p>${addedTags.categories.length > 0 || addedTags.productions.length > 0 ? '<p class="new-tags-added"><strong>New tags added:</strong> ' + escape([...addedTags.categories.map(c => c.name), ...addedTags.productions.map(p => p.name)].join(", ")) + '</p>' : ''}<div class="analysis-actions"><button class="button primary" type="button" id="useAnalysis">Use suggestions</button><button class="button subtle" type="button" id="dismissAnalysis">Keep current details</button></div>`;
      setProgress(100, "Suggestions ready."); results.hidden = false; status.textContent = "Review the suggestions before using them.";
      document.querySelector("#useAnalysis").onclick = () => {
        const name = field("name");
        const actorField = field("actors");
        const notesField = field("notes");

        // Apply basic fields
        if (name && suggestion.name) name.value = suggestion.name;
        if (actorField && (actors.length || characters.length)) actorField.value = [...actors, ...characters].join(", ");
        if (notesField && suggestion.summary) notesField.value = suggestion.summary;

        // Select checkboxes by name matching
        const selectedCategories = selectCheckboxesByNames(categorySuggestions, "categoryIds[]");
        const selectedProductions = selectCheckboxesByNames(productionSuggestions, "productionIds[]");

        status.textContent = `Suggestions applied to the form. ${selectedCategories + selectedProductions} tag(s) selected. Choose Save video to keep them.`;
      };
      document.querySelector("#dismissAnalysis").onclick = () => { results.hidden = true; status.textContent = "Suggestions dismissed; no changes were made."; };
    } catch (error) { progress.hidden = true; status.textContent = error.message || "Analysis could not be completed."; }
    finally { clearInterval(progressTimer); card.classList.remove("is-loading"); button.disabled = false; button.textContent = "✦ Analyze with Ollama"; }
  });
})();
