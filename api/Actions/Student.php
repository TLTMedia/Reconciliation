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
