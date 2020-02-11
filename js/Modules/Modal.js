export class Modal {
    constructor({ state, ui }) {
        this.state = state;
        this.ui = ui;
        this.modal_content = $(".modal-content")
        /**
         * TODO: rewrite this;
         * It was copy-pasted form old modal.js
         */
        this.modal = document.getElementById("myModal");

        this.modal_content.draggable();
        // When the user clicks anywhere outside of the modal, close it
        window.onclick = event => {
            if (event.target == this.modal) {
                $(this.modal).hide();
                // TODO: fix
                // history.pushState(null, null, ' '); // remove the hash, but preserves history so back button works
            }
        }
    }

    show_modal({ content, image = "images/YoungGirlPatients_Base.png", button_text = "Close", title = "status", show_cancel = true, show_image = true, show_attempts = true } = {}) {
        // this.modal.style.display = "block";

        $(this.modal).show();
        this.modal_content.css({ left: 0, top: 0 });

        $(".modal-image").attr("src", image);
        $("#modal-header-title").html(title);
        $(".modal-body").html(content);
        $("#modal-main-action").html(button_text);
        if (show_cancel) {
            $("#modal-secondary-action").show();
        } else {
            $("#modal-secondary-action").hide();
        }

        if (!show_image) {
            $(".modal-image").hide();
        } else {
            $(".modal-image").show();
        }

        if (!show_attempts) {
            $(".modal-attempts").hide();
        } else {
            $(".modal-attempts").show();
        }
    }

    set_modal_body(html) {
        $(".modal-body").html(html);
    }

    append_modal_body(html) {
        $(".modal-body").append(html);
    }

    get_width() {
        return $(".modal-body").width();
    }

    get_height() {
        return $(".modal-body").width();
    }

    close_modal() {
        $(this.modal).hide();
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
            this.state.start_time = Math.floor(Date.now() / 1000);
        });
    }

    set_main_action_go_home() {
        $("#modal-main-action").one("click", () => {
            // force refresh the page.
            window.location.href = window.location.origin + window.location.pathname;
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
