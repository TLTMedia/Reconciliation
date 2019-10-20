<?php

header("Access-Control-Allow-Origin: *");

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

// Necessary b/c of server PHP config
date_default_timezone_set("America/New_York");

require "../vendor/autoload.php";
require "../Common/Parameters.php";
require "../Common/APIResponse.php";

// Create the Object Variables
$parameters  = new Parameters;
$responseFmt = new APIResponse;

// Define String Constants
$PATH_PATIENTS = "../../data/patients/";
$PATH_STUDENTS = "../../data/students/";
$PATH_METADATA = "../../data/metadata/";

// Rereference Shibboleth Globals used
// Provides a bit of code-space away from Shibboleth,
// so that we can use other auth types in the future...
// Relocates hard coded $_SERVER[...] vars in the code below to up top here.
$authUniqueId  = $_SERVER["eppn"];
$authFirstName = $_SERVER["nickname"];
$authLastName  = $_SERVER["sn"];

/**
 * Prepare App
 */
$app = new \Slim\Slim(array(
    "templates.path" => "../templates",
));

/**
 * Create monolog logger and store logger in container as singleton
 * (Singleton resources retrieve the same log resource definition each time)
 * See Log Levels: https://github.com/Seldaek/monolog/blob/master/doc/01-usage.md
 */
$app->container->singleton("log", function () {
    $log = new \Monolog\Logger("Reconciliation");
    $log->pushHandler(new \Monolog\Handler\StreamHandler("../logs/app.log", \Monolog\Logger::DEBUG));
    return $log;
});

/**
 * Prepare view
 */
$app->view(new \Slim\Views\Twig());
$app->view->parserOptions = array(
    "charset"          => "utf-8",
    "cache"            => realpath("../templates/cache"),
    "auto_reload"      => true,
    "strict_variables" => false,
    "autoescape"       => true,
);
$app->view->parserExtensions = array(new \Slim\Views\TwigExtension());

/**
 * Basic index route called
 */
$app->get("/", function () use ($app) {
    $app->log->info("Reconciliation '/' route called");
    $app->render("index.html");
});

/**
 * General init script to check if students results directory exists & make it if it doesn't
 */
$app->get("/student_init", function () use ($app, $PATH_STUDENTS, $responseFmt, $authUniqueId, $authFirstName, $authLastName) {
    require "../Actions/Student.php";
    $users = new Student($app->log, $PATH_STUDENTS, $authUniqueId);

    echo $responseFmt->arrayToAPIObject(
        $users->studentFullInit(
            $authFirstName,
            $authLastName
        )
    );
});

// /**
//  * Get the current trial number of a student on a patient.
//  */
// $app->get("/trial_number", function () use ($app, $PATH, $PATH_COURSES, $parameters, $authUniqueId) {
//     $data = $app->request->get();
//     $parameters->paramCheck($data, array(
//         "creator", "course",
//     ));

//     require "../Actions/Users.php";
//     $users = new Users($app->log, $PATH);

//     echo $users->getWorksOfCourseAndCreator(
//         $data["creator"],
//         $data["course"],
//         $PATH_COURSES,
//         $authUniqueId
//     );
// });

/**
 * Adds a course to the courses list. Only course admins defined in courses/permissions.json may add courses.
 */
// $app->post("/add_course", function () use ($app, $PATH, $parameters, $authUniqueId) {
//     $data = json_decode($app->request->getBody(), true);
//     $parameters->paramCheck($data, array(
//         "course",
//     ));

//     require "../Actions/Comments.php";
//     $comments = new Courses($app->log, $PATH_COURSES, $authUniqueId);

//     echo $responseFmt->message(
//         $comments->addCourse(
//             $data["course"]
//         )
//     );
// });

/**
 * Run App
 */
$app->run();
