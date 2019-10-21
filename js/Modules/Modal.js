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
        let closeButtons = document.getElementsByClassName("modal-close-action");

        // When the user clicks the button, open the modal
        // When the user clicks on <span> (x), close the modal
        Array.from(closeButtons).forEach(closer => {
            closer.onclick = () => {
                if (closer.classList.contains("modal-reset-hash")) {
                    history.pushState(null, null, ' '); // remove the hash, but preserves history so back button works
                }
                this.modal.style.display = "none";
            }
        });

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
            $(".modal-close-btn").show();
        } else {
            $(".modal-close-btn").hide();
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
            this.ui.reset_med_grid_headers();
            this.ui.show_patient(patient_id);
        });
    }

    reset_main_action() {
        $("#modal-main-action").off();
    }

    reset_attempt_styling() {
        $("#attempt-current").css({ "color": "black" });
        $("#modal-main-action").prop("disabled", false);
    }

    user_max_attempts() {
        $("#attempt-current").css({ "color": "red" });
        $("#modal-main-action").prop("disabled", true);
    }

    set_current_attempt(attempt) {
        $("#attempt-current").html(attempt);
    }
}
