export class AdminData {
    constructor({ state = state, api = api }) {
        console.log("AdminData Module Loaded");

        this.state = state;
        this.api = api;
    }

    async reset_self() {
        let response = this.api.request({
            endpoint: 'reset_my_data',
        });

        return await response;
    }
}
