export class Charts {
    constructor({ state, charts_data, modal }) {
        console.log("Charts Module Loaded");

        this.state = state;
        this.charts_data = charts_data;
        this.modal = modal;
    }

    /**
     * Render all the charts
     */
    async render_charts() {
        let data = await this.charts_data.get_master_data();
        let parsed_data = d3.csvParse(data);

        // initially, empty out the modal body
        this.modal.set_modal_body("");

        // class accuracy
        this.show_class_accuracy(parsed_data);

        // scenario performance
        this.show_scenario_performance(parsed_data);
    }

    /**
     * Group the data by:
     * Patient ID -> Student -> Trial -> raw questions
     */
    cleanup_and_group_data(data) {
        return d3.nest().key(d => {
            return d.patient;
        }).key(d => {
            return d.student;
        }).key(d => {
            return d.trial;
        }).rollup(v => {
            return {
                questions: v,
                trial_correct: v[0]["trial correct"],
            };
        }).object(data);
    }

    /**
     * Show class accuracy
     */
    show_class_accuracy(data) {
        let grouped_data = this.cleanup_and_group_data(data);

        let total_correct = 0;
        let total_incorrect = 0;
        for (let [patient_id, data] of Object.entries(grouped_data)) {
            for (let [student, trial_responses] of Object.entries(data)) {
                if (trial_responses["1"]["trial_correct"] == "true") {
                    total_correct++;
                } else if (Object.keys(trial_responses).length == 2) {
                    // basically, the case that there are two trials, given the first one was false
                    if (trial_responses["2"]["trial_correct"] == "true") {
                        // if the second response was correct
                        total_correct++;
                    } else {
                        // second resposne was incorrect
                        total_incorrect++;
                    }
                } else {
                    // there was only one trial and it was incorrect
                    total_incorrect++;
                }
            }
        }

        let class_accuracy = (total_correct / (total_correct + total_incorrect) * 100).toFixed(2);
        this.modal.append_modal_body("<p><span style='text-decoration:underline;'>Class Accuracy:</span></br> " + class_accuracy + "% </p>");
    }

    /**
     * Show scenario performance
     */
    show_scenario_performance(data) {
        let grouped_data = this.cleanup_and_group_data(data);

        this.modal.append_modal_body("<span style='text-decoration:underline;'>Scenario Performance:</span></br>");

        let performance = {};
        let charting_data = [];
        for (let [patient_id, data] of Object.entries(grouped_data)) {
            performance[patient_id] = {
                incorrect: 0,
                correct: 0,
            };

            for (let [student, trial_responses] of Object.entries(data)) {
                if (trial_responses["1"]["trial_correct"] == "true") {
                    performance[patient_id]["correct"]++;
                } else if (Object.keys(trial_responses).length == 2) {
                    // basically, the case that there are two trials, given the first one was false
                    if (trial_responses["2"]["trial_correct"] == "true") {
                        // if the second response was correct
                        performance[patient_id]["correct"]++;
                    } else {
                        // second resposne was incorrect
                        performance[patient_id]["incorrect"]++;
                    }
                } else {
                    // there was only one trial and it was incorrect
                    performance[patient_id]["incorrect"]++;
                }
            }

            charting_data.push({
                "patient_id": patient_id,
                "percent_correct": (performance[patient_id]["correct"] / (performance[patient_id]["correct"] + performance[patient_id]["incorrect"]) * 100).toFixed(2),
            });

            this.modal.append_modal_body("Patient " + patient_id + ": " + (performance[patient_id]["correct"] / (performance[patient_id]["correct"] + performance[patient_id]["incorrect"]) * 100).toFixed(2) + "% </br>");
        }

        // now add/render the chart too
        this.modal.append_modal_body("<div id='chart-scenario-performance'></div>");
        let svg = d3.select("#chart-scenario-performance").append("svg:svg");
        let chart_width = this.modal.get_width();
        let chart_height = this.modal.get_width() / 2;

        $("#chart-scenario-performance").css({
            width: chart_width,
            height: chart_height,
        });

        let margin = {
            top: 20,
            right: 20,
            bottom: 30,
            left: 50,
        };

        let width = +chart_width - margin.left - margin.right;
        let height = +chart_height - margin.top - margin.bottom;

        let g = svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")");

        let x = d3.scaleBand()
            .rangeRound([0, width])
            .padding(0.1);

        let y = d3.scaleLinear()
            .rangeRound([height, 0]);

        x.domain(data.map(d => {
            return d["patient_id"];
        }));

        y.domain([0, d3.max(data, d => {
            return Number(d["percent_correct"]);
        })]);

        g.append("g")
            .attr("transform", "translate(0," + height + ")")
            .call(d3.axisBottom(x))

        g.append("g")
            .call(d3.axisLeft(y))
            .append("text")
            .attr("fill", "#000")
            .attr("transform", "rotate(-90)")
            .attr("y", 6)
            .attr("dy", "0.71em")
            .attr("text-anchor", "end")
            .text("Percent Correct");

        g.selectAll(".bar")
            .data(data)
            .enter().append("rect")
            .attr("class", "bar")
            .attr("x", function (d) {
                return x(d["patient_id"]);
            })
            .attr("y", function (d) {
                return y(Number(d["percent_correct"]));
            })
            .attr("width", x.bandwidth())
            .attr("height", function (d) {
                return height - y(Number(d["percent_correct"]));
            });
    }
}
