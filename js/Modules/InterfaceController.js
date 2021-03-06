import { InterfaceEvents, Modal, Charts } from './_ModuleLoader.js';

export class InterfaceController {
    constructor({ state, toast, student_data, intro_data, patient_data, admin_data, charts_data }) {
        console.log("InterfaceController Module Loaded");

        // Assign all arguments to 'this'
        Object.keys(arguments[0]).map(arg => this[arg] = arguments[0][arg]);

        /**
         * String constants
         */
        this.id_constants = {
            "med_grid": "#medGrid",
            "patient_list": "#patientList",
        };

        /** 
         * Create Modal 
         */
        this.modal = new Modal({
            state: state,
            toast: toast,
            ui: this,
        });

        /**
         * Create Charts
         */
        this.charts = new Charts({
            state,
            charts_data,
            modal: this.modal,
        });

        /**
         * Create InterfaceEvents
         */
        this.ui_events = new InterfaceEvents({
            state,
            student_data,
            patient_data,
            admin_data,
            charts: this.charts,
            modal: this.modal,
            ui: this,
        });
    }

    /**
     * Populate the med grid with medications etc... this is the main interface data
     */
    populate_med_grid(patient_id, num) {
        let med = this.state['patients'][patient_id].medications[num];
        med["Modify 0 display"] = "Modify Prescription";

        let select = $("select[name='med_modify_" + num + "']");

        let a = -1;
        while (a++ < 7) {
            let option = med[`Modify ${a} display`];
            let value = med[`Modify ${a} numeric`] || 0;

            if (option) {
                select.append($("<option/>", {
                    value: value,
                    html: option,
                }));
            } else {
                break;
            }
        }
    }

    /**
     * Show the patient screen; shows the medications and choices - etc...
     */
    show_patient(patient_id) {
        this.state.current_patient = patient_id;
        this.state["current_attempt"] = {};

        /**
         * Hide the modal
         */
        this.modal.close_modal();

        /**
         * Shows patient intro during the med selection phase
         */
        $('.patient-intro').show();
        $('.patient-intro-body').html(this.state['patients'][patient_id].info.Synopsis);

        let medications = this.state['patients'][patient_id].medications;

        let i;
        for (i in medications) {
            let groupClass = `group_${medications[i].group}`;

            /**
             * So that meds with spaces don't break (classes can't have spaces!)
             */
            let med_original = `${medications[i]["Medication"]}_${medications[i]["Source"]}`;
            let med_encoded = "med_" + btoa(med_original);

            let row = $("<div/>", {
                class: "row " + groupClass + " " + med_encoded,
            });

            ["Medication", "Source", "Dose", "Route", "Frequency"].forEach(function (element) {
                row.append($('<div/>', {
                    class: `col ${medications[i][element]}`,
                    html: medications[i][element]
                }));
            });

            let continueVal = medications[i]["Current Dose numeric"];

            row.append([
                $("<input/>", {
                    type: "radio",
                    name: "med_" + i,
                    value: "0",
                    class: "col",
                    "data-action": "stop"
                }),
                $("<input/>", {
                    type: "radio",
                    name: "med_" + i,
                    value: continueVal,
                    class: "col",
                    "data-action": "continue"
                }),
                $('<select/>', {
                    name: "med_modify_" + i,
                    class: "col"
                })
            ]);

            $("#medGrid").append(row);

            this.populate_med_grid(patient_id, i);
        }

        $("#medGrid").append(
            $('<button/>', {
                value: "submit",
                id: "submit-attempt",
                class: "btn-primary",
                html: "Submit",
            }).on("click", () => {
                this.state.end_time = Math.floor(Date.now() / 1000);
                this.ui_events.submit_patient_evaluation()
            })
        );

        $('input').on("click", event => {
            this.ui_events.bind_radio_choice_click(event);
        });

        $('select').on("change", event => {
            this.ui_events.bind_select_choice_click(event);
        });

        $('#medGrid').show();
    }

    /**
     * Resets the med-grid headers for when new patient data is loaded.
     */
    reset_med_grid_headers() {
        $(this.id_constants["med_grid"]).empty();
        $(this.id_constants["med_grid"]).html(`
            <div class="row">
                <div id="medication" class="col">Medication</div>
                <div id="source" class="col">Source</div>
                <div id="dose" class="col">Dose</div>
                <div id="route" class="col">Route</div>
                <div id="frequency" class="col">Frequency</div>
                <div id="stop" class="col">Stop</div>
                <div id="continue" class="col">Continue</div>
                <div id="modify" class="col">Modify</div>
            </div>
        `);
    }

    /**
     * Populates the patient names in the menu drop-down
     */
    populate_patient_list() {
        let patient;
        for (patient in this.state.patients) {
            $(this.id_constants["patient_list"]).append($('<a/>', {
                id: "patient_" + patient,
                class: "dropdown-item",
                html: this.state.patients[patient].info.Name
            }).on("click", event => {
                let selected_patient_id = event.target.id.split("_")[1];
                let submissions = this.state.submitted["patient_" + selected_patient_id];

                if (parseInt(submissions) == 2) {
                    event.stopPropagation();

                    let tmp = false;
                    this.state.student_report.forEach(submission => {
                        if (parseInt(submission.patient_id) == parseInt(selected_patient_id)) {
                            if (submission.correct) {
                                this.toast.create_toast("You already submitted the correct response for this patient.", "Success");
                                tmp = true;
                                return;
                            }
                        }
                    });

                    if (!tmp) {
                        this.toast.create_toast("You already used all your attempts!", "Warning");
                    }
                } else if (parseInt(submissions) == 1) {
                    let tmp = false;
                    this.state.student_report.forEach(submission => {
                        if (parseInt(submission.patient_id) == parseInt(selected_patient_id)) {
                            if (submission.correct) {
                                event.stopPropagation();
                                this.toast.create_toast("You already submitted the correct response for this patient.", "Success");
                                tmp = true;
                                return;
                            }
                        }
                    });

                    if (!tmp) {
                        this.ui_events.load_patient_intro(event);
                    }
                } else {
                    this.ui_events.load_patient_intro(event);
                }
            }));
        }
    }

    /**
     * Show the welcome text
     */
    show_welcome_message() {
        $(this.id_constants["med_grid"]).html(`
            <br />
            <p>${this.state.intro}</p>
        `);
    }

    check_permissions() {
        if (this.state.user_type == "admin") {
            $(".admin").show();
            this.ui_events.bind_admin_events();
            this.ui_events.bind_charts_events();
        }
    }
}
