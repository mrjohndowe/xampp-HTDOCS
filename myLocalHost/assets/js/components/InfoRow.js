/*
==================================================
    InfoRow.js
==================================================
*/

export default class InfoRow {

    constructor(label, value) {

        this.label = label;

        this.value = value;

    }

    render() {

        const row = document.createElement("div");

        row.className = "info-row";

        row.innerHTML = `

            <span>

                ${this.label}

            </span>

            <strong>

                ${this.value}

            </strong>

        `;

        return row;

    }

}
