/*
==================================================
    WindowsWidget.js
==================================================
*/

import Widget from "./Widget.js";

export default class WindowsWidget extends Widget {

    constructor(application) {

        super(

            application,

            "windows",

            "Windows",

            "🪟"

        );

    }

    async refresh() {

        this.setContent(`

            <div class="widget-loading">

                Loading Windows information...

            </div>

        `);

        try {

            const api =

                this.application.get(

                    "api"

                );

            const response =

                await api.get(

                    "windows.php"

                );

            const windows =

                response.windows ?? {};

            this.setContent(`

                <div class="windows-widget">

                    <div class="info-row">

                        <span>Edition</span>

                        <strong>

                            ${windows.edition ?? "-"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Version</span>

                        <strong>

                            ${windows.version ?? "-"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Build</span>

                        <strong>

                            ${windows.build ?? "-"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Install Date</span>

                        <strong>

                            ${windows.installDate ?? "-"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Last Boot</span>

                        <strong>

                            ${windows.lastBoot ?? "-"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Windows Update</span>

                        <strong class="${windows.upToDate ? "online" : "warning"}">

                            ${windows.upToDate ? "Current" : "Updates Available"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Activation</span>

                        <strong class="${windows.activated ? "online" : "offline"}">

                            ${windows.activated ? "Activated" : "Not Activated"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Pending Restart</span>

                        <strong>

                            ${windows.pendingRestart ? "Yes" : "No"}

                        </strong>

                    </div>

                </div>

            `);

        }

        catch (error) {

            console.error(error);

            this.setContent(`

                <div class="widget-error">

                    Failed to load Windows information.

                </div>

            `);

        }

    }

}
