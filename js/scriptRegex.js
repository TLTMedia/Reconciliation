var state = {};
$(function () {
    $.get('json/data.json', function (patients) {
        state['patients'] = patients
        init(patients)
    })
})

function init(patients) {
    for (patient in patients) {

        $('#patientList').append($('<button/>', {
            id: "patient_" + patient,
            html: patients[patient].info.Name
        }).on("click", showPatient))
    }

    if (location.hash) {
        $(`#patientList button:nth-child(${location.hash.split("#")[1]})`)
            .trigger("click")
    }
}

function showPatient(evt) {

    $('#patientList').hide()
    var patientId = $(this)[0].id.split("_")[1]
    location.hash = parseInt(patientId) + 1
    state.currentPatient = patientId
    $('#bio').html(state['patients'][patientId].info.Intro)
    for (i in state['patients'][patientId].medications) {
        var groupClass = "group_" + state['patients'][patientId].medications[i].group
        var row = $("<div/>", { class: `row ${groupClass}` });
        ["Medication", "Source", "Dose", "Route", "Frequency"].forEach(function (
            element) {
            row.append($('<div/>', {
                class:
                    `column ${state['patients'][patientId].medications[i][element]}`,

                html: state['patients'][patientId].medications[i][element]
            }))
        })
        var continueVal = state['patients'][patientId].medications[i]["Current Dose numeric"]
        row.append([
            $("<input/>",
                { type: "radio", name: "med_" + i, value: "0", class: "column" }),
            $("<input/>", {
                type: "radio",
                name: "med_" + i,
                value: continueVal,
                class: "column"
            }),
            $('<select/>', { name: "med_modify_" + i, class: "column" })
        ])
        $("#medGrid").append(row)

        populateModifications(patientId, i)
    }

    $("#medGrid").append($('<button/>', { value: "submit", html: "Submit" }))
    $('button').on("click", button)
    $('input').on("click", radio)
    $('select').on("change", select)
    $('#medGrid').show()
}

function button(evt) {

    var status = ""
    var report = ""
    for (i in state['patients'][state.currentPatient].medications) {
        medication = state['patients'][state.currentPatient].medications[i];
        if (!medication.pickedOption) {
            status += `You need to set a course of action for ${
                medication.Medication},  ${medication.Source} <br/>`;
        }
    }
    /*   
    else {
          medResponse = `${medication.Medication},${medication.Source} ${
              medication[medication.pickedOption + " Response"]}<br/>`
          // var correct="correct course of action<br/>";
          var correct = "<span id='check'>&check;</span>";
    
          if (!(medication.Correct == medication.pickedOption)) {
            correct = "<span id='cross'>&#x2718;</span>";
          }
    
          report += correct + medResponse;
        }
      }
    */
    if (!status) {
        //  status = report
        status = response();
    }

    $('#status').html(status)
}

function response() {
    var medications = state['patients'][state.currentPatient].medications
    var responses = state['patients'][state.currentPatient].responses

    var matchedResponse = ""
    for (j of responses.slice(1)) {
        var patternMatched = true;
        console.log("-------------------------")
        for (k in medications) {

            medication = medications[k];
            medName = medication.Medication;
            medSource = medication.Source;
            medIndex = responses[0].indexOf(`${medName}_${medSource}`)

            checkResponse = j[medIndex];
            console.log(`-${checkResponse}-`)
            console.log(`${medName}_${medSource}`)
            console.log(checkResponse == "*")
            console.log(patternMatched)
            if (checkResponse == "*" || checkResponse == medication.pickedOption) {
            }
            else {
                patternMatched = false;
            }
        }
        if (patternMatched) {

            return j[responses[0].length - 1]
        }
    }
}
function radio(evt) {
    var medId = $(this).attr("name").split("_")[1]
    console.log(state['patients'][state.currentPatient].medications[medId])
    $(`select[name='med_modify_${medId}']`)[0].selectedIndex = 0;
    state['patients'][state.currentPatient].medications[medId].pickedOption =
        $(this).val()
}

function select(evt) {
    var medId = $(this).attr("name").split("_")[2];
    if ($(this)[0].selectedIndex) {
        state['patients'][state.currentPatient].medications[medId].pickedOption =
            $(this).val()

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
        var option = state['patients'][patientId].medications[i][key]
        console.log(option)
        var key = `Modify ${a} numeric`
        var value = state['patients'][patientId].medications[i][key] || 0;
        if (option) {
            select.append($("<option/>", { value: value, html: option }))
        }
        else break;
    }
}
