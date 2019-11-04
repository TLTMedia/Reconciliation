export class InterfaceEvents {
    constructor({ state, student_data, patient_data, modal, ui }) {
        console.log("InterfaceEvents Module Loaded");

        this.state = state;
        this.student_data = student_data;
        this.patient_data = patient_data;
        this.modal = modal;
        this.ui = ui;
    }

    async submit_patient_evaluation() {
        const groupSet = new Set();
        let status = "";

        let i;
        let medication;
        for (i in this.state['patients'][this.state.current_patient].medications) {
            medication = this.state['patients'][this.state.current_patient].medications[i];
            groupSet.add(medication.group);

            if (!medication.pickedOption) {
                status += `You need to set a course of action for ${medication.Medication},  ${medication.Source} <br/>`;
            }
        }

        this.state['patients'][this.state.current_patient].groupCount = groupSet.size;

        /**
         * No missing data; so calculate the score
         */
        if (status == "") {
            /**
             * Increment the trial # (frontend) and set it in the modal
             */
            let attempts;
            if (!this.state.submitted.hasOwnProperty("patient_" + this.state.current_patient)) {
                attempts = 1; // b/c before it was 0 (null), but we just submitted
            } else {
                attempts = parseInt(this.state.submitted["patient_" + this.state.current_patient]) + 1;
            }

            /**
             * Don't submit attempt if >= 2
             */
            if (attempts > 2) {
                status = "Maximum submissions reached.";
            } else {
                /**
                 * Submit the attempt
                 */
                status = await this.student_data.submit_attempt();

                /**
                 * Update state and modal representation of attempt count
                 */
                this.state.submitted["patient_" + this.state.current_patient] = attempts;
                this.modal.set_current_attempt(attempts);
            }
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

        /**
         * Show patient introduction modal & reset the styling
         */
        this.modal.reset_attempt_styling();
        this.modal.show_modal({
            content: this.state['patients'][patient_id].info.Intro,
            button_text: "Begin",
            title: "Patient Introduction",
        });

        let attempts;
        if (this.state.submitted.hasOwnProperty("patient_" + patient_id)) {
            attempts = parseInt(this.state.submitted["patient_" + patient_id]);
            this.modal.set_current_attempt(attempts);
        } else {
            this.modal.set_current_attempt("0");
        }

        if (attempts >= 2) {
            $("#modal-main-action").off().on("click", () => {
                this.stop_begin_max_attempts();
            });
        } else {
            this.modal.set_main_action_show_patient(patient_id);
        }

        /**
         * Make the cancel button reset the location.hash - but only when viewing patient intros
         */
        this.modal.set_secondary_action_close_modal_reset_hash();
    }

    stop_begin_max_attempts() {
        this.ui.toast.create_toast("You have already attempted the maximum amount of times.", "Error");
    }

    check_window_hash_start() {
        if (location.hash) {
            $(`#patientList a:nth-child(${parseInt(location.hash.split("#")[1]) + 1})`).trigger("click");
        }
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

    bind_radio_choice_click(event) {
        let child = event.currentTarget;
        let med_id = $(child).attr("name").split("_")[1];

        $(`select[name='med_modify_${med_id}']`)[0].selectedIndex = 0;
        this.state['patients'][this.state.current_patient].medications[med_id].pickedOption = true;

        let parent = event.currentTarget.parentElement;
        $(parent).attr("data-action", $(child).data("action"));
        $(parent).attr("data-medAmount", $(child).val());

        let class_list = $(parent).attr('class').split(/\s+/);
        let current_group;
        $.each(class_list, (_, group) => {
            if (group.indexOf("group_") != -1) {
                current_group = group;
                if (!this.state.current_attempt.hasOwnProperty(group)) {
                    this.state.current_attempt[group] = {};
                }
            }
        });

        let med_amt = $(parent).attr("data-medAmount");
        let med_action = $(parent).attr("data-action");
        $.each(class_list, (_, item) => {
            if (item.indexOf("med_") != -1) {
                /**
                 * Encoding fix
                 */
                let [, encoding] = item.split("_");
                console.log(encoding);
                encoding = atob(encoding);
                console.log(encoding);

                let [med, source] = encoding.split("_");

                this.state.current_attempt[current_group][med + "_" + source] = {
                    amt: med_amt,
                    action: med_action,
                };
            }
        });
    }

    bind_select_choice_click(event) {
        let child = event.currentTarget;
        let med_id = $(child).attr("name").split("_")[2];

        if ($(child)[0].selectedIndex) {
            this.state['patients'][this.state.current_patient].medications[med_id].pickedOption = true;
            $(child).parent().attr("data-medAmount", $(child).val());

            $(`input[name='med_${med_id}']`).prop('checked', false);
        }

        let parent = event.currentTarget.parentElement;

        let class_list = $(parent).attr('class').split(/\s+/);
        let current_group;
        $.each(class_list, (_, group) => {
            if (group.indexOf("group_") != -1) {
                current_group = group;
                if (!this.state.current_attempt.hasOwnProperty(group)) {
                    this.state.current_attempt[group] = {};
                }
            }
        });

        let med_amt = $(parent).attr("data-medAmount");
        let med_action = "modify";
        $.each(class_list, (_, item) => {
            if (item.indexOf("med_") != -1) {
                /**
                 * Encoding fix
                 */
                let [, encoding] = item.split("_");
                console.log(encoding);
                encoding = atob(encoding);
                console.log(encoding);

                let [med, source] = encoding.split("_");

                this.state.current_attempt[current_group][med + "_" + source] = {
                    amt: med_amt,
                    action: med_action,
                };
            }
        });
    }
}
