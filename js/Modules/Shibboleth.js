/**
 * This module is standalone - it doesn't use the APIHandler class;
 * So that it can be easily copy/pasted to other projects
 * 
 * NOTE: Again, this stuff is only ever useful in the case that:
 * The URL called is: https://apps.tlt.stonybrook.edu/marginalia/index.htm
 * (+1 https, +1 index.htm) are both called directly.
 * AND, the user/browser has no valid cookies for Shibboleth - so this script redirects page to login via shibboleth
 */
export class Shibboleth {
    constructor() {
        console.log("Shibboleth Module Loaded");
    }

    async ping() {
        /**
         * Fetch a page that is protected by shibboleth
         */
        let promise = new Promise(resolve => {
            fetch("./shibboleth/index.html").then(response => {
                if (response.status === 200) {
                    console.info("Shibboleth OK");
                    return resolve(response);
                }
            }).catch(() => {
                window.location.href = "./shibboleth/index.html";
                console.error("Force Shibboleth Login");
                return "error";
            });
        });

        return await promise;
    }
}
