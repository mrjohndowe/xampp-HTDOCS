/*
==================================================
    ActionService.js
==================================================
*/

export default class ActionService {

    constructor(api) {

        this.api = api;

    }

    async execute(action, project) {

        try {

            const response = await this.api.action(

                action,

                project

            );

            console.log(response.message);

            return response;

        }
        catch (error) {

            console.error(error);

            alert(error.message);

            throw error;

        }

    }

    bind(container) {

        container
            .querySelectorAll("[data-action]")
            .forEach(button => {

                button.addEventListener("click", async () => {

                    button.disabled = true;

                    try {

                        await this.execute(

                            button.dataset.action,

                            button.dataset.project

                        );

                    }
                    finally {

                        button.disabled = false;

                    }

                });

            });

    }

}
