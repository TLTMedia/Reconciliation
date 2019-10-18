// Get the modal
$(function () {
    const modal = document.getElementById("myModal");

    // Get the <span> element that closes the modal
    let closeButtons = document.getElementsByClassName("modal-close-action");

    // When the user clicks the button, open the modal
    // When the user clicks on <span> (x), close the modal
    Array.from(closeButtons).forEach(closer => {
        closer.onclick = function () {
            if (closer.classList.contains("modal-reset-hash")) {
                history.pushState(null, null, ' '); // remove the hash, but preserves history so back button works
            }
            modal.style.display = "none";
        }
    });

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = "none";
            history.pushState(null, null, ' '); // remove the hash, but preserves history so back button works
        }
    }
});

function showModal(content, buttonText = "Close", title = "status") {
    const modal = document.getElementById("myModal");
    modal.style.display = "block";
    $("#modal-header-title").html(title);
    $(".modal-body").html(content);
    $("#modal-main-action").html(buttonText);
}
