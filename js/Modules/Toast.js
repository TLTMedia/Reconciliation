export class Toast {
    /**
     * Mostly just a wrapper around the jQuery $.toast library...
     */
    constructor() {
        console.log("Toast Module Loaded");
    }

    create_toast(message = "test toast notification", heading = "Info") {
        // heading can be: Info, Error, Warning, Success
        $.toast({
            heading: heading,
            text: message,
            showHideTransition: 'slide',
            icon: heading.toLowerCase()
        })
    }
}