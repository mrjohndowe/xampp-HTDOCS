<?php declare(strict_types=1);

if (
  !defined('THE_KIDS_INTERNAL_REQUEST') ||
  THE_KIDS_INTERNAL_REQUEST !== true
) {
  http_response_code(404);
  header('Location:../special/');
  exit('Request not allowed. Please use the special page to access this content.');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <base href="/theKids/">
  <title>Our Three Little Stars ✨</title>
<link rel="stylesheet" href="assets/css/style.css?v=20260904-13">
  
</head>
<body>
  <div class="stars"></div>
  <div class="floating f1">🌸</div><div class="floating f2">✨</div><div class="floating f3">🧡</div>
  <div class="floating f4">🦋</div><div class="floating f5">🌷</div><div class="floating f6">⭐</div>

  <section class="hero">
    <div class="cloud">☁️</div>
    <div class="hero-image"><img src="assets/images/our-three-stars.png" alt="Original illustration of three siblings"></div>
    <p class="eyebrow">✨ THREE LITTLE LIVES · ONE BIG LOVE ✨</p>
    <h1>Our Three<br><span>Little Stars</span> ✨</h1>
    <p class="hero-text">Before they were big enough to remember it all,<br>we wanted a place to keep the magic. 🌙</p>
    <button onclick="startStory()">Begin Their Story ✨</button>
  </section>

  <section class="story-section">
    <p class="section-label">📖 Chapter One</p>
    <h2>Once Upon Three Little Lights...</h2>
    <div class="story-card">
      <div class="story-cartoon">🌟 &nbsp; 🌙 &nbsp; 🌟</div>
      <p>Every home has a rhythm. Ours is made of bright questions, belly laughs, sleepy hugs, and three different ways of making an ordinary day feel completely new.</p>
      <div class="quote">“The days can feel long, but the years always find a way to run.”</div>
    </div>
  </section>

  <section class="letter-section">
    <div class="envelope-experience">
      <p class="section-label">💌 A Letter From Our Hearts</p>
      <h2>A Little Letter For You</h2>

      <div class="envelope-scene" id="envelopeScene">
        <article class="letter-paper" id="letterPaper" aria-hidden="true">
          <div class="letter-top">✦</div>
          <p class="letter-text">We may not get every moment right. We may forget where we put the shoes, lose track of the time, and wish the days had a few more quiet minutes...</p>
          <p class="highlight">But we will never forget how much you changed everything. <br>You made us feel so loved. <br>You made us PARENTS... 🤍</p>
          <p>You are loved in the loud moments and the small ones. In every brave first try, every new word, every growing-up day that arrives before we are ready.</p>
          <div class="signature">Always your biggest fans... <br>Mommy & Daddy✨</div>
        </article>

        <button
          class="envelope"
          id="envelopeButton"
          type="button"
          aria-expanded="false"
          aria-controls="letterPaper"
          aria-label="Open the letter"
          onclick="toggleLetter()"
        >
          <span class="envelope-back" aria-hidden="true"></span>
          <span class="envelope-flap" aria-hidden="true"></span>
          <span class="envelope-front" aria-hidden="true"></span>
          <span class="envelope-seal" aria-hidden="true">♥</span>
        </button>
      </div>

      <p class="envelope-hint" id="envelopeHint">Tap the envelope to open it ✨</p>
    </div>
  </section>

  <section class="portrait-section">
    <p class="section-label">📸 A Little Piece Of Right Now</p>
    <h2>The Faces We Never Want To Forget</h2>
    <p class="section-description">Three little people. Three completely different kinds of wonderful. Tap a photo for a note. ✨</p>
    <div class="portrait-grid">
      <button class="portrait-card portrait-one" onclick="revealPortrait(0)">
        <img src="assets/images/little-star-three.jpg" alt="A happy child playing in the mud">
        <span>Little Star One ✦</span>
      </button>
      <button class="portrait-card portrait-two" onclick="revealPortrait(1)">
        <img src="assets/images/little-star-two-new.png" alt="A smiling child outside">
        <span>Little Star Two ✦</span>
      </button>
      <button class="portrait-card portrait-three" onclick="revealPortrait(2)">
        <img src="assets/images/little-star-three-new.png" alt="A child sitting outside">
        <span>Little Star Three ✦</span>
      </button>
    </div>
    <div class="portrait-note" id="portraitNote" aria-live="polite">A little picture from a little season we will always remember. 🤍</div>
  </section>

  <section class="magic-section" id="their-worlds">
    <p class="section-label">🌷 Chapter Two</p>
    <h2>Three Little Worlds</h2>
    <p class="section-description">Tap a star and let each little world say hello. ✨</p>
    <div class="couples-grid kid-grid">
      <button class="couple-card kid-card" onclick="revealKid(0)"><div class="cartoon">🎀</div><h3>The Bright Spark</h3><p>A big heart with a million beautiful ideas.</p></button>
      <button class="couple-card kid-card" onclick="revealKid(1)"><div class="cartoon">🌼</div><h3>The Joyful Heart</h3><p>Wonder finds its way into every little thing.</p></button>
      <button class="couple-card kid-card" onclick="revealKid(2)"><div class="cartoon">🚀</div><h3>The Quiet Adventurer</h3><p>Small steps, wide eyes, an endless sky ahead.</p></button>
    </div>
    <div class="kid-reveal" id="kidReveal" aria-live="polite">Choose one of the three little worlds above. 💗</div>
  </section>

  <section class="poetry-section">
    <p class="section-label">🌙 Things We Hope You Know</p>
    <h2>For Your Growing Hearts</h2>
    <div class="poetry-container">
      <div class="poetry-card"><span>🫶</span><p>“You never have to earn a place in our hearts. You arrived with one already waiting.”</p></div>
      <div class="poetry-card"><span>🌱</span><p>“Take your time growing. There is no rush to be anything but you.”</p></div>
      <div class="poetry-card"><span>✨</span><p>“May you always feel safe enough to dream out loud.”</p></div>
    </div>
  </section>

  <section class="timeline-section">
    <p class="section-label">📖 Chapter Three</p>
    <h2>The Little Things We Keep</h2>
    <div class="timeline">
      <div class="timeline-item"><div class="timeline-icon">👣</div><div><h3>The Firsts</h3><p>The first steps, first words, and every “look what I can do!” moment.</p></div></div>
      <div class="timeline-item"><div class="timeline-icon">🎨</div><div><h3>The Everyday Art</h3><p>Crayon-covered paper, mismatched socks, and imaginations too big for one room.</p></div></div>
      <div class="timeline-item"><div class="timeline-icon">😂</div><div><h3>The Laughter</h3><p>The kind that fills the whole house and makes the hard days softer.</p></div></div>
      <div class="timeline-item"><div class="timeline-icon">🏡</div><div><h3>The Memories</h3><p>Not perfect. Not quiet. Completely, beautifully ours.</p></div></div>
    </div>
  </section>

  <section class="memory-section">
    <div class="memory-box">
      <div class="memory-top">📦✨</div><h2>Our Memory Box</h2>
      <p>Open it whenever you need a little reminder of what matters most.</p>
      <button id="memoryButton" type="button" aria-pressed="false" onclick="memorySurprise()">Open A Memory 🌸</button>
      <div id="memoryText" aria-live="polite">A keepsake is waiting inside. ✨</div>
    </div>
  </section>

  <section class="final-section">
    <div class="moon">🌙</div><div class="final-couple">🌟 🌟 🌟</div>
    <h2>May you always know<br>how loved you are.</h2>
    <p>This little page is only the beginning of the story. ✨</p>
    <div class="final-poetry">“One day you will be tall enough to reach the sky.<br><br>Until then, we will hold your hands and point out the stars.” 🤍</div>
    <div class="flowers">🌷 🌸 🦋 🌸 🌷</div>
    <p class="footer-text">Made with love, little memories & magic ✨</p>
  </section>
<script src="assets/js/script.js?v=20260904-15"></script>
</body></html>
