export class InterfaceEvents {
    constructor({ state, student_data, patient_data, admin_data, modal, ui }) {
        console.log("InterfaceEvents Module Loaded");

        this.state = state;
        this.student_data = student_data;
        this.patient_data = patient_data;
        this.admin_data = admin_data;
        this.modal = modal;
        this.ui = ui;
    }

    async submit_patient_evaluation() {
        const groupSet = new Set();
        let status = "";
        let force_home = false;

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
         * Show patient introduction modal & reset the styling
         */
        let underscore_name = this.state.patients[this.state.current_patient].info.Name.replace(" ", "_");
        let condition = "Worse";

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
                let response = await this.student_data.submit_attempt();
                if (attempts == 1 && response.message != "") {
                    /**
                     * Don't show the results if its their first submission
                     */
                    status = "Try again!";
                } else if (response.message == "") {
                    /**
                     * If the server doesn't return a message, it means it was correct.
                     */
                    status = "Success!";
                    condition = "Better";
                    force_home = true;
                } else {
                    /**
                     * Its their last attempt, show them their results and change color of incorrect rows
                     */
                    status = response.message;
                    this.show_wrong_groups(response.incorrect);
                }

                /**
                 * Update state and modal representation of attempt count
                 */
                this.state.submitted["patient_" + this.state.current_patient] = attempts;
                this.modal.set_current_attempt(attempts);
            }
        }

        let patient_image = `images/${underscore_name}_${condition}.png`;
        this.modal.show_modal({
            content: status,
            image: patient_image,
            button_text: "Accept",
            title: "Results",
            show_cancel: false,
        });

        if (force_home) {
            this.modal.set_main_action_go_home();
        } else {
            this.modal.set_main_action_close_modal();
        }
    }

    load_patient_intro(event) {
        /**
         * TODO: now after class Modal
         */
        let patient_id = event.target.id.split("_")[1];

        /**
         * Get number attempts submitted already
         * &
         * Get the patient condition ("Base", "Worse", "Better") based on amountt of attempts
         */
        let attempts;
        let condition = "Base";
        if (this.state.submitted.hasOwnProperty("patient_" + patient_id)) {
            attempts = parseInt(this.state.submitted["patient_" + patient_id]);
            if (attempts == 1) {
                /**
                 * NOTE:
                 * If here, the user already submitted once,
                 * currently we only show the image of the person again if they submitted incorrectly.
                 * No image is shown if two incorrect attempts or if first attempt was successful.
                 * Hence why I can just do this and be done with it.
                 * Must add additional conditions if we end up showing the pictures of the people even after 
                 * submitting successfully the first time, 
                 * and if showing pic of person after having failed (submitting incorrectly twice)
                 */
                condition = "Worse";
            }
        }

        /**
         * Show patient introduction modal & reset the styling
         */
        let underscore_name = this.state.patients[patient_id].info.Name.replace(" ", "_");
        let patient_image = `images/${underscore_name}_${condition}.png`;

        this.modal.reset_attempt_styling();
        this.modal.show_modal({
            content: this.state['patients'][patient_id].info.Intro,
            image: patient_image,
            button_text: "Begin",
            title: "Patient Introduction",
        });

        /**
         * Inject the attempts submitted into the modal
         */
        if (this.state.submitted.hasOwnProperty("patient_" + patient_id)) {
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

    show_wrong_groups(groups) {
        for (let i in groups) {
            $(".group_" + groups[i]).animate({ "background-color": "rgba(255, 0, 0, 0.4)" }, 1000);
        }
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
                encoding = atob(encoding);

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
                encoding = atob(encoding);

                let [med, source] = encoding.split("_");
                this.state.current_attempt[current_group][med + "_" + source] = {
                    amt: med_amt,
                    action: med_action,
                };
            }
        });
    }

    /**
     * bind events for the admin controls
     */
    bind_admin_events() {
        /**
         * TODO: add a new admin
         */
        $("#admin-add-admin").off().on("click", () => {
            alert("Sorry! Not yet implemented.");
        });

        /**
         * Reset your own student data
         */
        $("#admin-reset-self").off().on("click", async () => {
            if (confirm("Are you sure you want to reset your own data?")) {
                await this.admin_data.reset_self();

                // force refresh the page.
                window.location.reload(true);
            }
        });
    }

    /**
     * bind events for the charts controls
     */
    bind_charts_events() {
        /**
         * Show charts page
         */
        $("#view-data-charts").off().on("click", () => {
            console.log("etc");
            this.modal.show_modal({
                content: "charts go here <h3>asd</h3>",
                show_image: false,
                button_text: "Done",
                title: "Data View",
                show_cancel: false,
            });
        });
    }
}
