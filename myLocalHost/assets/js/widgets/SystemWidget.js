/*
==================================================
    SystemWidget.js
==================================================
*/

import Widget from "../Widget.js";

export default class SystemWidget extends Widget {

    constructor() {

        super({

            title: "System Status",

            icon: "⚙"

        });

    }

    async render(grid, data) {

        this.create(grid);

        const services = data.dashboard.system ?? [];

        if (!services.length) {

            this.setError("No system information available.");

            return;

        }

        let html = '<div class="system-list">';

        services.forEach(service => {

            const online = service.status === "online";

            html += `

                <div class="system-item">

                    <div class="system-left">

                        <span class="system-dot ${online ? "online" : "offline"}"></span>

                        <div>

                            <strong>${service.name}</strong>

                            <small>${service.version || "Unknown Version"}</small>

                        </div>

                    </div>

                    <div class="system-right">

                        ${online ? "Online" : "Offline"}

                    </div>

                </div>

            `;

        });

        html += "</div>";

        this.setContent(html);

    }

}
