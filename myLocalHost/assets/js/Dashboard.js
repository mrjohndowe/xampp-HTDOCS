/*
==================================================
    Dashboard.js
==================================================
*/

import Api from "./services/Api.js";
import ActionService from "./services/ActionService.js";

import GitWidget from "./widgets/GitWidget.js";
import MachineWidget from "./widgets/MachineWidget.js";
import SystemWidget from "./widgets/SystemWidget.js";

export default class Dashboard {

    constructor() {

        this.api = new Api();

        this.actions = new ActionService(this.api);

        this.grid = document.getElementById("dashboardGrid");

        this.search = document.getElementById("search");

        this.widgets = [];

        this.state = {
            dashboard: null,
            git: null,
            system: null
        };

        this.refreshInterval = null;

    }

    async initialize() {

        this.registerWidgets();

        this.registerEvents();

        await this.load(true);

        this.startRefreshTimer();

    }

    registerWidgets() {

        this.widgets = [

            new GitWidget(),

            new MachineWidget(),

            new SystemWidget()

        ];

    }

    registerEvents() {

        document.addEventListener("keydown", (event) => {

            if (event.ctrlKey && event.key.toLowerCase() === "k") {

                event.preventDefault();

                this.search.focus();

                this.search.select();

            }

            if (event.key === "F5") {

                event.preventDefault();

                this.refresh();

            }

        });

        this.search.addEventListener("input", () => {

            this.filterWidgets(this.search.value);

        });

    }

   async load(firstLoad = false) {

    try {

        this.state.dashboard = await this.api.dashboard();

        this.state.git = await this.api.git();

        this.state.system = await this.api.system();

        if (firstLoad) {

            await this.render();

        }
        else {

            await this.update();

        }

    }
    catch (error) {

        console.error(error);

        this.renderFatalError(error);

    }

}

    async render() {

        for (const widget of this.widgets) {

            try {

                await widget.render(

                    this.grid,

                    this.state,

                    this.api,

                    this.actions

                );

            }
            catch (error) {

                console.error(

                    widget.constructor.name,

                    error

                );

            }

        }

    }

    clear() {

        this.grid.innerHTML = "";

    }

    async refresh() {

        await this.load(false);

    }

    async update() {

        for (const widget of this.widgets) {

            if (typeof widget.update === "function") {

                try {

                    await widget.update(this.state);

                }
                catch (error) {

                    console.error(error);

                }

            }

        }

    }

    startRefreshTimer() {

        clearInterval(this.refreshInterval);

        this.refreshInterval = setInterval(() => {

            this.refresh();

        }, 30000);

    }

    stopRefreshTimer() {

        clearInterval(this.refreshInterval);

    }

    filterWidgets(search) {

        search = search.trim().toLowerCase();

        const cards = this.grid.querySelectorAll(".card");

        cards.forEach(card => {

            if (!search.length) {

                card.style.display = "";

                return;

            }

            card.style.display =

                card.innerText
                    .toLowerCase()
                    .includes(search)

                    ? ""

                    : "none";

        });

    }

    renderFatalError(error) {

        const card = document.createElement("section");

        card.className = "card";

        card.innerHTML = `

            <div class="card-header">

                <h2>

                    Dashboard Error

                </h2>

            </div>

            <div class="card-content">

                <pre>${error.stack}</pre>

            </div>

        `;

        this.grid.appendChild(card);

    }

}
