let storySections = [];
let activeStorySection = 0;

const chapterRequirements = [
  { selector: ".letter-section", key: "letter", total: 1, prompt: "Open the envelope to continue" },
  { selector: ".portrait-section", key: "portraits", total: 3, prompt: "Tap all three photos to continue" },
  { selector: ".magic-section", key: "kids", total: 3, prompt: "Explore all three little worlds to continue" },
  { selector: ".memory-section", key: "memory", total: 3, prompt: "Open memories to continue" }
];

const chapterProgress = {
  letter: new Set(),
  portraits: new Set(),
  kids: new Set(),
  memory: new Set()
};

function getChapterRequirement(section) {
  return chapterRequirements.find((requirement) => section.matches(requirement.selector));
}

function updateChapterGate(section) {
  const requirement = getChapterRequirement(section);

  if (!requirement) {
    return;
  }

  const completed = chapterProgress[requirement.key].size;
  const isComplete = completed >= requirement.total;
  const nextButton = section.querySelector(".section-next-button");
  const status = section.querySelector(".section-requirement");

  nextButton.disabled = !isComplete;
  nextButton.setAttribute("aria-disabled", String(!isComplete));

  if (isComplete) {
    status.textContent = "Chapter complete — you can continue ✨";
    status.classList.add("complete");
  } else {
    const counter = requirement.total > 1 ? ` · ${completed} / ${requirement.total}` : "";
    status.textContent = `${requirement.prompt}${counter}`;
    status.classList.remove("complete");
  }
}

function completeChapterInteraction(key, item, element) {
  chapterProgress[key].add(item);

  if (element) {
    element.classList.add("interaction-complete");
    element.setAttribute("aria-pressed", "true");
  }

  const requirement = chapterRequirements.find((entry) => entry.key === key);
  const section = requirement ? document.querySelector(requirement.selector) : null;

  if (section) {
    updateChapterGate(section);
  }
}

function showStorySection(index) {
  if (!storySections.length) {
    return;
  }

  const nextIndex = (index + storySections.length) % storySections.length;

  storySections.forEach((section, sectionIndex) => {
    const isActive = sectionIndex === nextIndex;

    section.classList.toggle("story-page-active", isActive);
    section.setAttribute("aria-hidden", String(!isActive));

    if (isActive) {
      section.scrollTop = 0;
    }
  });

  activeStorySection = nextIndex;
}

function createSectionNavigation() {
  storySections = Array.from(document.querySelectorAll("body > section"));

  storySections.forEach((section, index) => {
    section.classList.add("story-page");

    if (index === 0) {
      section.classList.add("story-page-active");
      section.setAttribute("aria-hidden", "false");
      return;
    }

    section.setAttribute("aria-hidden", "true");

    const navigation = document.createElement("div");
    const step = document.createElement("span");
    const button = document.createElement("button");
    const isFinalSection = index === storySections.length - 1;
    const requirement = getChapterRequirement(section);

    navigation.className = "section-navigation";
    step.className = "section-step";
    step.textContent = `${index + 1} / ${storySections.length}`;

    button.type = "button";
    button.className = "section-next-button";
    button.textContent = isFinalSection ? "Back to the beginning ↑" : "Next ↓";
    button.addEventListener("click", () => {
      showStorySection(isFinalSection ? 0 : index + 1);
      createHearts();
    });

    if (requirement) {
      const status = document.createElement("span");
      status.className = "section-requirement";
      status.setAttribute("aria-live", "polite");
      navigation.classList.add("has-requirement");
      navigation.appendChild(status);
    }

    navigation.append(step, button);
    section.appendChild(navigation);
    updateChapterGate(section);
  });

  document.body.classList.add("chapter-view-ready");
}

function startStory() {
  createHearts();
  showStorySection(1);
}

function toggleLetter() {
  const scene = document.getElementById("envelopeScene");
  const button = document.getElementById("envelopeButton");
  const paper = document.getElementById("letterPaper");
  const hint = document.getElementById("envelopeHint");
  const isOpen = scene.classList.toggle("open");

  button.setAttribute("aria-expanded", String(isOpen));
  button.setAttribute("aria-label", isOpen ? "Close the letter" : "Open the letter");
  paper.setAttribute("aria-hidden", String(!isOpen));
  hint.textContent = isOpen ? "Tap the envelope to tuck the letter away" : "Tap the envelope to open it ✨";

  if (isOpen) {
    completeChapterInteraction("letter", "opened", button);
    createHearts();
  }
}

const kidMessages = [
  "🎀 The Bright Spark: full of questions, courage, and the kind of joy that makes a room brighter. Keep being wonderfully, loudly you. ✨",
  "🌼 The Joyful Heart: the little one who reminds us that wonder is hiding everywhere — in a laugh, a puddle, a snack, a hug. 💛",
  "🚀 The Quiet Adventurer: already discovering the world one small, determined step at a time. We cannot wait to see where you go. 🌙"
];

