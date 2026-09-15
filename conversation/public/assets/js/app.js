(() => {
  "use strict";


  const appBasePath = "/conversation";
  const conversation = window.CONVERSATION_DATA || {};
  const emojiMap = window.EMOJI_MAP || {};
  const participants = conversation.participants || {};
  const messages = conversation.messages || [];

  const messagesEl = document.getElementById("messages");
  const inputEl = document.getElementById("message-input");
  const sendButton = document.getElementById("send-button");
  const avatarUpload = document.getElementById("avatar-upload");
  const contactAvatar = document.getElementById("contact-avatar");

  let playbackRunning = false;

  const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

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

  function escapeHtml(value) {
    const div = document.createElement("div");

    div.textContent = String(value ?? "");

    return div.innerHTML;
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
        avatar: appUrl("/assets/images/default-avatar.png"),
        side: "incoming",
      }
    );
  }

  function buildAvatar(participant) {
    if (participant.side !== "incoming") {
      return "";
    }

    const avatar = participant.avatar || "/assets/images/default-avatar.png";

    return `
      <img
        class="message-avatar"
        src="${escapeHtml(appUrl(avatar))}"
        alt=""
      >
    `;
  }

  function createMessage(participant, text) {
    if (!messagesEl) {
      return null;
    }

    const row = document.createElement("div");

    row.className = `message-row ${participant.side}`;

    row.innerHTML = `
      ${buildAvatar(participant)}

      <div class="message-column">

        <div class="sender-name">
          ${escapeHtml(participant.name)}
        </div>

        <div class="message-bubble">
          ${escapeHtml(replaceEmojiShortcodes(text))}
        </div>

      </div>
    `;

    messagesEl.appendChild(row);

    scrollToBottom();

    return row;
  }

  function createTypingIndicator(participant) {
    if (!messagesEl) {
      return null;
    }

    const row = document.createElement("div");

    row.className = "message-row incoming typing-row";

    row.innerHTML = `
      ${buildAvatar(participant)}

      <div class="message-column">

        <div class="sender-name">
          ${escapeHtml(participant.name)}
        </div>

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

  async function typeIntoComposer(text, speed = 55, pauseAt = {}) {
    if (!inputEl) {
      return;
    }

    inputEl.value = "";
    inputEl.focus();

    resizeComposer();

    const characters = String(text ?? "");

    for (let i = 0; i < characters.length; i++) {
      inputEl.value += characters[i];

      resizeComposer();

      inputEl.scrollTop = inputEl.scrollHeight;

      const pauseKey = i + 1;

      if (pauseAt && Object.prototype.hasOwnProperty.call(pauseAt, pauseKey)) {
        await sleep(Number(pauseAt[pauseKey]) || 0);
      }

      const naturalVariation = Math.floor(Math.random() * 35);

      await sleep(Number(speed || 55) + naturalVariation);
    }
  }

  async function playIncoming(item) {
    const participant = getParticipant(item.sender);

    await sleep(Number(item.delay ?? 1000));

    let typingRow = null;

    const typingDuration = Number(item.typing ?? 0);

    if (typingDuration > 0) {
      typingRow = createTypingIndicator(participant);

      await sleep(typingDuration);

      if (typingRow) {
        typingRow.remove();
      }

      scrollToBottom();
    }

    createMessage(participant, item.text);
  }

  async function playOutgoing(item) {
    const participant = getParticipant(item.sender);

    await sleep(Number(item.delay ?? 1000));

    if (item.compose) {
      await typeIntoComposer(
        item.text,
        Number(item.typingSpeed ?? 55),
        item.pauseAt ?? {},
      );

      if (inputEl) {
        inputEl.value = replaceEmojiShortcodes(inputEl.value);

        resizeComposer();
      }

      await sleep(Number(item.sendDelay ?? 700));

      if (inputEl) {
        inputEl.value = "";
        resizeComposer();
      }
    }

    createMessage(participant, item.text);
  }

  async function playConversation() {
    if (playbackRunning) {
      return;
    }

    playbackRunning = true;

    try {
      for (const item of messages) {
        const participant = getParticipant(item.sender);

        if (participant.side === "incoming") {
          await playIncoming(item);
        } else {
          await playOutgoing(item);
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
    } catch (error) {
      console.error("Message persistence failed:", error);
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

    document
      .querySelectorAll(".message-row.incoming .message-avatar")
      .forEach((img) => {
        img.src = avatarUrl;
      });
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
