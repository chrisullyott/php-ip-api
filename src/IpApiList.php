<?php

/**
 * Process a list of IP addresses.
 */

namespace ChrisUllyott;

class IpApiList
{
    /**
     * The file path to read from.
     *
     * @var string
     */
    private $fromFile;

    /**
     * The file path to write to.
     *
     * @var string
     */
    private $toFile;

    /**
     * The "from" file handle.
     *
     * @var resource
     */
    private $fromFileHandle;

    /**
     * The "to" file handle.
     *
     * @var resource
     */
    private $toFileHandle;

    /**
     * All possible item keys.
     *
     * @var array
     */
    private $itemKeys;

    /**
     * The IpApi instance.
     *
     * @var IpApi
     */
    private $api;

    /**
     * The query limit per request.
     *
     * @var integer
     */
    private static $batchLimit = 100;

    /**
     * The allowed number of requests per minute.
     *
     * @var integer
     */
    private static $rateLimit = 45;

    /**
     * Constructor.
     *
     * @param string $fromFile
     */
    public function __construct($fromFile)
    {
        $this->fromFile = $fromFile;
        $this->toFile = static::replaceExtension($fromFile, 'csv');
        $this->api = new IpApi();
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        fclose($this->getFromFileHandle());
        fclose($this->getToFileHandle());
    }

    /**
     * Iteratively request data and build the CSV file.
     *
     * @return self
     */
    public function build()
    {
        $handle = $this->getFromFileHandle();

        // Try to be nice.
        $sleepFor = (int) ceil(60 / static::$rateLimit) + 1;

        while (!feof($handle)) {
            $lines = [];
            $count = 0;

            while (!feof($handle) && (++$count <= static::$batchLimit)) {
                $lines[] = trim(fgets($handle));
            }

            $this->writeItems($this->requestItems($lines));

            sleep($sleepFor);
        }

        return $this;
    }

    /**
     * Request data for an array of IP addresses.
     *
     * @param  array $ips
     * @return array
     */
    private function requestItems(array $ips)
    {
        return $this->api->get(array_filter($ips));
    }

    /**
     * Get all possible item keys.
     *
     * @return array
     */
    private function getItemKeys()
    {
        if (!$this->itemKeys) {
            $a = (array) $this->api->get('8.8.8.8');
            $b = (array) $this->api->get('X.X.X.X');
            $this->itemKeys = array_merge(array_keys($a), array_keys($b));
        }

        return $this->itemKeys;
    }

    /**
     * Get the "from" file handle.
     *
     * @return resource
     */
    private function getFromFileHandle()
    {
        if (!is_resource($this->fromFileHandle)) {
            $this->fromFileHandle = fopen($this->fromFile, 'r');
        }

        return $this->fromFileHandle;
    }

    /**
     * Get the "to" file handle.
     *
     * @return resource
     */
    private function getToFileHandle()
    {
        if (!is_resource($this->toFileHandle)) {
            $this->toFileHandle = $this->createCsvFile();
        }

        return $this->toFileHandle;
    }

    /**
     * Create a new CSV file (overwrite if exists) and return its handle.
     *
     * @return resource
     */
    private function createCsvFile()
    {
        !file_exists($this->toFile) || unlink($this->toFile);

        $handle = fopen($this->toFile, 'a');
        fputcsv($handle, $this->getItemKeys());

        return $handle;
    }

    /**
     * Write an individual item to the CSV.
     *
     * @return self
     */
    private function writeItem($item)
    {
        $data = array_fill_keys($this->getItemKeys(), null);
        $data = array_merge($data, (array) $item);
        fputcsv($this->getToFileHandle(), $data);

        return $this;
    }

    /**
     * Write many items to the CSV.
     *
     * @param  array $items
     * @return self
     */
    private function writeItems(array $items)
    {
        foreach ($items as $item) {
            $this->writeItem($item);
        }

        return $this;
    }

    /**
     * Replace a file's extension with another one.
     *
     * @param  string $filename
     * @param  string $newExtension
     * @return string
     */
    private static function replaceExtension($filename, $newExtension)
    {
        $oldExtension = pathinfo($filename, PATHINFO_EXTENSION);

        return preg_replace("/\.{$oldExtension}$/", ".{$newExtension}", $filename);
    }
}
