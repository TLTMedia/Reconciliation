/**
 * Initialization of Reconciliation scripts begin here
 */

import { InterfaceController, APIHandler, StudentData, PatientData, Toast } from './Modules/ModuleLoader.js';

(async () => {
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

    /**
     * These must be await, 
     * & must be declared separately from their declarations
     * 
     * Cannot have inline await declarations e.g.) const courses = await new Courses(api);
     * You can if you have only 1 await in an async block, but not in this case where there's multiple. 
     */
    const student_data = await student_init;
    const patient_data = await patient_init;

    /**
     * Classes that need awaited objects
     */
    const ui = new InterfaceController({
        state: state,
        toast: toast,
        student_data: student_data,
        patient_data: patient_data,
    });

    /**
     * Call the init script
     */
    init({
        state: state,
        ui: ui,
        api: api, // this should be removed eventually
        student_data: student_data,
        patient_data: patient_data,
    });
})();