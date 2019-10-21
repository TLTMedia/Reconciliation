export class StudentData {
    constructor({ state, api }) {
        console.log("StudentData Module Loaded");

        this.state = state;
        this.api = api;
    }

    async student_init() {
        let student_init = this.api.request({
            endpoint: 'student_init',
        });

        return await student_init;
    }

    async submitted_trials_data() {
        let trials_data = this.api.request({
            endpoint: 'submitted_trials_data',
        });

        return await trials_data;
    }
}
