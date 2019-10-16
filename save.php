<?php
date_default_timezone_set('America/New_York');

$fileResultsLocation = "results/" . $_SERVER["eppn"] . ".data.csv";

/**
 * Decode the json post data
 */
$patientData = json_decode($_POST["data"], true);
var_dump($patientData);

/**
 * First 3 parts for the user identification cols
 */
$attemptNum = getLastAttemptNum($fileResultsLocation) + 1;

for ($i = 0; $i < count($patientData["groupInfo"]); $i++) {
    $csvData   = array();
    $csvData[] = $_SERVER["eppn"];
    $csvData[] = $_SERVER["nickname"];
    $csvData[] = $_SERVER["sn"];
    $csvData[] = $attemptNum;
    $csvData[] = $patientData["patientId"];
    $csvData[] = $patientData["patientName"];
    $csvData[] = $i; /** Group ID */
    $csvData[] = $patientData["groupInfo"][$i]["groupMed"];
    $csvData[] = $patientData["groupInfo"][$i]["groupTotal"];
    $csvData[] = json_encode($patientData["groupInfo"][$i]["isCorrect"]);

    $csvStringData = implode(",", $csvData);
    file_put_contents(
        $fileResultsLocation,
        $csvStringData . "\n",
        FILE_APPEND
    );
}

function getLastAttemptNum($filePath)
{
    $lastAttemptData = getLastAttemptData($filePath);
    var_dump($lastAttemptData);
    if (!$lastAttemptData) {
        return 0;
    }
    if (count($lastAttemptData) < 4) {
        return 0;
    }
    return $lastAttemptData[3];
}

function getLastAttemptData($filePath)
{
    if (!file_exists($filePath)) {
        return false;
    }
    $results = file_get_contents($filePath);
    if (!$results) {
        return false;
    }
    $lines = explode("\n", $results);
    var_dump($lines);
    return explode(",", $lines[count($lines) - 2]); // 2 b/c last line is empty
}
