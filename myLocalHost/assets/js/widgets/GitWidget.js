/*
==================================================
    GitWidget.js
==================================================
*/

import Widget from "../Widget.js";

export default class GitWidget extends Widget {

    constructor() {

        super({

            title: "Git Repositories",

            icon: "🌿"

        });

    }

    async render(grid, data, api, actions) {

        this.create(grid);

        const repositories = data.git.repositories ?? [];

        if (repositories.length === 0) {

            this.setContent(`

                <p>

                    No Git repositories found.

                </p>

            `);

            return;

        }

        let html = "<div class='repo-list'>";

        repositories.forEach(repo => {

            const status = repo.clean
                ? "🟢 Clean"
                : `🟠 ${repo.modified} Modified`;

            const branch = repo.branch || "Unknown";

            html += `

                <div class="repo">

                    <div class="repo-header">

                        <div>

                            <h3>

                                📁 ${repo.name}

                            </h3>

                            <small>

                                🌿 ${branch}

                            </small>

                        </div>

                        <span class="repo-status">

                            ${status}

                        </span>

                    </div>

                    <div class="repo-details">

                        <div>

                            ↑ ${repo.ahead}

                        </div>

                        <div>

                            ↓ ${repo.behind}

                        </div>

                    </div>

                    <div class="repo-footer">

                        <div>

                            👤 ${repo.lastAuthor || "Unknown"}

                        </div>

                        <div>

                            🕒 ${repo.lastCommit || "Unknown"}

                        </div>

                    </div>

                    <div class="repo-actions">

                        <button
                            class="repo-btn"
                            data-action="explorer"
                            data-project="${repo.name}"
                        >
                            📂 Explorer
                        </button>

                        <button
                            class="repo-btn"
                            data-action="vscode"
                            data-project="${repo.name}"
                        >
                            💻 VS Code
                        </button>

                        <button
                            class="repo-btn"
                            data-action="terminal"
                            data-project="${repo.name}"
                        >
                            🖥 Terminal
                        </button>

                        <button
                            class="repo-btn"
                            data-action="website"
                            data-project="${repo.name}"
                        >
                            🌐 Website
                        </button>

                    </div>

                </div>

            `;

        });

        html += "</div>";

        this.setContent(html);

        if (actions) {

            actions.bind(this.content);

        }

    }



}
