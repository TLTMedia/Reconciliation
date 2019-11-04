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

// Rereference Shibboleth Globals used
// Provides a bit of code-space away from Shibboleth,
// so that we can use other auth types in the future...
// Relocates hard coded $_SERVER[...] vars in the code below to up top here.
$authUniqueId  = $_SERVER["eppn"];
$authFirstName = $_SERVER["nickname"];
$authLastName  = $_SERVER["sn"];

// The admins of the server, temporarily hardcoded
$authAdmins = array(
    "ikleiman@stonybrook.edu",
    "vlgarcia@stonybrook.edu",
    "chaotsai@stonybrook.edu",
    "pstdenis@stonybrook.edu",
);

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
$app->get("/student_init", function () use ($app, $PATH_STUDENTS, $PATH_PATIENTS, $responseFmt, $authUniqueId, $authFirstName, $authLastName, $authAdmins) {
    require "../Actions/Student.php";
    $users = new Student($app->log, $PATH_STUDENTS, $PATH_PATIENTS, $authUniqueId);

    echo $responseFmt->arrayToAPIObject(
        $users->studentFullInit(
            $authFirstName,
            $authLastName,
            $authAdmins
        )
    );
});

/**
 * Get the trial numbers for each patient this student submitted answers for
 */
$app->get("/submitted_trials_data", function () use ($app, $PATH_STUDENTS, $responseFmt, $authUniqueId) {
    require "../Actions/Trial.php";
    $trial = new Trial($app->log, $PATH_STUDENTS, $authUniqueId);

    echo $responseFmt->arrayToAPIObject(
        $trial->getSubmittedTrialAmounts()
    );
});

/**
 * TODO: Temporary~ endpoint to serve the patient data from this router
 * Get all patient data
 */
$app->get("/all_patients_data", function () use ($app) {
    $app->log->info("Using depreciated endpoint /all_patients_data");

    echo "{\"status\":\"ok\", \"data\": " . file_get_contents("../../json/data.json") . "}";
});

/**
 * Submit a students attempt
 */
$app->post("/submit_attempt", function () use ($app, $PATH_STUDENTS, $PATH_PATIENTS, $parameters, $responseFmt, $authUniqueId) {
    $data = json_decode($app->request->getBody(), true);
    $parameters->paramCheck($data, array(
        "attempt", "patient",
    ));

    require "../Actions/Student.php";
    $student = new Student($app->log, $PATH_STUDENTS, $PATH_PATIENTS, $authUniqueId);

    echo $responseFmt->arrayToAPIObject(
        $student->submitStudentAttempt(
            $data["patient"],
            $data["attempt"],
            $data["time"]
        )
    );
});

/**
 * Create the master document...
 */
$app->get("/generate_master_doc", function () use ($app, $PATH_STUDENTS, $PATH_EXCLDATA, $parameters, $responseFmt, $authUniqueId, $authAdmins) {
    if (!in_array($authUniqueId, $authAdmins)) {
        echo "invalid permissions";
        exit;
    }

    $param = $app->request->get();
    $parameters->paramCheck($param, array(
        "download",
    ));

    /**
     * if download == true, then generate and then redirect the excel doc
     * else, json return the doc dl link
     */

    require "../Actions/Reporting.php";
    $reporting = new Reporting($app->log, $PATH_STUDENTS, $authUniqueId);

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
 * Reset My Student Data
 */
$app->get("/reset_my_data", function () use ($app, $PATH_STUDENTS, $authUniqueId, $authAdmins) {
    if (!in_array($authUniqueId, $authAdmins)) {
        echo "invalid permissions";
        exit;
    }

    $files = glob($PATH_STUDENTS . $authUniqueId . "/responses/*"); // get all file names
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }

    echo "success";
});

/**
 * Reset All Student Data
 */
$app->get("/reset_all_student_data", function () use ($app, $PATH_STUDENTS, $authUniqueId, $authAdmins) {
    if (!in_array($authUniqueId, $authAdmins)) {
        echo "invalid permissions";
        exit;
    }

    $studentsDirs = glob($PATH_STUDENTS . '/*', GLOB_ONLYDIR);
    foreach ($studentsDirs as $student) {
        $files = glob($PATH_STUDENTS . $student . "/responses/*"); // get all file names
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    echo "success";
});

/**
 * Reset All Patient Data
 */
$app->get("/reset_all_patient_data", function () use ($app, $PATH_PATIENTS, $authUniqueId, $authAdmins) {
    if (!in_array($authUniqueId, $authAdmins)) {
        echo "invalid permissions";
        exit;
    }

    $studentsDirs = glob($PATH_PATIENTS . '/*', GLOB_ONLYDIR);
    foreach ($studentsDirs as $student) {
        $files = glob($PATH_PATIENTS . $student . "/responses/*"); // get all file names
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    echo "success";
});

/**
 * NOTE:
 * NOTE: Below endpoints are deprecated as they produce now unnecessary data.
 * NOTE:
 */

$app->get("/all_patient_report_xl", function () use ($app, $PATH_STUDENTS, $PATH_PATIENTS, $PATH_EXCLDATA_PATIENTS, $responseFmt, $authUniqueId, $authAdmins) {
    if (!in_array($authUniqueId, $authAdmins)) {
        echo "invalid permissions";
        exit;
    }

    require "../Actions/Trial.php";
    $trial = new Trial($app->log, $PATH_STUDENTS, $authUniqueId);

    $allPatients = $trial->getAllPatients($PATH_PATIENTS);
    if ($allPatients["status"] != "ok") {
        echo "unable to read full patient list";
        exit;
    }

    $savedDirs = array();
    foreach ($allPatients["data"] as $patient) {
        $newTrial = new Trial($app->log, $PATH_STUDENTS, $patient);

        $fullData = $newTrial->getFullPatientReport($PATH_PATIENTS);
        if ($fullData["status"] != "ok") {
            echo "unable to create full patient report";
            exit;
        }
        $data = $fullData["data"];

        /** Include PHPExcel */
        require_once dirname(__FILE__) . '/../PHPExcel/PHPExcel.php';

        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();

        // Set document properties
        $objPHPExcel->getProperties()->setCreator("Reconciliation API")
            ->setLastModifiedBy("Reconciliation API")
            ->setTitle("Patient Report - " . $patient)
            ->setSubject("Patient Report - " . $patient);

        // Set the headers
        $alphabet = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        $headers  = array("Student Id", "Trial", "Correct", "Elapsed Time", "Date");
        for ($i = 0; $i < count($headers); $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$i] . "1", $headers[$i]);
        }

        // Iterate through the raw data
        $row   = 2;
        $alpha = 0;
        for ($i = 0; $i < count($data); $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$alpha] . $row, $data[$i]->student_id);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$alpha + 1] . $row, $data[$i]->trial_number);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$alpha + 2] . $row, json_encode($data[$i]->correct));
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$alpha + 3] . $row, $data[$i]->elapsed_time_sec);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$alpha + 4] . $row, $data[$i]->submitted_time);
            $row++;
        }

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);

        $saveTo = $PATH_EXCLDATA_PATIENTS . $patient . ".xlsx";

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($saveTo);

        $savedDirs[] = $saveTo;
    }

    /**
     * TODO: above would be in its own function call, not in index.php
     */
    if (isset($_GET["patient"])) {
        $responseFmt->xlDownload(
            "patient_" . $_GET["patient"],
            file_get_contents($savedDirs[$_GET["patient"]])
        );
    }
    echo $responseFmt->arrayToAPIObject(
        array(
            "status" => "ok",
            "data"   => $savedDirs,
        )
    );
});

/**
 * Run App
 */
$app->run();
