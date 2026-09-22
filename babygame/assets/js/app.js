let hunger = 0.65, happy = 0.80, sleep = 0.70, alive = true;
let chatBox = document.getElementById('chat');
let bubble = document.getElementById('bubble');
let baby = document.getElementById('baby');

let qa = [
    { q: "Are you hungry?", a: ["YES! I'm STARVING! 🍼", "Feed me!", "I want milk!"] },
    { q: "Do you love me?", a: ["I love you so much! ❤️", "You are my favorite!", "❤️❤️❤️"] },
    { q: "Want to play?", a: ["YEEEES! 🎈", "Hehehe!", "Catch me!"] },
    { q: "Are you sleepy?", a: ["Zzz... a little... 😴", "No!", "*yawn* yes..."] },
    { q: "What's your name?", a: ["I'm Baby! 👶", "Call me Boo Boo!", "I'm YOUR baby!"] },
    { q: "Are you happy?", a: ["So happy! 😄", "Yes!", "HAPPY HAPPY!"] },
];

function updateBars() {
    document.getElementById('hungerFill').style.width = hunger + '%';
    document.getElementById('happyFill').style.width = happy + '%';
    document.getElementById('sleepFill').style.width = sleep + '%';
    if (hunger < 25) baby.innerText = '😭'; else if (sleep < 20) baby.innerText = '🥱'; else if (happy > 85) baby.innerText = '😄'; else baby.innerText = '👶';
}
function addChat(who, text) { let d = document.createElement('div'); d.className = 'msg ' + who; d.innerText = text; chatBox.appendChild(d); chatBox.scrollTop = chatBox.scrollHeight; }
function ask(i) { if (!alive) return; if (hunger < 0.15) { bubble.innerText = "Too hungry to talk! Feed me! 😭"; addChat('baby', bubble.innerText); return; } let q = qa[i].q; let a = qa[i].a[Math.floor(Math.random() * qa[i].a.length)]; addChat('you', q); setTimeout(() => { bubble.innerText = a; addChat('baby', a); happy = Math.min(100, happy + 8); hunger = Math.max(0, hunger - 0.03); updateBars(); }, 350); }
function feed() { hunger = Math.min(100, hunger + 0.30); happy = Math.min(100, happy + 10); bubble.innerText = "Yummy! 😋"; addChat('baby', bubble.innerText); updateBars(); }
function play() { if (hunger < 0.15) { bubble.innerText = "Too hungry! 😭"; return; } happy = Math.min(100, happy + 22); hunger = Math.max(0, hunger - 0.10); sleep = Math.max(0, sleep - 12); bubble.innerText = "Hehehe! 🎈"; addChat('baby', bubble.innerText); updateBars(); }
function sleepBaby() { sleep = Math.min(100, sleep + 35); bubble.innerText = "Zzz... 😴"; baby.innerText = '😴'; addChat('baby', "Zzz..."); setTimeout(updateBars, 800); }

function die(reason) {
    alive = false;
    document.getElementById('deadReason').innerText = reason;
    document.getElementById('dead').classList.remove('hidden');
}

setInterval(() => {
    if (!alive) return;
    hunger -= 1.65; happy -= 0.18; sleep -= 0.12;
    if (hunger <= 0) die("you let your baby starve to death.");
    else if (happy <= 0) die("you let your baby die of sadness.");
    else if (sleep <= 0) die("you never let your baby sleep.");
    updateBars();
}, 120);

updateBars();
addChat('baby', "Hi! Ask me something! 👇");
