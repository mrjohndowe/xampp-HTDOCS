/*
==================================================
    Widget.js
    Base Widget Class
==================================================
*/

import Card from "./components/Card.js";

export default class Widget {

    constructor(options = {}) {

        this.title = options.title ?? "Widget";

        this.icon = options.icon ?? "";

        this.collapsible = options.collapsible ?? false;

        this.collapsed = options.collapsed ?? false;

        this.card = null;

        this.content = null;

    }

    create(grid) {

        this.card = new Card({

            title: this.title,

            icon: this.icon,

            collapsible: this.collapsible,

            collapsed: this.collapsed

        });

        this.content = this.card.render(grid);

    }

    setLoading() {

        this.content.innerHTML = `

            <div class="widget-loading">

                <div class="spinner"></div>

                <span>Loading...</span>

            </div>

        `;

    }

    setError(message) {

        this.content.innerHTML = `

            <div class="widget-error">

                ⚠

                ${message}

            </div>

        `;

    }

    setContent(html) {

        this.content.innerHTML = html;

    }

    async render(grid, data) {

        throw new Error(

            `${this.constructor.name} must implement render()`

        );

    }

}
