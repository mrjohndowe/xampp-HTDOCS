/*
==================================================
    EventLogWidget.js
==================================================
*/

import Widget from "./Widget.js";

export default class EventLogWidget extends Widget {

    constructor(application) {

        super(

            application,

            "event-log",

            "Windows Event Log",

            "📋"

        );

    }

    async refresh() {

        this.setContent(`

            <div class="widget-loading">

                Loading Windows Event Logs...

            </div>

        `);

        try {

            const api =

                this.application.get(

                    "api"

                );

            const response =

                await api.get(

                    "eventlog.php"

                );

            const events =

                response.events ?? [];

            if (!events.length) {

                this.setContent(`

                    <div class="widget-empty">

                        No recent events found.

                    </div>

                `);

                return;

            }

            this.setContent(`

                <table class="widget-table">

                    <thead>

                        <tr>

                            <th>Time</th>

                            <th>Level</th>

                            <th>Source</th>

                            <th>Message</th>

                        </tr>

                    </thead>

                    <tbody>

                        ${events.map(event => `

                            <tr>

                                <td>

                                    ${event.time}

                                </td>

                                <td>

                                    <span class="${event.level.toLowerCase()}">

                                        ${event.level}

                                    </span>

                                </td>

                                <td>

                                    ${event.source}

                                </td>

                                <td>

                                    ${event.message}

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

                    Failed to load Windows Event Logs.

                </div>

            `);

        }

    }

}
