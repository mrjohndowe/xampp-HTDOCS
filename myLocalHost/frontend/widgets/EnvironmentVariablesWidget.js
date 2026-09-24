/*
==================================================
    EnvironmentVariablesWidget.js
==================================================
*/

import Widget from "./Widget.js";

export default class EnvironmentVariablesWidget extends Widget {

    constructor(application) {

        super(

            application,

            "environment-variables",

            "Environment Variables",

            "🌱"

        );

    }

    async refresh() {

        this.setContent(`

            <div class="widget-loading">

                Loading environment variables...

            </div>

        `);

        try {

            const api =

                this.application.get(

                    "api"

                );

            const response =

                await api.get(

                    "environment-variables.php"

                );

            const variables =

                response.variables ?? [];

            if (!variables.length) {

                this.setContent(`

                    <div class="widget-empty">

                        No environment variables found.

                    </div>

                `);

                return;

            }

            this.setContent(`

                <table class="widget-table">

                    <thead>

                        <tr>

                            <th>Variable</th>

                            <th>Value</th>

                        </tr>

                    </thead>

                    <tbody>

                        ${variables.map(variable => `

                            <tr>

                                <td>

                                    ${variable.name}

                                </td>

                                <td>

                                    <code>

                                        ${variable.value}

                                    </code>

                                </td>

                            </tr>

                        `).join("")}

                    </tbody>

                </table>

            `);

        }

        catch (error) {

            console.error(error);

            this.setContent(`

                <div class="widget-error">

                    Failed to load environment variables.

                </div>

            `);

        }

    }

}
