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
     * Fields to be contained in the list.
     *
     * @var array
     */
    private $fields;

    /**
     * All possible item fields.
     *
     * @var array
     */
    private $itemFields;

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
     * Set the fields to be contained in the list.
     *
     * @param array $fields
     * @return self
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Get the fields to be contained in the list. Fall back to item fields.
     *
     * @return array
     */
    private function getFields()
    {
        return $this->fields ? $this->fields : $this->getItemFields();
    }

    /**
     * Get all possible item keys.
     *
     * @return array
     */
    private function getItemFields()
    {
        if (!$this->itemFields) {
            $a = (array) $this->api->get('8.8.8.8');
            $b = (array) $this->api->get('X.X.X.X');
            $this->itemFields = array_keys($a + $b);
        }

        return $this->itemFields;
    }

    /**
     * Iteratively request data and build the CSV file.
     *
     * @return self
     */
    public function build()
    {
        $handle = $this->getFromFileHandle();

        while (!feof($handle)) {
            $lines = [];
            $count = 0;

            while (!feof($handle) && (++$count <= static::$batchLimit)) {
                $lines[] = trim(fgets($handle));
            }

            $this->writeItems($this->requestItems($lines));
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
        fputcsv($handle, $this->getFields());

        return $handle;
    }

    /**
     * Write an individual item to the CSV.
     *
     * @return self
     */
    private function writeItem($item)
    {
        $data = array_fill_keys($this->getFields(), null);
        $data = array_merge($data, (array) $item);
        $data = array_intersect_key($data, array_flip($this->getFields()));

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
