<?php

class Student
{
    public function __construct($logger, $studentsPath, $patientsPath, $studentEppn)
    {
        $this->logger       = $logger;
        $this->studentsPath = $studentsPath;
        $this->patientsPath = $patientsPath;
        $this->studentEppn  = $studentEppn;
    }

    /**
     * Save a students submission and calculate the outcome
     */
    public function submitStudentAttempt($patientId, $attemptData, $time)
    {
        $sorted = array();
        for ($i = 1; $i <= count($attemptData); $i++) {
            $sorted["group_$i"] = $attemptData["group_" . $i];
        }

        $attemptData = $sorted;

        /**
         * Get which trial # this is
         */
        require "../Actions/Trial.php";
        $trial      = new Trial($this->logger, $this->studentsPath, $this->studentEppn);
        $trials     = $trial->getSubmittedTrialAmounts();
        $trials     = $trials["data"];
        $attemptNum = 1;
        if (array_key_exists("patient_" . $patientId, $trials)) {
            $attemptNum = $trials["patient_" . $patientId] + 1;
        }

        if ($attemptNum > 2) {
            // it's not fatal, so no error
            return array(
                "status" => "ok",
                "data"   => array(
                    "message" => "Maximum submissions reached.",
                ),
            );
        }

        /**
         * Get the results of the submission
         */
        $calculatedResults = $this->__calculateTrialResults($patientId, $attemptData);
        $isCorrect         = false;
        if ($calculatedResults == "") {
            $isCorrect = true;
        }

        /**
         * Get the hash submission time, which will be used as the file name and to timestamp the completion of the attempt
         */
        $submissionTime = time();

        /**
         * Check if the patient dir exists, otherwise make it
         */
        if (!$this->__createDirectory($this->patientsPath . "patient_" . $patientId)) {
            return array(
                "status" => "error",
                "data"   => array(
                    "message" => "Unable to create patient directory",
                ),
            );
        } else {
            if (!$this->__createDirectory($this->patientsPath . "patient_" . $patientId . "/responses")) {
                return array(
                    "status" => "error",
                    "data"   => array(
                        "message" => "Unable to create patient responses directory",
                    ),
                );
            }
        }

        /**
         * Get attempt data amt from the attemptData
         */
        $attemptDataAmt = $this->__getAttemptDataAmt($attemptData);

        /**
         * Get actual data for a patientId
         */
        $actualDataAmt = $this->__getActualPatientResults($patientId);

        /**
         * First save the attempt
         */
        $fullAttemptData = array(
            "student_id"       => $this->studentEppn,
            "patient_id"       => $patientId,
            "trial_number"     => $attemptNum,
            "response"         => $calculatedResults,
            "correct"          => $isCorrect,
            "submitted_time"   => date('Y-m-d', $submissionTime),
            "elapsed_time_sec" => $time,
            "submission"       => $attemptData,
            "submit_amt"       => $attemptDataAmt,
            "actual_amt"       => $actualDataAmt,
        );

        /**
         * Get the incorrect groups
         */
        $incorrectGroups = array();
        for ($i = 1; $i <= count($attemptDataAmt); $i++) {
            if ($attemptDataAmt["group_" . $i] != $actualDataAmt["group_" . $i]) {
                $incorrectGroups[] = $i;
            }
        }

        if (!file_put_contents($this->studentsPath . $this->studentEppn . "/responses/" . $submissionTime . ".json", json_encode($fullAttemptData))) {
            return array(
                "status" => "error",
                "data"   => array(
                    "message" => "Unable to save attempt for student",
                ),
            );
        } else {
            if (!file_put_contents($this->patientsPath . "patient_" . $patientId . "/responses/" . $submissionTime . ".json", json_encode($fullAttemptData))) {
                return array(
                    "status" => "error",
                    "data"   => array(
                        "message" => "Unable to save attempt for patient",
                    ),
                );
            } else {
                return array(
                    "status" => "ok",
                    "data"   => array(
                        "message"   => $calculatedResults,
                        "incorrect" => $incorrectGroups,
                    ),
                );
            }
        }
    }

    /**
     * Get attempt data amts
     */
    private function __getAttemptDataAmt($attemptData)
    {
        $attemptAmts = array();
        $i           = 1;
        foreach ($attemptData as $medications) {
            $attemptAmts["group_" . $i] = 0;

            foreach ($medications as $medication) {
                $attemptAmts["group_" . $i] += doubleval($medication["amt"]);
            }

            $i++;
        }

        return $attemptAmts;
    }

    /**
     * Get the actual patient results to match to the submitted student response
     */
    private function __getActualPatientResults($patientId)
    {
        $data = file_get_contents("../../json/data.json");
        if (!$data) {
            return "Unable to get actual patient results";
        }

        $data                     = json_decode($data);
        $actualResults            = array();
        $patientSolutionResponses = $data[$patientId]->responses;
        for ($i = 1; $i < count($patientSolutionResponses); $i++) {
            $actualResults["group_" . $patientSolutionResponses[$i][0]] = doubleval($patientSolutionResponses[$i][1]);
        }

        return $actualResults;
    }

