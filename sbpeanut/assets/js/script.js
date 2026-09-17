// ==========================================
// 💗 OUR LITTLE LOVE STORY
// ==========================================

// 🌸 START STORY

function startStory() {
  createHearts();

  setTimeout(() => {
    document.querySelector(".story-section").scrollIntoView({
      behavior: "smooth",
    });
  }, 500);
}

// 🎁 MAGIC BOX

function openMagic() {
  const box = document.getElementById("magicBox");

  const message = document.getElementById("magicMessage");

  box.classList.add("open");

  message.innerHTML =
    "✨ You found the little secret!<br><br>" +
    "Maybe the most beautiful part of a story " +
    "isn't how it begins... " +
    "but all the little moments that make " +
    "the heart whisper, " +
    "<strong>“I'm glad you have chosen to stay.”</strong> 💗";

  createHearts();

  createSparkles(box);
}

// 💌 MEMORY SURPRISE

function memorySurprise() {
  const memory = document.getElementById("memoryText");

  const memories = [
    "🌸 Some moments are small, but their feelings are enormous.",

    "🦋 The sweetest memories are often made from ordinary days.",

    "💗 Sometimes one little smile becomes a favorite memory.",

    "🌙 Some moments quietly stay in the heart for a very long time.",

    "✨ The best memories are the ones that make you smile when nobody is watching.",
  ];

  const random = memories[Math.floor(Math.random() * memories.length)];

  memory.innerHTML = random;

  memory.animate(
    [
      {
        opacity: 0,
        transform: "translateY(20px)",
      },

      {
        opacity: 1,
        transform: "translateY(0)",
      },
    ],

    {
      duration: 800,
      fill: "forwards",
    },
  );

  createHearts();
}

// 💕 FLOATING HEARTS

function createHearts() {
  for (let i = 0; i < 25; i++) {
    const heart = document.createElement("div");

    const icons = ["💗", "💕", "💖", "🌸", "✨", "🦋"];

    heart.innerHTML = icons[Math.floor(Math.random() * icons.length)];

    heart.style.position = "fixed";

    heart.style.left = Math.random() * 100 + "vw";

    heart.style.bottom = "-40px";

    heart.style.fontSize = Math.random() * 20 + 15 + "px";

    heart.style.zIndex = "999";

    heart.style.pointerEvents = "none";

    document.body.appendChild(heart);

    const duration = Math.random() * 3 + 3;

    heart.animate(
      [
        {
          transform: "translateY(0) rotate(0deg)",

          opacity: 1,
        },

        {
          transform: `translateY(-${window.innerHeight + 100}px)
                         rotate(${Math.random() * 360}deg)`,

          opacity: 0,
        },
      ],

      {
        duration: duration * 1000,

        easing: "ease-out",
      },
    );

    setTimeout(() => {
      heart.remove();
    }, duration * 1000);
  }
}

// ✨ SPARKLE EFFECT

function createSparkles(element) {
  const rect = element.getBoundingClientRect();

  for (let i = 0; i < 20; i++) {
    const sparkle = document.createElement("span");

    sparkle.innerHTML = "✨";

    sparkle.style.position = "fixed";

    sparkle.style.left = rect.left + Math.random() * rect.width + "px";

    sparkle.style.top = rect.top + Math.random() * rect.height + "px";

    sparkle.style.zIndex = "1000";

    sparkle.style.pointerEvents = "none";

    document.body.appendChild(sparkle);

    sparkle.animate(
      [
        {
          transform: "scale(0)",
          opacity: 0,
        },

        {
          transform: "scale(1.5)",
          opacity: 1,
        },

        {
          transform: "translateY(-60px) scale(0)",

          opacity: 0,
        },
      ],

      {
        duration: 1200,

        easing: "ease-out",
      },
    );

    setTimeout(() => {
      sparkle.remove();
    }, 1200);
  }
}

// 🌸 RANDOM HEARTS ON PAGE LOAD

window.addEventListener("load", () => {
  setTimeout(createHearts, 1200);
});