function revealKid(index) {
  const reveal = document.getElementById("kidReveal");
  const card = document.querySelectorAll(".kid-card")[index];
  reveal.textContent = kidMessages[index];
  completeChapterInteraction("kids", index, card);
  reveal.animate([{opacity:0,transform:"translateY(14px)"},{opacity:1,transform:"translateY(0)"}], {duration:500, fill:"forwards"});
  createHearts();
}

let activePortraitIndex = null;

function placePortraitNote() {
  const note = document.getElementById("portraitNote");
  const grid = document.querySelector(".portrait-grid");

  if (!note || !grid) {
    return;
  }

  if (window.matchMedia("(max-width: 600px)").matches && activePortraitIndex !== null) {
    const selectedCard = grid.querySelectorAll(".portrait-card")[activePortraitIndex];

    if (selectedCard) {
      selectedCard.insertAdjacentElement("afterend", note);
    }
  } else {
    grid.insertAdjacentElement("afterend", note);
  }
}

function revealPortrait(index) {
  const notes = [
    "That unforgettable little pout — the kind of face that could make us smile no matter what. 🌸",
    "A tiny face, a huge personality, and a whole world of beautiful things still to discover. 🌙",
    "Proof that the very best adventures sometimes involve getting wonderfully messy. 🌿"
  ];

  activePortraitIndex = index;

  const note = document.getElementById("portraitNote");
  const card = document.querySelectorAll(".portrait-card")[index];
  const image = card.querySelector("img");
  note.textContent = notes[index];

  if (index === 0 && !card.classList.contains("photo-swapped")) {
    image.classList.add("photo-changing");

    window.setTimeout(() => {
      image.src = "assets/images/little-star-one-surprise.jpg";
      image.alt = "Little Star One making a playful pout in a red outfit";
      card.classList.add("photo-swapped");
      image.classList.remove("photo-changing");
    }, 180);
  }

  placePortraitNote();
  completeChapterInteraction("portraits", index, card);

  note.animate([{opacity:0, transform:"translateY(12px)"},{opacity:1, transform:"translateY(0)"}], {duration:450, fill:"forwards"});
  createHearts();
}

window.addEventListener("resize", placePortraitNote);

const memoryKeepsakes = [
  "🌸 The way your laugh can change the whole mood of a room.",
  "🦋 The tiny hands that will not stay tiny forever.",
  "💗 The bedtime stories we will always be glad we paused for.",
  "🌙 The ordinary days that are secretly becoming the good old days.",
  "✨ The wonderful privilege of getting to be your home.",
  "🧸 The favorite toy carried everywhere like a tiny best friend.",
  "🎨 The masterpieces made with crayons, imagination, and very serious concentration.",
  "👣 The little footsteps racing toward us when we walk through the door.",
  "🥞 The slow mornings, sticky fingers, and breakfasts that become adventures.",
  "🌧️ The puddles that were far too important to walk around.",
  "🎵 The made-up songs whose words only our family understands.",
  "🤍 The sleepy hugs that somehow make the whole world feel quiet again."
];

let lastMemoryIndex = -1;
let memoryOpenCount = 0;

function memorySurprise() {
  const memory = document.getElementById("memoryText");
  const button = document.getElementById("memoryButton");
  let nextMemoryIndex = Math.floor(Math.random() * memoryKeepsakes.length);

  if (memoryKeepsakes.length > 1) {
    while (nextMemoryIndex === lastMemoryIndex) {
      nextMemoryIndex = Math.floor(Math.random() * memoryKeepsakes.length);
    }
  }

  lastMemoryIndex = nextMemoryIndex;
  memoryOpenCount += 1;
  memory.textContent = memoryKeepsakes[nextMemoryIndex];
  button.textContent = "Open Another Memory ✨";
  completeChapterInteraction("memory", memoryOpenCount, button);
  memory.animate([{opacity:0,transform:"translateY(20px)"},{opacity:1,transform:"translateY(0)"}], {duration:800,fill:"forwards"});
  createHearts();
}

function createHearts() {
  const icons = ["💗","🌸","✨","🦋","⭐"];
  for (let i = 0; i < 18; i++) {
    const heart = document.createElement("div");
    heart.textContent = icons[Math.floor(Math.random() * icons.length)];
    Object.assign(heart.style,{position:"fixed",left:Math.random()*100+"vw",bottom:"-40px",fontSize:Math.random()*20+15+"px",zIndex:"999",pointerEvents:"none"});
    document.body.appendChild(heart);
    const duration = Math.random()*3+3;
    heart.animate([{transform:"translateY(0) rotate(0deg)",opacity:1},{transform:"translateY(-"+(window.innerHeight+100)+"px) rotate("+(Math.random()*360)+"deg)",opacity:0}],{duration:duration*1000,easing:"ease-out"});
    setTimeout(()=>heart.remove(),duration*1000);
  }
}

window.addEventListener("load",()=>setTimeout(createHearts,900));

createSectionNavigation();
