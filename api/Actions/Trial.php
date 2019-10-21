<?php

class Trial
{
    public function __construct($logger, $studentsPath, $studentEppn)
    {
        $this->logger       = $logger;
        $this->studentsPath = $studentsPath;
        $this->studentEppn  = $studentEppn;
    }

    /**
     * Get the current users' (eppn) current trial # for the specified patientId
     */
    public function getSubmittedTrialAmounts()
    {
        $allRawResponses = $this->__getRawStudentResponses();
        if (!$allRawResponses && !is_array($allRawResponses)) {
            return array(
                "status" => "error",
                "data"   => "unable to get all student responses",
            );
        }

        $trialAmounts = array();
        foreach ($allRawResponses as $rawResponse) {
            if (array_key_exists("patient_" . $rawResponse->patient_id, $trialAmounts)) {
                $trialAmounts["patient_" . $rawResponse->patient_id]++;
            } else {
                $trialAmounts["patient_" . $rawResponse->patient_id] = 1;
            }
        }

        return array(
            "status" => "ok",
            "data"   => $trialAmounts,
        );
    }

    /**
     * Returns a JSON object with all the students responses from every patient and every trial they have ever submitted
     */
    private function __getRawStudentResponses()
    {
        $allResponseFiles = $this->__getDirectoryContents($this->studentsPath . $this->studentEppn . "/responses/", true);
        $responseDataObj  = array();
        foreach ($allResponseFiles as $responseFile) {
            $fileData = file_get_contents($responseFile);
            if (!$fileData) {
                $this->logger->error("unable to read/open a response file: " . $responseFile);

                return false;
            }

            $fileData          = json_decode($fileData);
            $responseDataObj[] = $fileData;
        }
        return $responseDataObj;
    }

    private function __getDirectoryContents($path, $returnWithPath = false)
    {
        $items = glob($path . '*');
        if ($returnWithPath) {
            return $items;
        }

        $cleanedItems = array();
        foreach ($items as $item) {
            $cleanedItems[] = str_replace($path, "", $item);
        }
        return $cleanedItems;
    }
}
