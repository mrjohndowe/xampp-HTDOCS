(() => {
    "use strict";

    function bootTextMessageSimulator() {


        const EMOJI_MAP = {
            ":rofl:": "🤣", ":joy:": "😂", ":laughing:": "😆", ":smile:": "😄",
            ":grin:": "😁", ":wink:": "😉", ":blush:": "😊", ":heart_eyes:": "😍",
            ":kissing_heart:": "😘", ":thinking:": "🤔", ":neutral:": "😐",
            ":unamused:": "😒", ":rolling_eyes:": "🙄", ":cry:": "😢", ":sob:": "😭",
            ":angry:": "😠", ":rage:": "😡", ":scream:": "😱", ":flushed:": "😳",
            ":sweat_smile:": "😅", ":sunglasses:": "😎", ":smirk:": "😏",
            ":drooling:": "🤤", ":sleeping:": "😴", ":skull:": "💀", ":clown:": "🤡",
            ":heart:": "❤️", ":broken_heart:": "💔", ":pink_heart:": "🩷",
            ":purple_heart:": "💜", ":blue_heart:": "💙", ":green_heart:": "💚",
            ":black_heart:": "🖤", ":fire:": "🔥", ":sparkles:": "✨", ":star:": "⭐",
            ":boom:": "💥", ":100:": "💯", ":thumbsup:": "👍", ":thumbsdown:": "👎",
            ":ok_hand:": "👌", ":clap:": "👏", ":pray:": "🙏", ":wave:": "👋",
            ":eyes:": "👀", ":lips:": "👄", ":kiss:": "💋", ":phone:": "📱",
            ":message:": "💬", ":bell:": "🔔", ":check:": "✅", ":x:": "❌",
            ":warning:": "⚠️"
        };

        const APP_DATA = {
            conversations: [
                {
                    id: "SoloLearn",
                    name: "SoloLearn",
                    description: "A Conversation between you and SoloLearn Bot",
                    avatarHue: 175,
                    startDate: "2026-09-21 21:29",
                    localReplyMode: "friendly",
                    messages: [
                        {
                            sender: "other",
                            text: "Conversation.exe is starting...",
                            delay: 900,
                            typing: 1100,
                            keepInteractive: true
                        },
                        {
                            sender: "other",
                            text: "Hello, What is your name?",
                            delay: 900,
                            typing: 1100,
                            keepInteractive: true
                        },
                        {
                            type: "wait_for_response",
                            saveAs: "name",
                            generateReply: false
                        },
                        {
                            sender: "other",
                            text: "Nice to meet you {{name}}",
                            delay: 900,
                            typing: 1100,
                            keepInteractive: true
                        },
                        {
                            sender: "other",
                            text: "I am an Adaptive Response Intelligence Assistant but you can call me ARIA",
                            delay: 1800,
                            typing: 1100,
                            keepInteractive: true
                        },
                        {
                            sender: "other",
                            text: "How old are you?",
                            delay: 900,
                            typing: 1100,
                            keepInteractive: true
                        },
                        {
                            type: "wait_for_response",
                            saveAs: "age",
                            generateReply: false
                        },
                        {
                            sender: "other",
                            text: "You are so young, You are {{age}} years old, thats cute I'm 17483 years old",
                            delay: 900,
                            typing: 1100,
                            keepInteractive: false
                        }
                    ]
                },

                {
                    id: "hippy-karkat",
                    name: "Hippy & Karkat",
                    description: "A peaceful hippy attempts to survive a conversation with Karkat.",
                    avatarHue: 120,
                    startDate: "2026-09-21 21:25",
                    localReplyMode: "direct",

                    messages: [
                        {
                            sender: "other",
                            text: "Whats up maaan?",
                            delay: 900,
                            typing: 1100
                        },

                        {
                            sender: "me",
                            draft: "MY HATE IS THE LIFEBLOOD THAT PULSES THORUGH THE VEINS OF YOUR UNIVERSE.",
                            text: "MY HATE IS THE LIFEBLOOD THAT PULSES THROUGH THE VEINS OF YOUR UNIVERSE.",
                            delay: 1000,
                            compose: true,
                            typingSpeed: 48,
                            draftPause: 700,
                            deleteSpeed: 28,
                            recomposePause: 450,
                            recomposeTypingSpeed: 52,
                            mistakeChance: 0.08,
                            sendDelay: 700
                        },

                        {
                            sender: "other",
                            text: "Not cool bro.",
                            delay: 1100,
                            typing: 1000
                        },

                        {
                            sender: "me",
                            draft: "I GOT A LAB FULL OF HUAMNS, A MOUTH FULL OF YELLING, AND A TORTURED PSYCHOLOGICAL PROFILE FULL OF TOTALLY HYSTERICAL EMOTIONS...",
                            text: "I GOT A LAB FULL OF HUMANS, A MOUTH FULL OF YELLING, AND A TORTURED PSYCHOLOGICAL PROFILE FULL OF TOTALLY HYSTERICAL EMOTIONS AND UNAIRED GRIEVANCES AT PRACTICALLY EVERYBODY.",
                            delay: 1200,
                            compose: true,
                            typingSpeed: 42,
                            draftPause: 900,
                            deleteSpeed: 20,
                            recomposePause: 600,
                            recomposeTypingSpeed: 44,
                            mistakeChance: 0.10,
                            sendDelay: 800
                        },

                        {
                            sender: "other",
                            draft: "Quit harsing my mellow man!!",
                            text: "Quit harshing my mellow man!!!",
                            delay: 1100,
                            compose: true,
                            typingSpeed: 65,
                            draftPause: 650,
                            deleteSpeed: 30,
                            recomposePause: 400,
                            recomposeTypingSpeed: 62,
                            mistakeChance: 0.06,
                            sendDelay: 600
                        },

                        {
                            sender: "me",
                            draft: "LOOKS LIKE I HAVE MET SOMEONE SENSIBEL FOR ONCE",
                            text: "LOOKS LIKE I HAVE MET SOMEONE SENSIBLE FOR ONCE",
                            delay: 1300,
                            compose: true,
                            typingSpeed: 50,
                            draftPause: 750,
                            deleteSpeed: 26,
                            recomposePause: 450,
                            recomposeTypingSpeed: 52,
                            mistakeChance: 0.08,
                            sendDelay: 700
                        },

                        {
                            type: "date_separator",
                            datetime: "2026-09-21 21:31"
                        },

                        {
                            sender: "other",
                            draft: "Alright maan, im out. Stay chill.",
                            text: "Alright maaan, I'm out. Stay chill.",
                            delay: 1300,
                            compose: true,
                            typingSpeed: 68,
                            draftPause: 600,
                            deleteSpeed: 30,
                            recomposePause: 350,
                            recomposeTypingSpeed: 65,
                            mistakeChance: 0.05,
                            sendDelay: 650
                        },

                        {
                            sender: "me",
                            draft: "GOODBYE. TRY NOT TO IRRITATE THE UNIVERSE ON YOUR WY OUT.",
                            text: "GOODBYE. TRY NOT TO IRRITATE THE UNIVERSE ON YOUR WAY OUT.",
                            delay: 1300,
                            compose: true,
                            typingSpeed: 50,
                            draftPause: 800,
                            deleteSpeed: 25,
                            recomposePause: 450,
                            recomposeTypingSpeed: 52,
                            mistakeChance: 0.08,
                            sendDelay: 750
                        }
                    ]
                },

                {
                    id: "jamie",
                    name: "Jamie",
                    description: "A scripted conversation with a browser-generated reply.",
                    avatarHue: 210,
                    startDate: "2026-09-16 18:30",
                    localReplyMode: "friendly",
                    messages: [
                        { sender: "other", text: "Hey! How has your day been going?", delay: 900, typing: 1100 },
                        { sender: "me", text: "A little busy, but I am finally slowing down.", delay: 1300, compose: true, typingSpeed: 48, sendDelay: 600, mistakeChance: 0.03 },
                        { sender: "other", text: "I am glad you are getting a breather. I was hoping to catch up.", delay: 1000, typing: 1450 },
                        { type: "date_separator", datetime: "2026-09-16 18:35" },
                        { sender: "other", text: "What is on your mind tonight?", delay: 1100, typing: 1200 },
                        { type: "wait_for_response", keepInteractive: true }
                    ]
                },
                {
                    id: "alex",
                    name: "Alex",
                    description: "Draft, backspace, recompose, typing sounds, and scripted playback.",
                    avatarHue: 336,
                    startDate: "2026-09-21 09:54",
                    localReplyMode: "direct",
                    messages: [
                        {
                            sender: "other",
                            text: "hey...",
                            delay: 1100,
                            typing: 900
                        },
                        {
                            sender: "me",
                            text: "hey yourself :rofl:",
                            delay: 800,
                            compose: true,
                            typingSpeed: 50,
                            sendDelay: 500
                        },
                        {
                            sender: "other",
                            text: "you used to not like being touched in an intimate way...",
                            delay: 1400,
                            typing: 1500
                        },
                        {
                            sender: "other",
                            text: "but I guess now you do...",
                            delay: 900,
                            typing: 1000
                        },
                        {
                            sender: "other",
                            text: "just not with me, right?",
                            delay: 700,
                            typing: 900
                        },
                        {
                            type: "wait_for_response",
                            keepInteractive: false
                        },
                        {
                            sender: "me",
                            draft: "I don't even know what you want me to say to that.",
                            text: "NVM on the flirting & roleplaying with me 😌",
                            delay: 900,
                            compose: true,
                            typingSpeed: 44,
                            draftPause: 900,
                            deleteSpeed: 24,
                            recomposePause: 450,
                            recomposeTypingSpeed: 58,
                            sendDelay: 700,
                            mistakeChance: 0.04
                        },
                        {
                            sender: "me",
                            text: "I got the memo.",
                            delay: 900,
                            compose: true,
                            typingSpeed: 70,
                            sendDelay: 550
                        }
                    ]
                }
            ]
        };

        const listView = document.getElementById("conversation-list-view");
        const conversationView = document.getElementById("conversation-view");
        const conversationListEl = document.getElementById("conversation-list");
        const messagesEl = document.getElementById("messages");
        const inputEl = document.getElementById("message-input");
        const sendButton = document.getElementById("send-button");
        const backButton = document.getElementById("back-button");
        const infoButton = document.getElementById("info-button");
        const imageButton = document.getElementById("image-button");
        const imageUpload = document.getElementById("image-upload");
        const avatarUpload = document.getElementById("avatar-upload");
        const contactAvatar = document.getElementById("contact-avatar");
        const contactName = document.getElementById("contact-name");
        const modal = document.getElementById("options-modal");
        const replayButton = document.getElementById("replay-button");
        const clearAvatarButton = document.getElementById("clear-avatar-button");
        const closeModalButton = document.getElementById("close-modal-button");

        let activeConversation = null;
        let playbackRunning = false;
        let playbackToken = 0;
        let playbackIndex = 0;
        let awaitingVisitorResponse = false;
        let generatedReplyInProgress = false;
        let keepInteractiveAfterReply = false;
        let currentWaitItem = null;
        let conversationMemory = Object.create(null);
        let chatHistory = [];
        let audioContext = null;

        const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

        function safeStorageGet(key) {
            try { return localStorage.getItem(key); } catch { return null; }
        }

        function safeStorageSet(key, value) {
            try { localStorage.setItem(key, value); } catch { /* Storage can be blocked in embeds. */ }
        }

        function safeStorageRemove(key) {
            try { localStorage.removeItem(key); } catch { /* Ignore. */ }
        }

        function getAudioContext() {
            if (!audioContext) {
                const AudioCtor = window.AudioContext || window.webkitAudioContext;
                if (AudioCtor) audioContext = new AudioCtor();
            }
            return audioContext;
        }

        function playTone(frequency, duration = 0.04, volume = 0.025, type = "sine") {
            const ctx = getAudioContext();
            if (!ctx || ctx.state !== "running") return;

            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();
            oscillator.type = type;
            oscillator.frequency.value = frequency;
            gain.gain.setValueAtTime(volume, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + duration);
            oscillator.connect(gain);
            gain.connect(ctx.destination);
            oscillator.start();
            oscillator.stop(ctx.currentTime + duration);
        }

        const playTypingSound = () => playTone(180 + Math.random() * 80, 0.025, 0.012, "square");
        const playBackspaceSound = () => playTone(120, 0.035, 0.018, "square");
        function playSendSound() {
            playTone(520, 0.06, 0.035, "sine");
            setTimeout(() => playTone(720, 0.08, 0.025, "sine"), 35);
        }
        function playReceiveSound() {
            playTone(700, 0.07, 0.035, "sine");
            setTimeout(() => playTone(920, 0.1, 0.025, "sine"), 55);
        }

        function replaceEmojiShortcodes(text) {
            let result = String(text ?? "");
            for (const [shortcode, emoji] of Object.entries(EMOJI_MAP)) {
                result = result.split(shortcode).join(emoji);
            }
            return result;
        }

        /*
         * Conversation memory
         * -------------------
         * Every visitor reply is automatically available as {{lastReply}}.
         * A wait_for_response item can also save it under a custom name:
         *
         * {
         *     type: "wait_for_response",
         *     keepInteractive: false,
         *     saveAs: "userAnswer",
         *     generateReply: false
         * }
         *
         * A later scripted message can then use:
         * "You said: {{userAnswer}}"
         */
        function resolveVariables(text) {
            return String(text ?? "").replace(/\{\{([^}]+)\}\}/g, (match, key) => {
                const name = String(key || "").trim();

                if (!name) return match;

                return Object.prototype.hasOwnProperty.call(conversationMemory, name)
                    ? String(conversationMemory[name])
                    : match;
            });
        }

        function rememberVisitorResponse(value) {
            const storedValue = String(value ?? "");

            // Always retain the newest visitor response.
            conversationMemory.lastReply = storedValue;

            // Optionally retain it under a custom name from wait_for_response.saveAs.
            const saveAs = String(currentWaitItem?.saveAs || "").trim();
            if (saveAs) {
                conversationMemory[saveAs] = storedValue;
            }
        }

        function initials(name) {
            return String(name || "?")
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2)
                .map((part) => part[0].toUpperCase())
                .join("") || "?";
        }

        function defaultAvatar(name, hue = 220) {
            const label = initials(name);
            const svg = `
      <svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">
        <defs>
          <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
            <stop stop-color="hsl(${hue} 78% 58%)"/>
            <stop offset="1" stop-color="hsl(${(hue + 38) % 360} 72% 38%)"/>
          </linearGradient>
        </defs>
        <rect width="128" height="128" rx="64" fill="url(#g)"/>
        <text x="64" y="72" text-anchor="middle" font-family="Arial, sans-serif" font-size="42" font-weight="700" fill="white">${label}</text>
      </svg>`;
            return `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`;
        }

        function avatarFor(conversation) {
            const saved = safeStorageGet(`sololearn-avatar:${conversation.id}`);
            return saved || defaultAvatar(conversation.name, conversation.avatarHue);
        }

        function formatMessageTime(date = new Date()) {
            return date.toLocaleTimeString([], { hour: "numeric", minute: "2-digit" });
        }

        function formatSeparator(datetime) {
            const date = new Date(String(datetime).replace(" ", "T"));
            if (Number.isNaN(date.getTime())) return String(datetime || "").toUpperCase();

            return date.toLocaleString("en-US", {
                month: "short",
                day: "numeric",
                hour: "numeric",
                minute: "2-digit",
                hour12: true
            })
                .replace(",", "")
                .replace(" AM", "AM")
                .replace(" PM", "PM")
                .toUpperCase()
                .replace(/(\d{1,2}:\d{2}(?:AM|PM))$/, "AT $1");
        }

        function scrollToBottom(behavior = "smooth") {
            requestAnimationFrame(() => {
                messagesEl.scrollTo({ top: messagesEl.scrollHeight, behavior });
            });
        }

        function createDateSeparator(datetime) {
            const separator = document.createElement("div");
            separator.className = "date-separator";
            const label = document.createElement("span");
            label.className = "date-separator-label";
            label.textContent = formatSeparator(datetime);
            separator.appendChild(label);
            messagesEl.appendChild(separator);
            scrollToBottom();
        }

        function createMessage(side, text, timestamp = null) {
            const row = document.createElement("div");
            row.className = `message-row ${side}`;

            const column = document.createElement("div");
            column.className = "message-column";

            const bubble = document.createElement("div");
            bubble.className = "message-bubble";

            const messageText = document.createElement("span");
            messageText.className = "message-text";
            messageText.textContent = replaceEmojiShortcodes(text);

            const time = document.createElement("span");
            time.className = "message-timestamp";
            time.textContent = timestamp || formatMessageTime();

            bubble.append(messageText, time);
            column.appendChild(bubble);
            row.appendChild(column);
            messagesEl.appendChild(row);
            scrollToBottom();
            return row;
        }

        function createImageMessage(side, imageSource, timestamp = null, caption = "") {
            const row = document.createElement("div");
            row.className = `message-row ${side}`;

            const column = document.createElement("div");
            column.className = "message-column image-message-column";

            const bubble = document.createElement("div");
            bubble.className = "message-bubble image-message-bubble";

            const img = document.createElement("img");
            img.className = "message-image";
            img.src = imageSource;
            img.alt = caption || "Image";
            bubble.appendChild(img);

            if (caption) {
                const captionEl = document.createElement("div");
                captionEl.className = "message-image-caption";
                captionEl.textContent = replaceEmojiShortcodes(caption);
                bubble.appendChild(captionEl);
            }

            const time = document.createElement("div");
            time.className = "message-image-timestamp";
            time.textContent = timestamp || formatMessageTime();
            bubble.appendChild(time);
            column.appendChild(bubble);
            row.appendChild(column);
            messagesEl.appendChild(row);

            img.addEventListener("load", () => scrollToBottom());
            scrollToBottom();
        }

        function createTypingIndicator() {
            const row = document.createElement("div");
            row.className = "message-row incoming typing-row";
            row.innerHTML = `
      <div class="message-column">
        <div class="message-bubble typing-bubble">
          <span></span><span></span><span></span>
        </div>
      </div>`;
            messagesEl.appendChild(row);
            scrollToBottom();
            return row;
        }

        function resizeComposer() {
            inputEl.style.height = "auto";
            inputEl.style.height = `${Math.min(inputEl.scrollHeight, 110)}px`;
        }

        async function deleteComposerText(speed = 35, token = playbackToken) {
            while (inputEl.value.length > 0 && token === playbackToken) {
                inputEl.value = inputEl.value.slice(0, -1);
                playBackspaceSound();
                resizeComposer();
                await sleep(Number(speed || 35) + Math.floor(Math.random() * 25));
            }
        }

        async function typeIntoComposer(text, speed = 55, pauseAt = {}, mistakeChance = 0.06, token = playbackToken) {
            inputEl.value = "";
            inputEl.focus();
            resizeComposer();

            const characters = String(text ?? "");
            const typoCharacters = "abcdefghijklmnopqrstuvwxyz";

            for (let i = 0; i < characters.length && token === playbackToken; i++) {
                const correctCharacter = characters[i];
                const canMakeMistake = /[a-z]/i.test(correctCharacter) && Math.random() < mistakeChance;

                if (canMakeMistake) {
                    let wrongCharacter = typoCharacters[Math.floor(Math.random() * typoCharacters.length)];
                    if (correctCharacter === correctCharacter.toUpperCase()) wrongCharacter = wrongCharacter.toUpperCase();

                    inputEl.value += wrongCharacter;
                    playTypingSound();
                    resizeComposer();
                    await sleep(120 + Math.random() * 280);

                    if (token !== playbackToken) return;
                    inputEl.value = inputEl.value.slice(0, -1);
                    playBackspaceSound();
                    resizeComposer();
                    await sleep(80 + Math.random() * 180);
                }

                if (token !== playbackToken) return;
                inputEl.value += correctCharacter;
                playTypingSound();
                resizeComposer();
                inputEl.scrollTop = inputEl.scrollHeight;

                const pauseKey = i + 1;
                if (Object.prototype.hasOwnProperty.call(pauseAt || {}, pauseKey)) {
                    await sleep(Number(pauseAt[pauseKey]) || 0);
                }
                await sleep(Number(speed || 55) + Math.floor(Math.random() * 45));
            }
        }

        async function playIncoming(item, token) {
            await sleep(Number(item.delay || 0));
            if (token !== playbackToken) return false;

            if (Number(item.typing || 0) > 0) {
                const typing = createTypingIndicator();
                await sleep(Number(item.typing));
                typing.remove();
                if (token !== playbackToken) return false;
            }

            playReceiveSound();

            const resolvedText = resolveVariables(item.text || "");
            const resolvedCaption = resolveVariables(item.caption || "");

            if (item.image) createImageMessage("incoming", item.image, item.timestamp, resolvedCaption);
            else createMessage("incoming", resolvedText, item.timestamp);

            if (resolvedText) chatHistory.push({ sender: "other", text: resolvedText });
            return true;
        }

        async function playOutgoing(item, token) {
            await sleep(Number(item.delay || 0));
            if (token !== playbackToken) return false;

            if (item.image) {
                await sleep(Number(item.sendDelay || 0));
                if (token !== playbackToken) return false;
                playSendSound();
                createImageMessage(
                    "outgoing",
                    item.image,
                    item.timestamp,
                    resolveVariables(item.caption || "")
                );
                return true;
            }

            const draft = resolveVariables(item.draft || "");
            const text = resolveVariables(item.text || "");

            if (item.compose) {
                if (draft) {
                    await typeIntoComposer(draft, item.typingSpeed || 65, item.draftPauseAt || {}, item.draftMistakeChance ?? item.mistakeChance ?? 0.06, token);
                    await sleep(Number(item.draftPause || 1000));
                    if (token !== playbackToken) return false;
                    await deleteComposerText(item.deleteSpeed || 35, token);
                    await sleep(Number(item.recomposePause || 500));
                }

                await typeIntoComposer(text, item.recomposeTypingSpeed ?? item.typingSpeed ?? 65, item.pauseAt || {}, item.mistakeChance ?? 0.06, token);
                await sleep(Number(item.sendDelay || 0));
                if (token !== playbackToken) return false;
                inputEl.value = "";
                resizeComposer();
            }

            playSendSound();
            createMessage("outgoing", text, item.timestamp);
            if (text) chatHistory.push({ sender: "me", text });
            return true;
        }

        async function continuePlayback() {
            if (!activeConversation || playbackRunning) return;

            playbackRunning = true;
            const token = playbackToken;

            try {
                while (playbackIndex < activeConversation.messages.length && token === playbackToken) {
                    const item = activeConversation.messages[playbackIndex];
                    playbackIndex += 1;

                    if (item.type === "date_separator") {
                        createDateSeparator(item.datetime);
                        continue;
                    }

                    if (item.type === "wait_for_response") {
                        awaitingVisitorResponse = true;
                        keepInteractiveAfterReply = Boolean(item.keepInteractive);
                        currentWaitItem = item;
                        inputEl.focus();
                        return;
                    }

                    const ok = item.sender === "other"
                        ? await playIncoming(item, token)
                        : await playOutgoing(item, token);

                    if (!ok) return;
                }
            } catch (error) {
                console.error("Conversation playback error:", error);
            } finally {
                playbackRunning = false;
            }
        }

        function generateLocalReply(message, mode = "friendly") {
            const text = String(message || "").trim().toLowerCase();

            const choose = (items) => items[Math.floor(Math.random() * items.length)];

            if (!text) return "I’m here.";
            if (/\b(hi|hey|hello|sup)\b/.test(text)) {
                return choose(["Hey 🙂 what’s going on?", "Hey. I’m here.", "Hi 🙂 tell me what’s up."]);
            }
            if (/\b(sorry|apologize|apology)\b/.test(text)) {
                return choose(["I appreciate you saying that.", "Thank you. I needed to hear that.", "Okay. I’m listening."]);
            }
            if (/\b(love|miss you|missed you)\b/.test(text)) {
                return choose(["That’s a lot to take in, but I hear you.", "I’ve missed parts of this too.", "I wasn’t expecting you to say that."]);
            }
            if (/\b(mad|angry|upset|hurt)\b/.test(text)) {
                return choose(["I can tell this is still bothering you.", "I get why you’re upset.", "I’m not trying to make this worse."]);
            }
            if (/\b(why|how|what|when|where|\?)\b/.test(text)) {
                return choose(["That’s fair. I’ve been trying to figure that out too.", "I don’t have a perfect answer, but I can be honest with you.", "I was wondering when you’d ask me that."]);
            }
            if (mode === "direct") {
                return choose(["I mean… that’s kind of what it felt like.", "I’m not trying to start a fight. I’m telling you how it looked from my side.", "You can tell me I’m wrong. I just wanted an honest answer."]);
            }

            return choose([
                "That makes sense. Tell me a little more.",
                "I hear you. I’m not judging you for it.",
                "Okay… I can understand where you’re coming from.",
                "I’m listening. Keep going."
            ]);
        }

        async function requestGeneratedReply() {
            if (!activeConversation || generatedReplyInProgress) return;

            generatedReplyInProgress = true;
            awaitingVisitorResponse = false;
            const token = playbackToken;
            const typing = createTypingIndicator();

            try {
                const latest = [...chatHistory].reverse().find((item) => item.sender === "me")?.text || "";
                const reply = generateLocalReply(latest, activeConversation.localReplyMode);
                const typingDelay = Math.max(700, Math.min(2200, reply.length * 32));
                await sleep(typingDelay);

                if (token !== playbackToken) return;
                typing.remove();
                playReceiveSound();
                createMessage("incoming", reply);
                chatHistory.push({ sender: "other", text: reply });

                if (keepInteractiveAfterReply) {
                    awaitingVisitorResponse = true;
                } else {
                    currentWaitItem = null;
                    setTimeout(() => continuePlayback(), 450);
                }
            } finally {
                if (typing.isConnected) typing.remove();
                generatedReplyInProgress = false;
            }
        }

        function sendManualMessage() {
            const rawValue = inputEl.value.trim();
            if (!rawValue || !activeConversation) return;

            const value = replaceEmojiShortcodes(rawValue);
            inputEl.value = "";
            resizeComposer();
            playSendSound();
            createMessage("outgoing", value);
            chatHistory.push({ sender: "me", text: value });

            if (awaitingVisitorResponse) {
                rememberVisitorResponse(value);

                // Set generateReply: false on wait_for_response when the next
                // scripted line should run immediately and use the saved answer.
                if (currentWaitItem?.generateReply === false) {
                    awaitingVisitorResponse = false;
                    keepInteractiveAfterReply = false;
                    currentWaitItem = null;
                    setTimeout(() => continuePlayback(), 250);
                } else {
                    requestGeneratedReply();
                }
            }
        }

        function fileToDataUrl(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(String(reader.result || ""));
                reader.onerror = () => reject(reader.error || new Error("Unable to read file."));
                reader.readAsDataURL(file);
            });
        }

        async function sendImage(file) {
            if (!file || !activeConversation) return;
            if (!file.type.startsWith("image/")) return;

            const source = await fileToDataUrl(file);
            playSendSound();
            createImageMessage("outgoing", source);
            chatHistory.push({ sender: "me", text: "[Image]" });

            if (awaitingVisitorResponse) {
                rememberVisitorResponse("[Image]");

                if (currentWaitItem?.generateReply === false) {
                    awaitingVisitorResponse = false;
                    keepInteractiveAfterReply = false;
                    currentWaitItem = null;
                    setTimeout(() => continuePlayback(), 250);
                } else {
                    requestGeneratedReply();
                }
            }
        }

        function renderConversationList() {
            conversationListEl.replaceChildren();

            APP_DATA.conversations.forEach((conversation) => {
                const card = document.createElement("button");
                card.className = "conversation-card";
                card.type = "button";

                const avatar = document.createElement("img");
                avatar.className = "conversation-card-avatar";
                avatar.src = avatarFor(conversation);
                avatar.alt = "";

                const content = document.createElement("div");
                content.className = "conversation-card-content";

                const top = document.createElement("div");
                top.className = "conversation-card-top";

                const name = document.createElement("strong");
                name.textContent = conversation.name;
                const arrow = document.createElement("span");
                arrow.className = "conversation-arrow";
                arrow.textContent = "›";
                top.append(name, arrow);

                const preview = document.createElement("div");
                preview.className = "conversation-preview";
                preview.textContent = conversation.description;

                content.append(top, preview);
                card.append(avatar, content);
                card.addEventListener("click", () => openConversation(conversation.id));
                conversationListEl.appendChild(card);
            });
        }

        function openConversation(id) {
            const conversation = APP_DATA.conversations.find((item) => item.id === id);
            if (!conversation) return;

            playbackToken += 1;
            activeConversation = conversation;
            playbackIndex = 0;
            playbackRunning = false;
            awaitingVisitorResponse = false;
            generatedReplyInProgress = false;
            keepInteractiveAfterReply = false;
            currentWaitItem = null;
            conversationMemory = Object.create(null);
            chatHistory = [];
            messagesEl.replaceChildren();
            inputEl.value = "";
            resizeComposer();

            contactName.textContent = conversation.name;
            contactAvatar.src = avatarFor(conversation);
            listView.classList.add("hidden");
            conversationView.classList.remove("hidden");

            if (conversation.startDate) createDateSeparator(conversation.startDate);
            setTimeout(() => continuePlayback(), 500);
        }

        function goBack() {
            playbackToken += 1;
            activeConversation = null;
            playbackRunning = false;
            awaitingVisitorResponse = false;
            generatedReplyInProgress = false;
            currentWaitItem = null;
            conversationMemory = Object.create(null);
            conversationView.classList.add("hidden");
            listView.classList.remove("hidden");
            modal.classList.add("hidden");
            renderConversationList();
        }

        function replayConversation() {
            if (!activeConversation) return;
            const id = activeConversation.id;
            modal.classList.add("hidden");
            openConversation(id);
        }

        sendButton.addEventListener("click", sendManualMessage);
        inputEl.addEventListener("input", resizeComposer);
        inputEl.addEventListener("keydown", (event) => {
            if (event.key === "Enter" && !event.shiftKey) {
                event.preventDefault();
                sendManualMessage();
            }
        });

        backButton.addEventListener("click", goBack);
        infoButton.addEventListener("click", () => modal.classList.remove("hidden"));
        closeModalButton.addEventListener("click", () => modal.classList.add("hidden"));
        modal.addEventListener("click", (event) => {
            if (event.target === modal) modal.classList.add("hidden");
        });
        replayButton.addEventListener("click", replayConversation);

        imageButton.addEventListener("click", () => imageUpload.click());
        imageUpload.addEventListener("change", async () => {
            const file = imageUpload.files?.[0];
            imageUpload.value = "";
            if (!file) return;
            try { await sendImage(file); } catch (error) { console.error(error); }
        });

        avatarUpload.addEventListener("change", async () => {
            const file = avatarUpload.files?.[0];
            avatarUpload.value = "";
            if (!file || !activeConversation || !file.type.startsWith("image/")) return;

            try {
                const source = await fileToDataUrl(file);
                safeStorageSet(`sololearn-avatar:${activeConversation.id}`, source);
                contactAvatar.src = source;
            } catch (error) {
                console.error("Avatar could not be loaded:", error);
            }
        });

        clearAvatarButton.addEventListener("click", () => {
            if (!activeConversation) return;
            safeStorageRemove(`sololearn-avatar:${activeConversation.id}`);
            contactAvatar.src = defaultAvatar(activeConversation.name, activeConversation.avatarHue);
            modal.classList.add("hidden");
        });

        document.addEventListener("click", async () => {
            const ctx = getAudioContext();
            if (ctx?.state === "suspended") {
                try { await ctx.resume(); } catch { /* Browser may still decline. */ }
            }
        }, { once: true });

        renderConversationList();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", bootTextMessageSimulator, { once: true });
    } else {
        bootTextMessageSimulator();
    }
})();
