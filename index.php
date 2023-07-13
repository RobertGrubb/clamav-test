<?php

/**
 * Require files
 */
require_once 'vendor/autoload.php';

/**
 * Setup files to be scanned
 */
$files = [
    './files',
    './files/test',
];

/**
 * Set the files, run the scan, return the results.
 */
try {
    /**
     * Instantiates a new instance of the scanner
     *
     * Note: Make sure if using AWS EC2, the inbound port
     * for 3310 enables permissions for the ip the request is
     * coming from.
     */
    $scanner = new \Libraries\Scanner('localhost', 3310);

    /**
     * Perform the scan and return the results
     */
    $results = $scanner
        ->verbose() // Optional
        ->instantiate() // Instantiates the connection to ClamAV Server
        ->setFiles($files) // Sets files to be scanned
        ->scan() // Scan the files
        ->results(); // Return the results

    /**
     * WIll return the following keys:
     *
     * passed (int of files that passed)
     * failed (int of files that failed)
     * files (List of files that were scanned with status of scan)
     * log (Logs from the scanner if verbose is enabled)
     */
    var_dump($results);
} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
} catch (\TypeError $e) {
    echo $e->getMessage() . PHP_EOL;
}