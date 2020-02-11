<?php

// For ajax/api requests from diff origins
header("Access-Control-Allow-Origin: *");

// NOTE: remove in prod.
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
$PATH_PATIENTS              = "../../data/patients/";
$PATH_STUDENTS              = "../../data/students/";
$PATH_METADATA              = "../../data/metadata/";
$PATH_EXCLDATA              = "../../data/excel_dumps/students/";
$PATH_EXCLDATA_STUDENT_META = "../../data/excel_dumps/students_metadata/";
$PATH_EXCLDATA_PATIENTS     = "../../data/excel_dumps/patients/";

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
 * Create the master document...
 */
$app->get("/generate_master_doc", function () use ($app, $PATH_STUDENTS, $parameters, $responseFmt) {
    $param = $app->request->get();
    $parameters->paramCheck($param, array(
        "download",
    ));

    /**
     * if download == true, then generate and then redirect the excel doc
     * else, json return the doc dl link
     */

    require "../Actions/Reporting.php";
    $reporting = new Reporting($app->log, $PATH_STUDENTS, "_");

    if ($param["download"] == "true") {
        $responseFmt->csvDownload(
            "master",
            $reporting->getMasterReport()
        );
    } else {
        echo $responseFmt->arrayToAPIObject(
            array(
                "data" => $reporting->getMasterReport(),
            )
        );
    }
});

/**
 * Run App
 */
$app->run();
