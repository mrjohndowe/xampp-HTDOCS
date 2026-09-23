/*
==================================================
    myLocalHost Dashboard
==================================================
*/

import Dashboard from "./Dashboard.js";

class Application {

    constructor() {

        this.dashboard = null;

    }

    async start() {

        try {

            this.dashboard = new Dashboard();

            await this.dashboard.initialize();

            console.log("myLocalHost Dashboard Started");

        }
        catch (error) {

            console.error(error);

            document.body.innerHTML = `

                <div style="
                    display:flex;
                    justify-content:center;
                    align-items:center;
                    height:100vh;
                    background:#0d1117;
                    color:#fff;
                    font-family:Segoe UI;
                ">

                    <div style="
                        width:700px;
                        background:#161b22;
                        border:1px solid #30363d;
                        border-radius:14px;
                        padding:30px;
                    ">

                        <h1 style="margin-bottom:20px;">

                            Dashboard Failed To Start

                        </h1>

                        <pre style="
                            white-space:pre-wrap;
                            color:#ff6b6b;
                        ">${error.stack}</pre>

                    </div>

                </div>

            `;

        }

    }

}

window.addEventListener("DOMContentLoaded", async () => {

    const app = new Application();

    await app.start();

});
