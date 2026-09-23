/*
==================================================
    Card.js
==================================================
*/

export default class Card {

    constructor(options = {}) {

        this.title = options.title ?? "Card";

        this.icon = options.icon ?? "";

        this.collapsible = options.collapsible ?? false;

        this.collapsed = options.collapsed ?? false;

        this.badge = options.badge ?? "";

        this.element = null;

        this.content = null;

    }

    render(parent) {

        this.element = document.createElement("section");

        this.element.className = "card";

        const header = document.createElement("div");
        header.className = "card-header";

        const title = document.createElement("div");
        title.className = "card-title";

        title.innerHTML = `

            <span class="card-icon">

                ${this.icon}

            </span>

            <span>

                ${this.title}

            </span>

        `;

        header.appendChild(title);

        const actions = document.createElement("div");
        actions.className = "card-actions";

        if (this.badge !== "") {

            const badge = document.createElement("span");

            badge.className = "card-badge";

            badge.textContent = this.badge;

            actions.appendChild(badge);

        }

        if (this.collapsible) {

            const collapse = document.createElement("button");

            collapse.className = "collapse-btn";

            collapse.type = "button";

            collapse.textContent = this.collapsed ? "►" : "▼";

            collapse.addEventListener("click", () => {

                this.toggle();

            });

            actions.appendChild(collapse);

        }

        header.appendChild(actions);

        this.element.appendChild(header);

        this.content = document.createElement("div");

        this.content.className = "card-content";

        if (this.collapsed) {

            this.content.classList.add("collapsed");

        }

        this.element.appendChild(this.content);

        parent.appendChild(this.element);

        return this.content;

    }

    toggle() {

        this.collapsed = !this.collapsed;

        this.content.classList.toggle("collapsed");

        const button = this.element.querySelector(".collapse-btn");

        if (button) {

            button.textContent = this.collapsed ? "►" : "▼";

        }

    }

    setBadge(text) {

        let badge = this.element.querySelector(".card-badge");

        if (!badge) {

            badge = document.createElement("span");

            badge.className = "card-badge";

            this.element
                .querySelector(".card-actions")
                .prepend(badge);

        }

        badge.textContent = text;

    }

    clear() {

        this.content.innerHTML = "";

    }

    append(node) {

        this.content.appendChild(node);

    }

    html(html) {

        this.content.innerHTML = html;

    }

}
