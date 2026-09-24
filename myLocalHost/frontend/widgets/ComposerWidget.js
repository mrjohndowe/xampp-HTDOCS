/*
==================================================
    ComposerWidget.js
==================================================
*/

import Widget from "./Widget.js";

export default class ComposerWidget extends Widget {

    constructor(application) {

        super(

            application,

            "composer",

            "Composer",

            "🎼"

        );

    }

    async refresh() {

        this.setContent(`

            <div class="widget-loading">

                Loading Composer...

            </div>

        `);

        try {

            const api =

                this.application.get(

                    "api"

                );

            const response =

                await api.get(

                    "composer.php"

                );

            const composer =

                response.composer ?? {};

            const packages =

                composer.packages ?? [];

            this.setContent(`

                <div class="composer-widget">

                    <div class="info-row">

                        <span>Status</span>

                        <strong class="${composer.installed ? "online" : "offline"}">

                            ${composer.installed ? "Installed" : "Not Installed"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Version</span>

                        <strong>

                            ${composer.version ?? "-"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>PHP</span>

                        <strong>

                            ${composer.php ?? "-"}

                        </strong>

                    </div>

                    <div class="info-row">

                        <span>Packages</span>

                        <strong>

                            ${packages.length}

                        </strong>

                    </div>

                    <div class="composer-packages">

                        ${packages.slice(0,10).map(pkg => `

                            <div class="composer-package">

                                <span>

                                    ${pkg.name}

                                </span>

                                <small>

                                    ${pkg.version}

                                </small>

                            </div>

                        `).join("")}

                    </div>

                </div>

            `);

        }

        catch (error) {

            console.error(error);

            this.setContent(`

                <div class="widget-error">

                    Failed to load Composer information.

                </div>

            `);

        }

    }

}
