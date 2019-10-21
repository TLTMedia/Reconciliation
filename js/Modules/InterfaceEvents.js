export class InterfaceEvents {
    constructor({ state, student_data, patient_data, modal, ui }) {
        console.log("InterfaceEvents Module Loaded");

        this.state = state;
        this.student_data = student_data;
        this.patient_data = patient_data;
        this.modal = modal;
        this.ui = ui;
    }

    submit_patient_evaluation() {
        const groupSet = new Set();
        let status = "";
        let report = "";

        let i;
        let medication;
        for (i in this.state['patients'][this.state.current_patient].medications) {
            medication = this.state['patients'][this.state.current_patient].medications[i];
            groupSet.add(medication.group);

            if (!medication.pickedOption) {
                status += `You need to set a course of action for ${medication.Medication},  ${medication.Source} <br/>`;
            }
        }

        this.state['patients'][this.state.current_patient].groupCount = groupSet.size
        if (status == "") {
            //  status = report
            status = response();
        }

        this.modal.show_modal({
            content: status,
            button_text: "Accept",
            title: "Results",
            show_cancel: false,
        });
        this.modal.set_main_action_close_modal();
    }

    load_patient_intro(event) {
        /**
         * TODO: now after class Modal
         */
        let patient_id = event.target.id.split("_")[1];
        location.hash = parseInt(patient_id);

        /**
         * Show patient introduction modal & reset the styling
         */
        this.modal.reset_attempt_styling();
        this.modal.show_modal({
            content: this.state['patients'][patient_id].info.Intro,
            button_text: "Begin",
            title: "Patient Introduction",
        });

        if (this.state.submitted.hasOwnProperty("patient_" + patient_id)) {
            let attempts = parseInt(this.state.submitted["patient_" + patient_id]);
            if (attempts == 2) {
                this.modal.user_max_attempts();
            }

            this.modal.set_current_attempt(attempts);
        } else {
            this.modal.set_current_attempt("0");
        }

        this.modal.set_main_action_show_patient(patient_id);
    }

    bind_window_hash_change() {
        /**
         * When no hash is present and the back button is pressed; fully refresh the page.	
         */
        window.onhashchange = () => {
            if (!location.hash) {
                window.location.href = window.location.href;
            }
        };
    }
}