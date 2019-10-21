/**
 * Init script
 */
async function init({ state: state, ui: ui, student_data: student_data, patient_data: patient_data }) {
    /**
     * Initialize the backend user if necessary
     */
    let res = await student_data.student_init();
    console.log(res); // should be "student ok"

    /**
     * Get submitted trials data
     */
    let submitted_trials = await student_data.submitted_trials_data();
    state.submitted = submitted_trials;

    /**
     * Get the patient data
     */
    let patients_data = await patient_data.all_patients_data();
    console.log(patients_data);
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
    if (location.hash) {
        //$("#medGrid").empty();
        $(`#patientList a:nth-child(${parseInt(location.hash.split("#")[1]) + 1})`).trigger("click");
    }
}

function response() {
    var responses = state['patients'][state.current_patient].responses
    var output = ""
    state['patients'][state.current_patient].tries =
        state['patients'][state.current_patient].tries + 1 || 0;

    var groupCount = state['patients'][state.current_patient].groupCount;
    var alertColor = "white"
    if (state['patients'][state.current_patient].tries) {
        alertColor = "yellow"
    }
    var results = {
        patientId: state.current_patient,
        patientName: state['patients'][state.current_patient].info['Name'],
        try: state['patients'][state.current_patient].tries,
        groupInfo: []
    };

    for (i = 1; i <= groupCount; i++) {

        groupTotal = 0;
        var groupMedString = "";

        $(`.group_${i}`).each(function (item) {
            var [, med, source] = $(this).attr("class").split(" ")[2].split("_");
            groupMedString += `${med} ${source}`;
            groupTotal += parseInt($(this).attr("data-medAmount"));
        });

        var groupInfoDict = {
            groupMed: groupMedString,
            groupTotal: groupTotal,
            isCorrect: false
        };

        // results.isCorrect.push(false)
        var target = parseInt(responses[i][responses[0].indexOf("target value")])

        if (groupTotal > target) {
            output += responses[i][responses[0].indexOf(">target")] + "<br/>"
            $(`.group_${i}`).css({ "background-color": alertColor })
        }
        else if (groupTotal < target) {
            output += responses[i][responses[0].indexOf("<target")] + "<br/>"

            $(`.group_${i}`).css({ "background-color": alertColor })
        }
        else {
            groupInfoDict.isCorrect = true;
            $(`.group_${i}`).css({ "background-color": "white" })
        }
        results.groupInfo.push(groupInfoDict)
    }
    if (output == "") {
        output = "Response for all correct meds prescribed";
    } else {
        if (!state['patients'][state.current_patient].tries) {
            output = "Try Again!"
        }
    }

    $.post("save.php", {
        data: JSON.stringify(results),
        contentType: 'application/json'
    }).done(() => {
        //alert("saved results");
    }).fail(() => {
        alert("an error occured while saving results");
    });

    return output
}

function radio(evt) {
    var medId = $(this).attr("name").split("_")[1]
    $(`select[name='med_modify_${medId}']`)[0].selectedIndex = 0;
    state['patients'][state.current_patient].medications[medId].pickedOption =
        true;
    $(this).parent().attr("data-medAmount", $(this).val())
    $(this).parent().attr("data-action", $(this).data("action"))
}

function select(evt) {
    var medId = $(this).attr("name").split("_")[2];
    if ($(this)[0].selectedIndex) {
        state['patients'][state.current_patient].medications[medId].pickedOption =
            true;
        $(this).parent().attr("data-medAmount", $(this).val())
        $(this).parent().attr("data-action", $(evt.currentTarget).text())
        console.log($(`#${evt.currentTarget.name} option:selected`))
        $(`input[name='med_${medId}']`).prop('checked', false);
    } else {
        state['patients'][state.current_patient].medications[medId].pickedOption =
            false;
    }
}
