<?php

class Reporting
{
    public function __construct($logger, $studentsPath, $currentEppn)
    {
        $this->logger       = $logger;
        $this->studentsPath = $studentsPath;
        $this->currentEppn  = $currentEppn;
    }

    public function getMasterReport()
    {
        require "Trial.php";
        $trial = new Trial($this->logger, $this->studentsPath, $this->currentEppn);

        $allStudents = $trial->getAllStudents();
        if ($allStudents["status"] != "ok") {
            return array(
                "status" => "error",
                "data"   => "unable to read full student list",
            );
        }

        require "MasterFormat.php";
        $master = new MasterFormat;

        foreach ($allStudents["data"] as $studentEppn) {
            $studentTrial  = new Trial($this->logger, $this->studentsPath, $studentEppn);
            $studentReport = $studentTrial->getFullStudentReport();
            if ($studentReport["status"] != "ok") {
                return $studentReport;
            }

            foreach ($studentReport["data"] as $studentResponse) {
                $line = new MasterFormatLine;
                $line->setCommon(
                    $studentResponse->student_id,
                    $studentResponse->patient_id,
                    $studentResponse->trial_number,
                    $studentResponse->elapsed_time_sec,
                    $studentResponse->submitted_time,
                    json_encode($studentResponse->correct)
                );

                foreach ($studentResponse->submission as $group => $lineResponses) {
                    foreach ($lineResponses as $medLocation => $medProperties) {
                        $medication = substr($medLocation, 0, strrpos($medLocation, "_", 0));
                        $location   = substr($medLocation, strrpos($medLocation, "_", 0) + 1);

                        $amt    = $medProperties->amt;
                        $action = $medProperties->action;

                        $groupCorrect = false;
                        if ($studentResponse->actual_amt->$group == $studentResponse->submit_amt->$group) {
                            $groupCorrect = true;
                        }

                        // now create a line item with this info
                        $newLine = clone $line;
                        $newLine->setPerGroup(
                            $group,
                            ucfirst($medication),
                            $location,
                            $amt,
                            $studentResponse->actual_amt->$group, // actual value solution
                            $action,
                            json_encode($groupCorrect)
                        );
                        /**
                         * Add the line object created above to the master obj
                         */
                        $master->addLine($newLine);
                    }
                }
            }
        }

        return $master->toCSV();
    }
}
