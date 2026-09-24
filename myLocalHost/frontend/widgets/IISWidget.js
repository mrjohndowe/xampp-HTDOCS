/*
==================================================
    IISWidget.js
==================================================
*/

import Widget from "./Widget.js";

export default class IISWidget extends Widget {

    constructor(application) {

        super(

            application,

            "iis",

            "IIS",

            "🌐"

        );

    }

    async refresh() {

        this.setContent(`

            <div class="widget-loading">

                Checking IIS...

            </div>

        `);

        try {

            const api =

                this.application.get(

                    "api"

                );

            const response =

                await api.get(

                    "iis.php"

                );

            const iis =

                response.iis ?? {};

            this.setContent(`

                <div class="iis-widget">

                    <div class="info-row">

                        <span>Status</span>

                        <strong class="${iis.running ? "online" : "offline"}">

                            ${iis.running ? "Running" : "Stopped"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Version</span>

                        <strong>

                            ${iis.version ?? "-"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Sites</span>

                        <strong>

                            ${iis.sites ?? 0}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Application Pools</span>

                        <strong>

                            ${iis.applicationPools ?? 0}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Bindings</span>

                        <strong>

                            ${iis.bindings ?? 0}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Uptime</span>

                        <strong>

                            ${iis.uptime ?? "-"}

                        </strong>

                    </div>

                    <div class="widget-actions">

                        <button

                            id="restartIIS"

                            class="primary">

                            Restart IIS

                        </button>

                    </div>

                </div>

            `);

            this.body
                .querySelector("#restartIIS")
                ?.addEventListener(

                    "click",

                    async () => {

                        await api.action(

                            "restart-iis"

                        );

                        await this.refresh();

                    }

                );

        }

        catch (error) {

            console.error(error);

            this.setContent(`

                <div class="widget-error">

                    Failed to load IIS information.

                </div>

            `);

        }

    }

}
