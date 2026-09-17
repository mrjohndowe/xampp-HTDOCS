(() => {
  "use strict";

  const appBasePath = String(window.APP_BASE_PATH || "").replace(/\/$/, "");
  const conversation = window.CONVERSATION_DATA || {};
  const emojiMap = window.EMOJI_MAP || {};
  const participants = conversation.participants || {};
  const messages = conversation.messages || [];
  const conversationId = String(conversation.id || "");

  const messagesEl = document.getElementById("messages");
  const inputEl = document.getElementById("message-input");
  const sendButton = document.getElementById("send-button");
  const avatarUpload = document.getElementById("avatar-upload");
  const contactAvatar = document.getElementById("contact-avatar");

  let playbackRunning = false;
  let awaitingVisitorResponse = false;
  let generatedReplyInProgress = false;
  const chatHistory = [];

  const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

  const audioContext = new (window.AudioContext || window.webkitAudioContext)();

  function playTone(frequency, duration = 0.04, volume = 0.025, type = "sine") {
    if (!audioContext) {
      return;
    }

    const oscillator = audioContext.createOscillator();
    const gain = audioContext.createGain();

    oscillator.type = type;
    oscillator.frequency.value = frequency;

    gain.gain.setValueAtTime(volume, audioContext.currentTime);
    gain.gain.exponentialRampToValueAtTime(
      0.0001,
      audioContext.currentTime + duration,
    );

    oscillator.connect(gain);
    gain.connect(audioContext.destination);

    oscillator.start();
    oscillator.stop(audioContext.currentTime + duration);
  }

  function playTypingSound() {
    playTone(180 + Math.random() * 80, 0.025, 0.012, "square");
  }

  function playBackspaceSound() {
    playTone(120, 0.035, 0.018, "square");
  }

  function playSendSound() {
    playTone(520, 0.06, 0.035, "sine");

    setTimeout(() => {
      playTone(720, 0.08, 0.025, "sine");
    }, 35);
  }

  function playReceiveSound() {
    playTone(700, 0.07, 0.035, "sine");

    setTimeout(() => {
      playTone(920, 0.1, 0.025, "sine");
    }, 55);
  }

  function replaceEmojiShortcodes(text) {
    let result = String(text ?? "");

    for (const [shortcode, emoji] of Object.entries(emojiMap)) {
      result = result.split(shortcode).join(emoji);
    }

    return result;
  }

  function appUrl(path) {
    const normalizedPath = String(path ?? "").replace(/\\/g, "/");

    if (/^(?:[a-z][a-z\d+.-]*:|\/\/)/i.test(normalizedPath)) {
      return normalizedPath;
    }

    if (
      appBasePath &&
      (normalizedPath === appBasePath ||
        normalizedPath.startsWith(`${appBasePath}/`))
    ) {
      return normalizedPath;
    }

    if (normalizedPath.startsWith("/")) {
      return `${appBasePath}${normalizedPath}`;
    }

    return `${appBasePath}/${normalizedPath}`;
  }

  function scrollToBottom(behavior = "smooth") {
    if (!messagesEl) {
      return;
    }

    requestAnimationFrame(() => {
      messagesEl.scrollTo({
        top: messagesEl.scrollHeight,
        behavior,
      });
    });
  }

  function getParticipant(id) {
    return (
      participants[id] || {
        id,
        name: id,
        avatar: appUrl("/assets/images/avatars/default-avatar.png"),
        side: "incoming",
      }
    );
  }

  function formatMessageTime(date) {
    return date.toLocaleTimeString([], {
      hour: "numeric",
      minute: "2-digit",
    });
  }

function createDateSeparator(datetime) {
  if (!messagesEl || !datetime) {
    return null;
  }

  const date = new Date(datetime.replace(" ", "T"));

  const labelText = date
    .toLocaleString("en-US", {
      month: "short",
      day: "numeric",
      hour: "numeric",
      minute: "2-digit",
      hour12: true,
    })
    .replace(",", "")
    .replace(" AM", "AM")
    .replace(" PM", "PM")
    .toUpperCase()
    .replace(/(\d{1,2}:\d{2}(?:AM|PM))$/, "AT $1");

  const separator = document.createElement("div");
  separator.className = "date-separator";

  const label = document.createElement("span");
  label.className = "date-separator-label";
  label.textContent = labelText;

  separator.appendChild(label);
  messagesEl.appendChild(separator);

  scrollToBottom();

  return separator;
}

  function createMessage(participant, text, timestamp = null) {
    if (!messagesEl) {
      return null;
    }

    const row = document.createElement("div");
    const displayTime = timestamp || formatMessageTime(new Date());

    row.className = `message-row ${participant.side}`;

    const column = document.createElement("div");
    column.className = "message-column";

    const bubble = document.createElement("div");
    bubble.className = "message-bubble";

    const messageText = document.createElement("span");
    messageText.className = "message-text";
    messageText.textContent = replaceEmojiShortcodes(text);

    const time = document.createElement("span");
    time.className = "message-timestamp";
    time.textContent = displayTime;

    bubble.appendChild(messageText);
    bubble.appendChild(time);
    column.appendChild(bubble);
    row.appendChild(column);

    messagesEl.appendChild(row);

    scrollToBottom();

    return row;
  }

  function createImageMessage(
    participant,
    image,
    timestamp = null,
    caption = "",
  ) {
    if (!messagesEl) {
      return null;
    }

    const row = document.createElement("div");
    const displayTime = timestamp || formatMessageTime(new Date());

    row.className = `message-row ${participant.side}`;

    const column = document.createElement("div");
    column.className = "message-column image-message-column";

    const bubble = document.createElement("div");
    bubble.className = "message-bubble image-message-bubble";

    const img = document.createElement("img");
    img.className = "message-image";
    img.src = appUrl(image);
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
    time.textContent = displayTime;

    bubble.appendChild(time);
    column.appendChild(bubble);
    row.appendChild(column);

    messagesEl.appendChild(row);

    img.addEventListener("load", () => {
      scrollToBottom();
    });

    scrollToBottom();

    return row;
  }

  function createTypingIndicator(participant) {
    if (!messagesEl) {
      return null;
    }

    const row = document.createElement("div");

    row.className = `message-row ${participant.side} typing-row`;

    row.innerHTML = `
      <div class="message-column">
        <div class="message-bubble typing-bubble">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    `;

    messagesEl.appendChild(row);

    scrollToBottom();

    return row;
  }

  function resizeComposer() {
    if (!inputEl) {
      return;
    }

    inputEl.style.height = "auto";
    inputEl.style.height = `${Math.min(inputEl.scrollHeight, 110)}px`;
  }

async function typeIntoComposer(
  text,
  speed = 55,
  pauseAt = {},
  mistakeChance = 0.06,
) {
  if (!inputEl) {
    return;
  }

  inputEl.value = "";
  inputEl.focus();

  resizeComposer();

  const characters = String(text ?? "");
  const typoCharacters = "abcdefghijklmnopqrstuvwxyz";

  for (let i = 0; i < characters.length; i++) {
    const correctCharacter = characters[i];

    const canMakeMistake =
      /[a-z]/i.test(correctCharacter) && Math.random() < mistakeChance;

    if (canMakeMistake) {
      let wrongCharacter =
        typoCharacters[Math.floor(Math.random() * typoCharacters.length)];

      if (correctCharacter === correctCharacter.toUpperCase()) {
        wrongCharacter = wrongCharacter.toUpperCase();
      }

      inputEl.value += wrongCharacter;

      playTypingSound();
      resizeComposer();

      await sleep(120 + Math.random() * 280);

      inputEl.value = inputEl.value.slice(0, -1);

      playBackspaceSound();
      resizeComposer();

      await sleep(80 + Math.random() * 180);
    }

    inputEl.value += correctCharacter;

    playTypingSound();

    resizeComposer();

    inputEl.scrollTop = inputEl.scrollHeight;

    const pauseKey = i + 1;

    if (pauseAt && Object.prototype.hasOwnProperty.call(pauseAt, pauseKey)) {
      await sleep(Number(pauseAt[pauseKey]) || 0);
    }

    const naturalVariation = Math.floor(Math.random() * 45);

    await sleep(Number(speed || 55) + naturalVariation);
  }
}

async function playIncoming(item, participant) {
  await sleep(Number(item.delay || 0));

  if (Number(item.typing || 0) > 0) {
    const typing = createTypingIndicator(participant);

    await sleep(Number(item.typing));

    if (typing) {
      typing.remove();
    }
  }

  playReceiveSound();

  if (item.image) {
    createImageMessage(
      participant,
      item.image,
      item.timestamp ?? null,
      item.caption ?? "",
    );

    return;
  }

  createMessage(participant, item.text || "", item.timestamp ?? null);
  chatHistory.push({ sender: item.sender, text: item.text || "" });
}

  async function playOutgoing(item, participant) {
    await sleep(Number(item.delay || 0));

    if (item.image) {
      await sleep(Number(item.sendDelay || 0));

      playSendSound();

      createImageMessage(
        participant,
        item.image,
        item.timestamp ?? null,
        item.caption ?? "",
      );

      return;
    }

    const text = String(item.text || "");

    if (item.compose) {
      await typeIntoComposer(
        text,
        Number(item.typingSpeed || 65),
        item.pauseAt || {},
        Number(item.mistakeChance ?? 0.06),
      );

      await sleep(Number(item.sendDelay || 0));

      if (inputEl) {
        inputEl.value = "";
        resizeComposer();
      }
    }

    playSendSound();

    createMessage(participant, text, item.timestamp ?? null);
    chatHistory.push({ sender: item.sender, text });
  }
async function playConversation() {
  if (playbackRunning) {
    return;
  }

  playbackRunning = true;

  try {
    if (conversation.start_date) {
      createDateSeparator(conversation.start_date);
    }

    for (const item of messages) {
      if (item.type === "date_separator") {
        createDateSeparator(item.datetime);
        continue;
      }

      if (item.type === "wait_for_response") {
        awaitingVisitorResponse = true;
        inputEl?.focus();
        break;
      }

      const participant = getParticipant(item.sender);

      if (participant.side === "incoming") {
        await playIncoming(item, participant);
      } else {
        await playOutgoing(item, participant);
      }
    }
  } catch (error) {
    console.error("Conversation playback error:", error);
  } finally {
    playbackRunning = false;
  }
}
  async function parseJsonResponse(response) {
    const responseText = await response.text();

    let result;

    try {
      result = JSON.parse(responseText);
    } catch (error) {
      console.error("Expected JSON but server returned:", responseText);

      throw new Error(`Server returned invalid JSON (${response.status})`);
    }

    return result;
  }

  async function sendManualMessage() {
    if (!inputEl) {
      return;
    }

    const rawValue = inputEl.value.trim();

    if (!rawValue) {
      return;
    }

    const value = replaceEmojiShortcodes(rawValue);
    const me = getParticipant("me");

    inputEl.value = "";
    resizeComposer();

    createMessage(me, value);
    chatHistory.push({ sender: "me", text: value });

    try {
      const response = await fetch(appUrl("/api/send-message.php"), {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          sender: "me",
          text: value,
        }),
      });

      const result = await parseJsonResponse(response);

      if (!response.ok) {
        throw new Error(result.error || `Request failed (${response.status})`);
      }

      console.log("Message saved:", result);

      if (awaitingVisitorResponse && !generatedReplyInProgress) {
        await requestGeneratedReply();
      }
    } catch (error) {
      console.error("Message persistence failed:", error);
    }
  }

  async function requestGeneratedReply() {
    generatedReplyInProgress = true;
    awaitingVisitorResponse = false;

    const other = getParticipant("other");
    const typing = createTypingIndicator(other);

    try {
      const response = await fetch(appUrl("/api/generate-reply.php"), {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          conversationId,
          history: chatHistory,
        }),
      });

      const result = await parseJsonResponse(response);

      if (!response.ok) {
        throw new Error(result.error || `Reply request failed (${response.status})`);
      }

      if (typing) {
        typing.remove();
      }

      const reply = String(result.reply || "").trim();

      if (reply) {
        playReceiveSound();
        createMessage(other, reply);
        chatHistory.push({ sender: "other", text: reply });
      }
    } catch (error) {
      if (typing) {
        typing.remove();
      }

      console.error("Generated reply failed:", error);
      awaitingVisitorResponse = true;
      alert("The reply could not be generated. Please try sending your message again.");
    } finally {
      generatedReplyInProgress = false;
    }
  }

  async function uploadAvatar(file) {
    if (!file) {
      return;
    }

    const formData = new FormData();

    formData.append("avatar", file);

    const response = await fetch(appUrl("/api/upload-avatar.php"), {
      method: "POST",
      headers: {
        Accept: "application/json",
      },
      body: formData,
    });

    const result = await parseJsonResponse(response);

    if (!response.ok) {
      throw new Error(result.error || "Avatar upload failed.");
    }

    const avatarUrl = appUrl(result.url);

    if (contactAvatar) {
      contactAvatar.src = avatarUrl;
    }

    if (participants.other) {
      participants.other.avatar = avatarUrl;
    }
  }

  if (sendButton) {
    sendButton.addEventListener("click", sendManualMessage);
  }

  if (inputEl) {
    inputEl.addEventListener("input", resizeComposer);

    inputEl.addEventListener("keydown", (event) => {
      if (event.key === "Enter" && !event.shiftKey) {
        event.preventDefault();
        sendManualMessage();
      }
    });
  }

  document.addEventListener(
    "click",
    () => {
      if (audioContext.state === "suspended") {
        audioContext.resume();
      }
    },
    { once: true },
  );

  if (avatarUpload) {
    avatarUpload.addEventListener("change", async (event) => {
      const file = event.target.files?.[0];

      if (!file) {
        return;
      }

      try {
        await uploadAvatar(file);
      } catch (error) {
        console.error("Avatar upload failed:", error);
        alert(error.message);
      } finally {
        avatarUpload.value = "";
      }
    });
  }

  window.addEventListener("load", () => {
    scrollToBottom("auto");

    setTimeout(() => {
      playConversation();
    }, 900);
  });
})();