    /**
     * Returns string(s) stating the results of the trial
     */
    private function __calculateTrialResults($patientId, $attemptData)
    {
        $data = file_get_contents("../../json/data.json");
        if (!$data) {
            return "Unable to calculate results";
        }

        $groupResults = array();

        $groupKeys = array_keys($attemptData);
        for ($i = 0; $i < count($attemptData); $i++) {
            // echo ">>>>" . $groupKeys[$i] . "<<<<";
            $groupId                = substr($groupKeys[$i], strrpos($groupKeys[$i], "_") + 1);
            $groupResults[$groupId] = 0.0;

            $medicationKeys = array_keys($attemptData[$groupKeys[$i]]);
            for ($j = 0; $j < count($attemptData[$groupKeys[$i]]); $j++) {
                // echo ">>" . $medicationKeys[$j] . "<<";
                // var_dump($attemptData[$groupKeys[$i]][$medicationKeys[$j]]);
                $groupResults[$groupId] += doubleval($attemptData[$groupKeys[$i]][$medicationKeys[$j]]["amt"]);
            }
        }

        //var_dump($groupResults);

        /**
         * Compare the responses to the actual patient solution responses
         */
        $responsePerGroup         = array();
        $data                     = json_decode($data);
        $patientSolutionResponses = $data[$patientId]->responses;
        for ($i = 1; $i < count($patientSolutionResponses); $i++) {
            if ($groupResults[$i] == $patientSolutionResponses[$i][1]) {
                // correct, so do nothing
            } elseif ($groupResults[$i] < $patientSolutionResponses[$i][1]) {
                $responsePerGroup[] = $patientSolutionResponses[$i][2];
            } elseif ($groupResults[$i] > $patientSolutionResponses[$i][1]) {
                $responsePerGroup[] = $patientSolutionResponses[$i][3];
            } else {
                $responsePerGroup[] = "error calculating result";
            }
        }

        /**
         * for each string response, concatenate it into a single string to return with
         */
        $response = "";
        foreach ($responsePerGroup as $res) {
            $response = $response . $res . "\n";
        }

        return $response;
    }

    /**
     * Each time someone visits Reconciliation, a request is made to the /student_init endpoint.
     * That endpoint calls this function - which checks if a student dir already exists & their metadata.json file exists as-well.
     * If they don't exist, this file will create them.
     * So that, future calls to write and read to the students directory - shouldn't have to check whether the student exists...
     */
    public function studentFullInit($firstName, $lastName, $admins)
    {
        if (!$this->__createStudentDirectory()) {
            return array(
                "status" => "error",
                "data"   => "unable to create student data directory",
            );
        } else {
            if (!$this->__createStudentResponseDirectory()) {
                return array(
                    "status" => "error",
                    "data"   => "failed to created student responses",
                );
            } else {
                if (!$this->__createStudentMetadataFile($firstName, $lastName)) {
                    return array(
                        "status" => "error",
                        "data"   => "failed to created student metadata",
                    );
                } else {
                    if (in_array($this->studentEppn, $admins)) {
                        return array(
                            "status" => "ok",
                            "data"   => "admin",
                        );
                    }
                    /**
                     * All the directories and files necessary have been successfully created/already exist
                     */
                    return array(
                        "status" => "ok",
                        "data"   => "student",
                    );
                }
            }
        }
    }

    private function __createStudentResponseDirectory()
    {
        return $this->__createDirectory($this->studentsPath . $this->studentEppn . "/responses");
    }

    private function __createStudentDirectory()
    {
        return $this->__createDirectory($this->studentsPath . $this->studentEppn);
    }

    private function __createStudentMetadataFile($firstName, $lastName)
    {
        $studentData = json_encode(array(
            "eppn"       => $this->studentEppn,
            "first_name" => $firstName,
            "last_name"  => $lastName,
        ));

        if (!$this->__createFile($this->studentsPath . $this->studentEppn . "/metadata.json", $studentData)) {
            $this->logger->error("unable to create student metadata file for: " . $this->studentEppn);

            return false;
        } else {
            return true;
        }
    }

    private function __createFile($path, $data = "")
    {
        if (!file_exists($path)) {
            if (!file_put_contents($path, $data)) {
                /**
                 * Failed to write/create the file
                 * This may be a permissions issue
                 */
                $this->logger->error("unable to create file: " . $path);

                return false;
            } else {
                /**
                 * Successfully created the file
                 */
                return true;
            }
        } else {
            /**
             * The file appears to already exist
             */
            return true;
        }
    }

    private function __createDirectory($path, $chmod = 0777)
    {
        if (!is_dir($path)) {
            if (!mkdir($path, $chmod)) {
                /**
                 * Failed to write/create the directory
                 * This may be a permissions issue
                 */
                $this->logger->error("unable to create directory: " . $path);

                return false;
            } else {
                /**
                 * Successfully created the directory
                 */
                return true;
            }
        } else {
            /**
             * The directory already exists
             */
            return true;
        }
    }
}
