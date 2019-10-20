<?php

/**
 * Helpful and widely used functions for formatting data for API responses
 */
class APIResponse
{
    /**
     * Formats and returns the api data format which can be printed out directly
     */
    public function data($data, $status = "ok")
    {
        return json_encode(array(
            "status" => $status,
            "data"   => json_encode($data),
        ));
    }

    /**
     * Formats and returns the api data format which can be printed out directly
     */
    public function message($message, $status = "ok")
    {
        return json_encode(array(
            "status"  => $status,
            "message" => $message,
        ));
    }

    /**
     * Formats and prints out the api data format
     */
    public function printMessage($message, $status = "ok")
    {
        echo $this->message($status, $message);
    }

    /**
     * Parses the values of an array and returns a valid JSON object that can
     * be printed out to the API
     */
    public function arrayToAPIObject($array)
    {
        if (!array_key_exists("status", $array)) {
            $array["status"] = "ok";
        }
        return json_encode($array);
    }
}
