/*
==================================================
    MachineWidget.js
==================================================
*/

import Widget from "../Widget.js";

import ProgressBar from "../components/ProgressBar.js";

import InfoRow from "../components/InfoRow.js";

export default class MachineWidget extends Widget {

    constructor() {

        super({

            title: "Machine",

            icon: "🖥"

        });

    }

    async render(grid, data) {

        this.create(grid);

        const system = data.system;

        const disk = system.disk.percent ?? 0;

        this.content.innerHTML = "";

        const title = document.createElement("h3");

        title.textContent = system.hostname;

        this.content.appendChild(title);

        const server = document.createElement("p");

        server.style.marginBottom = "20px";

        server.style.color = "var(--muted)";

        server.textContent = system.server;

        this.content.appendChild(server);

        this.content.appendChild(

            new ProgressBar(

                disk,

                {

                    label: "Disk Usage"

                }

            ).render()

        );

        this.content.appendChild(

            new InfoRow(

                "PHP",

                system.php.version

            ).render()

        );

        this.content.appendChild(

            new InfoRow(

                "Memory Limit",

                system.memory.limit

            ).render()

        );

        this.content.appendChild(

            new InfoRow(

                "Uptime",

                system.uptime

            ).render()

        );

    }

}
