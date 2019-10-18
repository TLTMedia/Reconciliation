const state = {};

$(function () {
    $.get('json/data.json', function (patients) {
        state['patients'] = patients;
        init(patients);
    }).done(() => {
        //console.log(state.patients[2].medications);
    });
})

function init(patients) {
    for (patient in patients) {
        $('#patientList').append($('<button/>', {
            id: "patient_" + patient,
            html: patients[patient].info.Name
        }).on("click", showPatient));
    }

    if (location.hash) {
        $(`#patientList button:nth-child(${parseInt(location.hash.split("#")[1]) + 1})`)
            .trigger("click");
        console.log(parseInt(location.hash.split("#")[1]) + 1);
    }
}

function showPatientIntro(event) {
    let patientId = event.target.id.split("_")[1];
    location.hash = parseInt(patientId);
    showModal(state['patients'][patientId].info.Intro, "Begin", "Patient Introduction");
    $("#modal-main-action").one("click", () => {
        showPatient(patientId)
    });
}

function showPatient(patientId) {
    $('#patientList').hide();
    $('.patient-intro').show();
    // console.log(state.patients[2].medications);
    $('#patientList').hide()
    var patientId = $(this)[0].id.split("_")[1]
    location.hash = parseInt(patientId) + 1
    state.currentPatient = patientId
    $('.patient-intro-body').html(state['patients'][patientId].info.Intro);

    var medications = state['patients'][patientId].medications;
    // console.log(state.patients[2].medications);

    for (i in state['patients'][patientId].medications) {
        var groupClass = `group_${medications[i].group}`
        // console.log(i);
        // console.log(medications[i]);
        // console.log(medications[i].group);
        var row = $("<div/>", {
            class: `row ${groupClass} med_${medications[i]["Medication"]}_${
                medications[i]["Source"]}`
        });
        ["Medication", "Source", "Dose", "Route", "Frequency"].forEach(function (
            element) {
            row.append($('<div/>', {
                class: `column ${medications[i][element]}`,

                html: medications[i][element]
            }))
        })
        var continueVal = medications[i]["Current Dose numeric"]
        row.append([
            $("<input/>", {
                type: "radio",
                name: "med_" + i,
                value: "0",
                class: "column",
                "data-action": "stop"
            }),
            $("<input/>", {
                type: "radio",
                name: "med_" + i,
                value: continueVal,
                class: "column",
                "data-action": "continue"
            }),
            $('<select/>', { name: "med_modify_" + i, class: "column" })
        ])
        $("#medGrid").append(row)

        populateModifications(patientId, i)
    }

    $("#medGrid").append($('<button/>', { value: "submit", id: "myBtn", html: "Submit" }))
    $('button').on("click", button)
    $('input').on("click", radio)
    $('select').on("change", select)
    $('#medGrid').show()
}
function button(evt) {
    showModal();
    var groupSet = new Set();
    var status = ""
    var report = ""
    for (i in state['patients'][state.currentPatient].medications) {
        medication = state['patients'][state.currentPatient].medications[i];
        groupSet.add(medication.group)
        if (!medication.pickedOption) {
            status += `You need to set a course of action for ${
                medication.Medication},  ${medication.Source} <br/>`;
        }
    }
    state['patients'][state.currentPatient].groupCount = groupSet.size
    if (status == "") {
        //  status = report
        status = response();
    }

    $('#status').html(status)
}

function response() {
    var responses = state['patients'][state.currentPatient].responses
    var output = ""
    state['patients'][state.currentPatient].tries =
        state['patients'][state.currentPatient].tries + 1 || 0;

    var groupCount = state['patients'][state.currentPatient].groupCount;
    var alertColor = "white"
    if (state['patients'][state.currentPatient].tries) {
        alertColor = "yellow"
    }
    var results = {
        patientId: state.currentPatient,
        patientName: state['patients'][state.currentPatient].info['Name'],
        try: state['patients'][state.currentPatient].tries,
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
        if (!state['patients'][state.currentPatient].tries) {
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
    state['patients'][state.currentPatient].medications[medId].pickedOption =
        true;
    $(this).parent().attr("data-medAmount", $(this).val())
    $(this).parent().attr("data-action", $(this).data("action"))
}

function select(evt) {
    var medId = $(this).attr("name").split("_")[2];
    if ($(this)[0].selectedIndex) {
        state['patients'][state.currentPatient].medications[medId].pickedOption =
            true;
        $(this).parent().attr("data-medAmount", $(this).val())
        $(this).parent().attr("data-action", $(evt.currentTarget).text())
        console.log($(`#${evt.currentTarget.name} option:selected`))
        $(`input[name='med_${medId}']`).prop('checked', false);
    } else {
        state['patients'][state.currentPatient].medications[medId].pickedOption =
            false;
    }
}

function populateModifications(patientId, i) {
    var a = -1;
    var med = state['patients'][patientId].medications[i];
    var doSend = $('<div/>')
    var select = $("select[name='med_modify_" + i + "']")
    med["Modify 0 display"] = "Modify Prescription";

    while (a++ < 7) {
        var key = `Modify ${a} display`
        var option = state['patients'][patientId].medications[i][key];
        var key = `Modify ${a} numeric`;
        var value = state['patients'][patientId].medications[i][key] || 0;
        if (option) {
            select.append($("<option/>", { value: value, html: option }))
        } else
            break;
    }
}
