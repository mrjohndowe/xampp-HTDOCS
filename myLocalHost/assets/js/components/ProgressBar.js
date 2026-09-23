/*
==================================================
    ProgressBar.js
==================================================
*/

export default class ProgressBar {

    constructor(value = 0, options = {}) {

        this.value = Math.max(0, Math.min(100, value));

        this.label = options.label ?? "";

        this.color = options.color ?? "var(--primary)";

    }

    render() {

        const wrapper = document.createElement("div");

        wrapper.className = "progress-widget";

        wrapper.innerHTML = `

            <div class="progress-header">

                <span>

                    ${this.label}

                </span>

                <span>

                    ${this.value}%

                </span>

            </div>

            <div class="progress">

                <div
                    class="progress-fill"
                    style="
                        width:${this.value}%;
                        background:${this.color};
                    "
                ></div>

            </div>

        `;

        return wrapper;

    }

}
