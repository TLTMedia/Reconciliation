<?php

class MasterFormat
{
    public function __construct()
    {
        // array of MasterFormatLine
        $this->lines = array();
    }

    /**
     * Where $line is type MasterFormatLine
     */
    public function addLine($line)
    {
        $this->lines[] = $line;
    }

    /**
     * Returns all the objects in this->lines to a CSV string, with headers
     */
    public function toCSV()
    {
        $fullCSV = "";
        foreach ($this->lines as $line) {
            $fullCSV .= "\n" . $line->toCSV();
        }

        // don't remove leading new line since we're going to add a header to the start.
        // add headers
        $headers = array(
            "student",
            "patient",
            "trial",
            "elapsed time",
            "submission date",
            "trial correct",
            "group",
            "entry (medication)",
            "location",
            "submitted value",
            "actual value",
            "action",
            "group correct",
        );

        return implode(",", $headers) . $fullCSV;
    }
}

class MasterFormatLine
{
    public function __construct()
    {
        // $this->student;
        // $this->patient;
        // $this->trial;
        // $this->group;
        // $this->entry;
        // $this->location;
        // $this->value_submitted;
        // $this->value_actual;
        // $this->action;
        // $this->group_correct;
        // $this->elapsed_time;
        // $this->date;
        // $this->trial_correct;
    }

    public function setCommon(
        $student,
        $patient,
        $trial,
        $elapsedTime,
        $date,
        $trialCorrect
    ) {
        $this->student       = $student;
        $this->patient       = $patient;
        $this->trial         = $trial;
        $this->elapsed_time  = $elapsedTime;
        $this->date          = $date;
        $this->trial_correct = $trialCorrect;
    }

    public function setPerGroup(
        $group,
        $entry,
        $location,
        $valueSubmitted,
        $valueActual,
        $action,
        $groupCorrect
    ) {
        $this->group           = $group;
        $this->entry           = $entry;
        $this->location        = $location;
        $this->value_submitted = $valueSubmitted;
        $this->value_actual    = $valueActual;
        $this->action          = $action;
        $this->group_correct   = $groupCorrect;
    }

    public function toCSV()
    {
        $fullCSV = "";
        foreach ($this as $key => $value) {
            $fullCSV .= "," . $value;
        }

        return substr($fullCSV, 1);
    }
}
