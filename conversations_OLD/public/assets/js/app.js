const messagesEl = document.querySelector("#messages");
const composerInput = document.querySelector("#message-input");
const sendButton = document.querySelector("#send-button");

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function scrollToBottom() {
  messagesEl.scrollTo({
    top: messagesEl.scrollHeight,
    behavior: "smooth",
  });
}

function createTypingIndicator(person) {
  const row = document.createElement("div");
  row.className = "message-row incoming typing-row";

  row.innerHTML = `
        <img class="message-avatar" src="${person.avatar}" alt="">
        <div>
            <div class="sender-name">${person.name}</div>
            <div class="bubble typing-bubble">
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

function addMessage(person, text) {
  const row = document.createElement("div");

  row.className = `message-row ${person.side}`;

  row.innerHTML = `
        ${
          person.side === "incoming"
            ? `<img class="message-avatar"
                    src="${person.avatar}"
                    alt="">`
            : ""
        }

        <div class="message-content">
            <div class="sender-name">
                ${person.name}
            </div>

            <div class="bubble">
                ${escapeHtml(text)}
            </div>
        </div>
    `;

  messagesEl.appendChild(row);
  scrollToBottom();
}

async function typeIntoComposer(text, speed = 55) {
  composerInput.value = "";

  for (const char of text) {
    composerInput.value += char;

    composerInput.scrollLeft = composerInput.scrollWidth;

    await sleep(speed);
  }
}

function escapeHtml(value) {
  const div = document.createElement("div");
  div.textContent = value;
  return div.innerHTML;
}

async function playConversation(script) {
  for (const item of script.messages) {
    const person = script.participants[item.sender];

    await sleep(item.delay ?? 1000);

    if (item.sender !== "me") {
      const typing = createTypingIndicator(person);

      await sleep(item.typing ?? 1500);

      typing.remove();

      addMessage(person, item.text);
      continue;
    }

    if (item.compose) {
      composerInput.focus();

      await typeIntoComposer(item.text, item.typingSpeed ?? 50);

      await sleep(item.sendDelay ?? 700);

      composerInput.value = "";
    }

    addMessage(person, item.text);
  }
}
