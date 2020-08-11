<?php

/**
 * PHP wrapper for ip-api.com.
 *
 * Resources:
 * https://ip-api.com/docs/api:batch
 */

namespace ChrisUllyott;

class IpApi
{
    /**
     * Fields to request for each IPs.
     *
     * @var array
     */
    private $fields;

    /**
     * The language setting for the query.
     *
     * @var string
     */
    private $lang;

    /**
     * The cURL connection.
     *
     * @var resource
     */
    private $connection;

    /**
     * The API endpoint.
     *
     * @var string
     */
    private static $endpoint = 'http://ip-api.com/batch';

    /**
     * The API user agent.
     *
     * @var string
     */
    private static $userAgent = 'php-ip-api';

    /**
     * The request headers for cURL.
     *
     * @var array
     */
    private static $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    /**
     * The query limit per request.
     */
    private static $limit = 100;

    /**
     * Close the connection on destruct.
     */
    public function __destruct()
    {
        curl_close($this->getConnection());
    }

    /**
     * Set the fields to request.
     *
     * @param array $fields
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Append a request field.
     *
     * @param string $field
     */
    public function addField($field)
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * Get the fields to be requested.
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Get the fields to be requested, as a string.
     *
     * @return string
     */
    public function getFieldString()
    {
        return join(',', $this->fields);
    }

    /**
     * Set the language for this query.
     *
     * @param string $lang
     */
    public function setLanguage($lang)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Get the language setting for this query.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->lang;
    }

    /**
     * Submit a request and decode the response.
     *
     * @param  string|array $query
     * @return object|array
     * @throws Exception
     */
    public function get($query)
    {
        $opts = [
            CURLOPT_POST => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => static::$endpoint,
            CURLOPT_USERAGENT => static::$userAgent,
            CURLOPT_HTTPHEADER => static::$headers,
            CURLOPT_POSTFIELDS => $this->buildPayload($query)
        ];

        $data = json_decode($this->request($opts));

        return !is_array($query) ? $data[0] : $data;
    }

    /**
     * Get the cURL connection.
     *
     * @return resource
     */
    private function getConnection()
    {
        if (!is_resource($this->connection)) {
            $this->connection = curl_init();
        }

        return $this->connection;
    }

    /**
     * Build the JSON payload for this request. Each IP address submitted must
     * individually contain the desired fields and language.
     *
     * @param  string|array $query
     * @return string
     */
    private function buildPayload($query)
    {
        $payload = [];

        foreach ((array) $query as $ip) {
            $i = ['query' => $ip];
            if ($this->fields) $i['fields'] = $this->getFieldString();
            if ($this->lang) $i['lang'] = $this->lang;

            $payload[] = $i;
        }

        if (count($payload) > static::$limit) {
            throw new \Exception("Can't request over " . static::$limit . " items.");
        }

        return json_encode($payload);
    }

    /**
     * Submit a cURL request to the server.
     *
     * @param  array $opts
     * @return array
     * @throws Exception
     */
    private function request($opts)
    {
        $conn = $this->getConnection();
        curl_setopt_array($conn, $opts);
        $resp = curl_exec($conn);

        if (curl_errno($conn)) {
            throw new \Exception(curl_error($conn));
        }

        return $resp;
    }
}
