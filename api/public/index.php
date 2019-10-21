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

$PATH_EXCLDATA          = "../../data/excel_dumps/students/";
$PATH_EXCLDATA_PATIENTS = "../../data/excel_dumps/patients/";

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
$app->post("/submit_attempt", function () use ($app, $PATH_STUDENTS, $parameters, $responseFmt, $authUniqueId) {
    $data = json_decode($app->request->getBody(), true);
    $parameters->paramCheck($data, array(
        "attempt", "patient",
    ));

    require "../Actions/Student.php";
    $student = new Student($app->log, $PATH_STUDENTS, $authUniqueId);

    echo $responseFmt->arrayToAPIObject(
        $student->submitStudentAttempt(
            $data["patient"],
            $data["attempt"]
        )
    );
});

/**
 * Get the trial numbers for each patient this student submitted answers for
 */
$app->get("/my_student_report_xl", function () use ($app, $PATH_STUDENTS, $PATH_EXCLDATA, $parameters, $responseFmt, $authUniqueId) {
    $param = $app->request->get();
    $parameters->paramCheck($param, array(
        "download",
    ));

    require "../Actions/Trial.php";
    $trial = new Trial($app->log, $PATH_STUDENTS, $authUniqueId);

    $fullData = $trial->getFullStudentReport();
    if ($fullData["status"] != "ok") {
        echo "unable to create full student report";
        exit;
    }
    $data = $fullData["data"];

    /** Include PHPExcel */
    require_once dirname(__FILE__) . '/../PHPExcel/PHPExcel.php';

    // Create new PHPExcel object
    $objPHPExcel = new PHPExcel();

    $fileName = "Student Report - " . $authUniqueId;

    // Set document properties
    $objPHPExcel->getProperties()->setCreator("Reconciliation API")
        ->setLastModifiedBy("Reconciliation API")
        ->setTitle("Student Report - " . $authUniqueId)
        ->setSubject("Student Report - " . $authUniqueId);

    // Set the headers
    $alphabet = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
    $headers  = array("Patient Id", "Trial", "Correct", "Date");
    for ($i = 0; $i < count($headers); $i++) {
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$i] . "1", $headers[$i]);
    }

    // Iterate through the raw data
    $row   = 2;
    $alpha = 0;
    for ($i = 0; $i < count($data); $i++) {
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$alpha] . $row, $data[$i]->patient_id);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$alpha + 1] . $row, $data[$i]->trial_number);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$alpha + 2] . $row, json_encode($data[$i]->correct));
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$alpha + 3] . $row, $data[$i]->submitted_time);
        $row++;
    }

    // Set active sheet index to the first sheet, so Excel opens this as the first sheet
    $objPHPExcel->setActiveSheetIndex(0);

    $saveTo = $PATH_EXCLDATA . $authUniqueId . ".xlsx";
    if ($param["download"] == "yes") {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '.xlsx"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $saveTo = "php://output";
    } else {
        echo "file saved to: " . $saveTo;
    }

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($saveTo);
    exit;
});

$app->get("/all_student_report_xl", function () use ($app, $PATH_STUDENTS, $PATH_EXCLDATA, $responseFmt, $authUniqueId) {
    require "../Actions/Trial.php";
    $trial = new Trial($app->log, $PATH_STUDENTS, $authUniqueId);

    $allStudents = $trial->getAllStudents();
    if ($allStudents["status"] != "ok") {
        echo "unable to read full student list";
        exit;
    }

    foreach ($allStudents["data"] as $student) {
        $newTrial = new Trial($app->log, $PATH_STUDENTS, $student);

        $fullData = $newTrial->getFullStudentReport();
        if ($fullData["status"] != "ok") {
            echo "unable to create full student report";
            exit;
        }
        $data = $fullData["data"];

        /** Include PHPExcel */
        require_once dirname(__FILE__) . '/../PHPExcel/PHPExcel.php';

        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();

        $fileName = "Student Report - " . $student;

        // Set document properties
        $objPHPExcel->getProperties()->setCreator("Reconciliation API")
            ->setLastModifiedBy("Reconciliation API")
            ->setTitle("Student Report - " . $student)
            ->setSubject("Student Report - " . $student);

        // Set the headers
        $alphabet = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        $headers  = array("Patient Id", "Trial", "Correct", "Date");
        for ($i = 0; $i < count($headers); $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$i] . "1", $headers[$i]);
        }

        // Iterate through the raw data
        $row   = 2;
        $alpha = 0;
        for ($i = 0; $i < count($data); $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$alpha] . $row, $data[$i]->patient_id);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$alpha + 1] . $row, $data[$i]->trial_number);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$alpha + 2] . $row, json_encode($data[$i]->correct));
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$alpha + 3] . $row, $data[$i]->submitted_time);
            $row++;
        }

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);

        $saveTo = $PATH_EXCLDATA . $student . ".xlsx";
        echo "file saved to: " . $saveTo . "\n";

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($saveTo);
    }

    exit;
});

$app->get("/all_patient_report_xl", function () use ($app, $PATH_STUDENTS, $PATH_EXCLDATA_PATIENTS, $responseFmt, $authUniqueId) {
    // require "../Actions/Trial.php";
    // $trial = new Trial($app->log, $PATH_STUDENTS, $authUniqueId);

    // $allStudents = $trial->getAllStudents();
    // if ($allStudents["status"] != "ok") {
    //     echo "unable to read full student list";
    //     exit;
    // }

    // foreach ($allStudents["data"] as $student) {
    //     $newTrial = new Trial($app->log, $PATH_STUDENTS, $student);

    //     $fullData = $newTrial->getFullStudentReport();
    //     if ($fullData["status"] != "ok") {
    //         echo "unable to create full student report";
    //         exit;
    //     }
    //     $data = $fullData["data"];

    //     /** Include PHPExcel */
    //     require_once dirname(__FILE__) . '/../PHPExcel/PHPExcel.php';

    //     // Create new PHPExcel object
    //     $objPHPExcel = new PHPExcel();

    //     $fileName = "Patient Report - " . $student;

    //     // Set document properties
    //     $objPHPExcel->getProperties()->setCreator("Reconciliation API")
    //         ->setLastModifiedBy("Reconciliation API")
    //         ->setTitle("Patient Report - " . $student)
    //         ->setSubject("Patient Report - " . $student);

    //     // Set the headers
    //     $alphabet = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
    //     $headers  = array("Patient Id", "Trial", "Correct", "Date");
    //     for ($i = 0; $i < count($headers); $i++) {
    //         $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$i] . "1", $headers[$i]);
    //     }

    //     // Iterate through the raw data
    //     $row   = 2;
    //     $alpha = 0;
    //     for ($i = 0; $i < count($data); $i++) {
    //         $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$alpha] . $row, $data[$i]->patient_id);
    //         $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$alpha + 1] . $row, $data[$i]->trial_number);
    //         $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$alpha + 2] . $row, json_encode($data[$i]->correct));
    //         $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alphabet[$alpha + 3] . $row, $data[$i]->submitted_time);
    //         $row++;
    //     }

    //     // Set active sheet index to the first sheet, so Excel opens this as the first sheet
    //     $objPHPExcel->setActiveSheetIndex(0);

    //     $saveTo = $PATH_EXCLDATA_PATIENTS . $student . ".xlsx";
    //     echo "file saved to: " . $saveTo . "\n";

    //     $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    //     $objWriter->save($saveTo);
    // }

    exit;
});

// $app->get("/trial_numbers", function () use ($app, $PATH, $PATH_COURSES, $parameters, $authUniqueId) {
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
 * Run App
 */
$app->run();
