export class ChartsData {
    constructor({ state, api }) {
        console.log("ChartsData Module Loaded");

        this.state = state;
        this.api = api;
    }

    async get_master_data() {
        let master_data = this.api.request({
            endpoint: 'generate_master_doc',
            data: {
                download: "false",
            },
        });

        return await master_data;
    }
}
