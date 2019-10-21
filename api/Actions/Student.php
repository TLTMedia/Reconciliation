<?php

class Student
{
    public function __construct($logger, $studentsPath, $studentEppn)
    {
        $this->logger       = $logger;
        $this->studentsPath = $studentsPath;
        $this->studentEppn  = $studentEppn;
    }

    /**
     * Save a students submission and calculate the outcome
     */
    public function submitStudentAttempt($patientId, $attemptData)
    {
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
                "data"   => "Maximum submissions reached.",
            );
        }

        /**
         * First save the attempt
         */
        $fullAttemptData = array(
            "patient_id"   => $patientId,
            "trial_number" => $attemptNum,
            "submission"   => $attemptData,
        );

        if (!file_put_contents($this->studentsPath . $this->studentEppn . "/responses/" . time() . ".json", json_encode($fullAttemptData))) {
            return array(
                "status" => "error",
                "data"   => "unable to save attempt",
            );
        } else {
            return array(
                "status" => "ok",
                "data"   => $this->__calculateTrialResults($patientId, $attemptData),
            );
        }
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
    public function studentFullInit($firstName, $lastName)
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
                    /**
                     * All the directories and files necessary have been successfully created/already exist
                     */
                    return array(
                        "status" => "ok",
                        "data"   => "student ok",
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
