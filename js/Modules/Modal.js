export class Modal {
    constructor({ state, ui }) {
        this.state = state;
        this.ui = ui;

        /**
         * TODO: rewrite this;
         * It was copy-pasted form old modal.js
         */
        this.modal = document.getElementById("myModal");

        // Get the <span> element that closes the modal
        //let closeButtons = document.getElementsByClassName("modal-close-action");

        // When the user clicks the button, open the modal
        // When the user clicks on <span> (x), close the modal
        // Array.from(closeButtons).forEach(closer => {
        //     closer.onclick = () => {
        //         if (closer.classList.contains("modal-reset-hash")) {
        //             history.pushState(null, null, ' '); // remove the hash, but preserves history so back button works
        //         }

        //         this.close_modal();
        //     }
        // });

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function (event) {
            if (event.target == this.modal) {
                this.modal.style.display = "none";
                history.pushState(null, null, ' '); // remove the hash, but preserves history so back button works
            }
        }
    }

    show_modal({ content, button_text = "Close", title = "status", show_cancel = true } = {}) {
        if (show_cancel) {
            $("#modal-secondary-action").show();
        } else {
            $("#modal-secondary-action").hide();
        }

        this.modal.style.display = "block";
        $("#modal-header-title").html(title);
        $(".modal-body").html(content);
        $("#modal-main-action").html(button_text);
    }

    close_modal() {
        this.modal.style.display = "none";
    }

    set_main_action_close_modal() {
        this.reset_main_action();

        $("#modal-main-action").one("click", () => {
            this.close_modal();
        });
    }

    set_main_action_show_patient(patient_id) {
        this.reset_main_action();

        $("#modal-main-action").one("click", () => {
            location.hash = parseInt(patient_id);
            this.ui.reset_med_grid_headers();
            this.ui.show_patient(patient_id);
        });
    }

    set_secondary_action_close_modal_reset_hash() {
        $("#modal-secondary-action").one("click", () => {
            this.close_modal();

            /**
             * Only reset hash if there is no patient currently selected
             */
            if (!this.state.current_patient) {
                // removes the location.hash but also preserves history so pressing back button in browser works correctly
                history.pushState(null, null, ' ');
            } else {
                /**
                 * TODO: we successfully don't push ' ' into the hash if we're viewing a patient already and press cancel
                 * (cancel on the modal dropdown to view other patients)
                 * BUT... the hash gets updated to the new patient immediately onclick...
                 * either revert the hash to the current patient, or only update the hash after view_patient() is successful
                 */
            }
        });
    }

    reset_main_action() {
        $("#modal-main-action").off();
    }

    reset_attempt_styling() {
        $("#attempt-current").css({ "color": "black" });
    }

    user_max_attempts() {
        $("#attempt-current").css({ "color": "red" });
    }

    set_current_attempt(attempt) {
        if (parseInt(attempt) >= 2) {
            this.user_max_attempts();
        }

        $("#attempt-current").html(attempt);
    }
}
