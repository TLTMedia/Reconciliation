/**
 * Initialization of Reconciliation scripts begin here
 */
import {
  InterfaceController,
  APIHandler,
  StudentData,
  PatientData,
  Intro,
  Toast,
  AdminData,
  Shibboleth,
  ChartsData,
} from './Modules/_ModuleLoader.js';

(async () => {
  /**
   * Shibboleth Ping,
   * It's blocking - nothing else happens until the request resolves.
   */
  const shibboleth = new Shibboleth();
  await shibboleth.ping();

  /**
   * Our main state for Marginalia.
   */
  const state = {};

  const toast = new Toast();
  const api = new APIHandler();

  /**
   * Since we're using multiple await's in a single async -
   * we must first declare each, then we can await them all
   */
  const student_init = new StudentData({
    state: state,
    api: api,
  });
  const patient_init = new PatientData({
    state: state,
    api: api,
  });
  const intro_init = new Intro({
    state: state,
    api: api,
  });
  const admin_init = new AdminData({
    state: state,
    api: api,
  });
  const charts_init = new ChartsData({
    state: state,
    api: api,
  })

  /**
   * These must be await,
   * & must be declared separately from their declarations
   *
   * Cannot have inline await declarations e.g.) const courses = await new Courses(api);
   * You can if you have only 1 await in an async block, but not in this case where there's multiple.
   */
  const student_data = await student_init;
  const patient_data = await patient_init;
  const intro_data = await intro_init;
  const admin_data = await admin_init;
  const charts_data = await charts_init;

  /**
   * Classes that need awaited objects
   */
  const ui = new InterfaceController({
    state,
    toast,
    student_data,
    patient_data,
    intro_data,
    admin_data,
    charts_data,
  });

  /**
   * Call the init script
   */
  init({
    state,
    ui,
    student_data,
    patient_data,
    intro_data,
  });
})();

/**
 * Init script
 */
async function init({
  state,
  ui,
  student_data,
  patient_data,
  intro_data,
}) {
  /**
   * Initialize the backend user if necessary
   */
  state.user_type = await student_data.student_init();

  /**
   * Get submitted trials data
   */
  let submitted_trials = await student_data.submitted_trials_data();
  state.submitted = submitted_trials;

  /**
   * Get student submitted trials
   * (this is being used to garner which they've already successfully submitted so they don't need to do a second attempt)
   */
  let student_report = await student_data.student_report_data();
  state.student_report = student_report;

  /**
   * Get the patient data
   */
  let patients_data = await patient_data.all_patients_data();
  state.patients = patients_data;

  /**
   * Show welcome text
   */
  let intro_data_text = await intro_data.main_intro_data();
  state.intro = intro_data_text[0].replace("\n", "<br/>");
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

  /**
   * Check if the user is admin and show some UI elements to them if they are.
   */
  ui.check_permissions();
}
