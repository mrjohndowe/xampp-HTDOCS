/*
==================================================
    Api.js
==================================================
*/

export default class Api {

    constructor() {

        this.baseUrl = "api/";

    }

    async request(endpoint, options = {}) {

        const response = await fetch(
            this.baseUrl + endpoint,
            {
                headers: {
                    "Accept": "application/json"
                },
                ...options
            }
        );

        if (!response.ok) {

            throw new Error(

                `${endpoint} returned HTTP ${response.status}`

            );

        }

        const text = await response.text();

        let json;

        try {

            json = JSON.parse(text);

        }
        catch {

            console.error(text);

            throw new Error(

                `${endpoint} returned invalid JSON.`

            );

        }

        if (json.success === false) {

            throw new Error(

                json.message ?? `${endpoint} failed.`

            );

        }

        return json;

    }

    dashboard() {

        return this.request("dashboard.php");

    }

    git() {

        return this.request("git.php");

    }

    system() {

        return this.request("system.php");

    }

    databases() {

        return this.request("databases.php");

    }

    sites() {

        return this.request("sites.php");

    }

    activity() {

        return this.request("activity.php");

    }

    search(query) {

        return this.request(

            `search.php?q=${encodeURIComponent(query)}`

        );

    }

    action(action, project) {

        return this.post(

            "actions.php",

            {

                action,

                project

            }

        );

    }

    post(endpoint, data = {}) {

        return this.request(endpoint, {

            method: "POST",

            headers: {

                "Accept": "application/json",

                "Content-Type": "application/json"

            },

            body: JSON.stringify(data)

        });

    }

}
