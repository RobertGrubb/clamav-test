<?php

namespace Libraries;

/**
 * Connects to a ClamAV Server via TCP
 * and scans a list of files, then returns
 * those results in a resultset.
 */
class Scanner
{
    /**
     * @param string $host
     * The host for the ClamAV Server
     */
    private string $host = 'localhost';

    /**
     * @param int $port
     * The port for the ClamAV Server
     */
    private int $port = 3310;

    /**
     * @param bool $log
     * If the class should log actions to the cli
     */
    private bool $log = false;

    /**
     * @param Appwrite\ClamAV\Network $clam
     * The php-clamav instance
     */
    private object $clam;

    /**
     * @param array $results
     * The results array for the files being scanned
     */
    private array $results = [
        'passed' => 0,
        'failed' => 0,
        'files' => [],
        'logs' => [],
    ];

    /**
     * @param array $files
     * The list of files that will be scanned by ClamAV
     */
    private array $files = [];

    /**
     * The class constructor
     *
     * @param string $host  The host for ClamAV
     * @param int $port  The port for ClamAV
     */
    public function __construct(
        string $host = 'localhost',
        int $port = 3310
    ) {
        /**
         * Checks if the php-clamav exists and is installed
         */
        if (!class_exists("\\Appwrite\\ClamAV\\Network")) {
            throw new \Exception("\\Appwrite\\ClamAV\\Network does not exist. Please intall via composer.");
        }

        /**
         * Host requirement check
         */
        if (!$host) {
            throw new \Exception('Scanner: Host must be provided.');
        }

        /**
         * Port requirement check
         */
        if (!$port) {
            throw new \Exception('Scanner: Port must be provided.');
        }

        /**
         * Set the host and port for the class
         */
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Enables or disables verbose mode to allow logging.
     *
     * @return instance
     */
    public function verbose(): object
    {
        $this->log = true;
        return $this;
    }

    /**
     * Instantiates a new instance of php-clamav
     *
     * @return instnace
     */
    public function instantiate(): object
    {
        try {
            $this->clam = new \Appwrite\ClamAV\Network($this->host, $this->port);
        } catch (\Exception $e) {
            throw new \Exception('Scanner: ' . $e->getMessage());
        }

        /**
         * Run a ping test for ClamAV
         */
        if ($this->clam->ping()) {
            $this->logger('ClamAV ping returned successful response.');
        } else {
            throw new \Exception('Scanner: Ping returned an invalid response.');
        }

        return $this;
    }

    /**
     * Sets the files to be scanned
     *
     * @param array $files  File list to be scanned
     *
     * Note: files can be a specific file, directory, or a directory
     * using glob expressions.
     *
     * Example: test.php, ./files, ./files/*.php
     *
     * @return instance
     */
    public function setFiles(array $files = []): object
    {
        if (!is_array($files)) {
            throw new \Exception('Scanner: Expects files to be an array');
        }

        $this->files = $files;
        return $this;
    }

    /**
     * Performs the scan via ClamAV Server for the files
     * set.
     *
     * @return instance
     */
    public function scan(): object
    {
        if (!count($this->files)) {
            $this->logger('No files to be scanned. Please use setFiles and pass an array of files to scan.');
        }

        /**
         * Iterate through each file and add scan results
         * to the resultset.
         */
        foreach ($this->files as $file) {

            // Check if file is a directory, or is using glob expression
            if (is_dir($file) || str_contains($file, '*')) {

                // Set default array to hold directory files
                $directoryFiles = [];

                /**
                 * Checks if a glob expression is present.
                 *
                 * If it is, use glob to grab the files, if not, use scandir
                 * to get the files.
                 */
                if (str_contains($file, '*')) {
                    $directoryFiles = glob($file);
                } else {
                    $directoryFiles = array_slice(scandir(realpath($file)), 2);

                    if (count($directoryFiles)) {
                        foreach ($directoryFiles as $key => $f) {

                            /**
                             * Prepend the realpath for the directory to the files found
                             */
                            $directoryFiles[$key] = rtrim(realpath($file), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $f;
                        }
                    }
                }

                /**
                 * If directoryFiles is an array and has indexes,
                 * iterate through the array and scan each file.
                 */
                if (is_array($directoryFiles)) {
                    if (count($directoryFiles)) {
                        foreach ($directoryFiles as $directoryFile) {

                            // If is a directory, skip it
                            if (is_dir($directoryFile)) {
                                continue;
                            }

                            $this->scanFile($directoryFile);
                        }
                    }
                }
            } else {
                /**
                 * Check if the file exists, if it does
                 * scan the file with ClamAV
                 */
                if (!file_exists(realpath($file))) {
                    $this->logger($file . ' does not exist.');
                } else {
                    $this->scanFile($file);
                }
            }
        }

        return $this;
    }

    /**
     * Returns the results
     *
     * @param string $format  The format in which the resultset is returned. (array|json)
     *
     * @return array Scan results
     */
    public function results(string $format = 'array'): string | array
    {
        return ($format === 'json' ? json_encode($this->results) : $this->results);
    }

    /**
     * Scans a file and adds to the resultset
     *
     * @param string $filePath The absolute path to the file
     */
    private function scanFile(string $filePath)
    {
        $this->logger('ClamAV is running a scan test against ' . realpath($filePath));
        $res = $this->clam->fileScan(realpath($filePath));
        $this->setFileResults([
            'file' => realpath($filePath),
            'passed' => $res,
        ]);
    }

    /**
     * Adds a result for a scanned file to the resultset.
     */
    private function setFileResults(array $data)
    {
        $this->results['files'][] = $data;

        /**
         * If the passed index exists, increment
         * either passed or failed in the resultset.
         */
        if (isset($data['passed'])) {
            if (is_bool($data['passed'])) {
                if ($data['passed']) {
                    $this->results['passed']++;
                } else {
                    $this->results['failed']++;
                }
            }
        }
    }

    /**
     * Simple logger function for cli
     * @param array|object|string $data To be logged to cli
     */
    private function logger(array | object | string $data): bool
    {
        if (is_array($data) || is_object($data)) {
            if ($this->log) {
                echo "Scanner:" . PHP_EOL;
                print_r($data);
            }
        } else {
            if ($this->log) {
                echo "Scanner: " . $data . PHP_EOL;
            }
            $this->results['logs'][] = "Scanner: " . $data;
        }

        return true;
    }
}