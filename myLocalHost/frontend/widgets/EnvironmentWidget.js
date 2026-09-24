/*
==================================================
    EnvironmentWidget.js
==================================================
*/

import Widget from "./Widget.js";

export default class EnvironmentWidget extends Widget {

    constructor(application) {

        super(

            application,

            "environment",

            "Environment",

            "🌍"

        );

    }

    async refresh() {

        this.setContent(`

            <div class="widget-loading">

                Loading environment...

            </div>

        `);

        try {

            const api =

                this.application.get(

                    "api"

                );

            const response =

                await api.get(

                    "environment.php"

                );

            const environment =

                response.environment ?? {};

            this.setContent(`

                <div class="environment-widget">

                    <div class="info-row">

                        <span>Mode</span>

                        <strong>

                            ${environment.mode ?? "-"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Hostname</span>

                        <strong>

                            ${environment.hostname ?? "-"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>IP Address</span>

                        <strong>

                            ${environment.ip ?? "-"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Domain</span>

                        <strong>

                            ${environment.domain ?? "-"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>HTTPS</span>

                        <strong class="${environment.https ? "online" : "offline"}">

                            ${environment.https ? "Enabled" : "Disabled"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Timezone</span>

                        <strong>

                            ${environment.timezone ?? "-"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Locale</span>

                        <strong>

                            ${environment.locale ?? "-"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Environment</span>

                        <strong>

                            ${environment.name ?? "-"}

                        </strong>

                    </div>

                </div>

            `);

        }

        catch (error) {

            console.error(error);

            this.setContent(`

                <div class="widget-error">

                    Failed to load environment information.

                </div>

            `);

        }

    }

}
