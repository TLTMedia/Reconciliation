$(window).resize(function () {
    resizeWindow();
});

function resizeWindow() {
    var w = $(window).width();
    $("html").css({
        "font-size": w / 100 + "px",

    });
    //setQuestionTextSize()
}
resizeWindow()

