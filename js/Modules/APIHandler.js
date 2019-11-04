export class APIHandler {
    constructor() {
        console.log("APIHandler Module Loaded");

        this.base_url = location.pathname + 'api/public/';
    }

    parse_get_data(data) {
        let res = "";
        for (let key in data) {
            res += "&" + key + "=" + data[key];
        }

        return res;
    }

    request({ endpoint = 'student_init', method = 'GET', data = '', data_type = 'object', callback } = {}) {
        let get_data = '';
        if (method == 'GET') {
            get_data = this.parse_get_data(data);
            data = '';
        } else if (method == 'POST' && data_type == 'object') {
            data = JSON.stringify(data);
        } else if (method == 'POST' && data_type == 'form') {
            // do nothing with the data
        }

        let defer = $.Deferred();
        $.ajax({
            url: this.base_url + endpoint + '?modular=true' + get_data,
            method: method,
            data: data,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false
        }).done(data => {
            if (data['status'] == 'error') {
                console.error("ERROR", data);

                let error_data = data.data || data.message;
                alert("A server error occured. \nPlease contact: 'paul.st.denis@stonybrook.edu' with the subject 'Reconciliation Server Error'\nAnd include this error: " + error_data);

                return;
            } else if (data['status'] !== 'ok') {
                console.error("NOK", data);

                return;
            } else {
                if (callback) {
                    defer.resolve(callback(data['data']));
                } else {
                    defer.resolve(data['data']);
                }
            }
        }).fail((_, __, errorThrown) => {
            alert("An unexpected error occured. Please try again.");

            console.log("ERROR", errorThrown);
        });

        return defer.promise();
    }
}
