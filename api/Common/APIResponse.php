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

    /**
     * Download the supplied csv data as a file
     */
    public function csvDownload($fileName, $data)
    {
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $fileName . '.csv"');
        header('Pragma: no-cache');

        echo $data;
    }

    /**
     * Download the supplied xl data as a file
     */
    public function xlDownload($fileName, $data)
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '.xlsx"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        echo $data;
    }
}
