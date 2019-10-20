<?php

class Parameters
{
    /**
     * Checks whether parameters have been properly set
     */
    public function paramCheck($a, $b)
    {
        if (is_null($a)) {
            echo json_encode(array(
                "status"  => "error",
                "message" => "invalid parameters 1",
            ));
            exit; // Maybe this is a good idea?
        }
        if (!$this->isInArray(array_keys($a), $b)) {
            echo json_encode(array(
                "status"  => "error",
                "message" => "invalid parameters 2",
            ));
            exit; // Maybe this is a good idea?
        }
    }

    /**
     * Returns true if at least the listed items are in the array
     */
    public function isInArray($a, $b)
    {
        foreach ($b as $item) {
            if (!in_array($item, $a)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true when the values of an array are equal
     * Ordering of array values don't matter.
     */
    public function array_equal($a, $b)
    {
        return (
            is_array($a)
            && is_array($b)
            && count($a) == count($b)
            && array_diff($a, $b) === array_diff($b, $a)
        );
    }

}
