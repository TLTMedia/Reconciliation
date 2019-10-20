<?php

class Trial
{
    public function __construct($logger, $studentEppn)
    {
        $this->logger      = $logger;
        $this->studentEppn = $studentEppn;
    }

    /**
     * Get the current users' (eppn) current trial # for the specified patientId
     */
    public function getTrialNumber($patientId)
    {
        return 1;
    }
}
