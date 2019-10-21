export class PatientData {
    constructor({ state, api }) {
        console.log("PatientData Module Loaded");

        this.state = state;
        this.api = api;
    }

    async all_patients_data() {
        let student_init = this.api.request({
            endpoint: 'all_patients_data',
        });

        return await student_init;
    }
}
