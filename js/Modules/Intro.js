export class Intro {
    constructor({ state, api }) {
        console.log("Main intro Module Loaded");

        this.state = state;
        this.api = api;
    }

    async main_intro_data() {
        let main_intro = this.api.request({
            endpoint: 'app_data/main_intro',
        });

        return await main_intro;
    }
}
