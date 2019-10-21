/**
 * Init script
 */
async function init({ state: state, ui: ui, student_data: student_data, patient_data: patient_data }) {
    /**
     * Initialize the backend user if necessary
     */
    await student_data.student_init();

    /**
     * Get submitted trials data
     */
    let submitted_trials = await student_data.submitted_trials_data();
    state.submitted = submitted_trials;

    /**
     * Get the patient data
     */
    let patients_data = await patient_data.all_patients_data();
    state.patients = patients_data;

    /**
     * Show welcome text
     */
    ui.show_welcome_message();

    /**
     * Set global event listeners 
     */
    ui.ui_events.bind_window_hash_change();

    /**
     * Populate the patient names in the patients list
     */
    ui.populate_patient_list();

    /** 
     * If a hash is present when the page loads - then parse it and load that patient by mimicking a click
     */
    ui.ui_events.check_window_hash_start();
}
