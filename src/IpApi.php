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
     * The TTL header.
     *
     * @var int
     */
    private $X_TTL;

    /**
     * The rate limit header.
     *
     * @var int
     */
    private $X_RL;

    /**
     * The query limit per request.
     */
    private static $limit = 100;

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
            CURLOPT_HEADER => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => static::$endpoint,
            CURLOPT_USERAGENT => static::$userAgent,
            CURLOPT_HTTPHEADER => static::$headers,
            CURLOPT_POSTFIELDS => $this->buildPayload($query)
        ];

        $data = $this->wait()->request($opts);

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
     * Wait until it's safe to make requests.
     *
     * @return self
     */
    private function wait()
    {
        if ($this->X_RL === 0) {
            sleep($this->X_TTL + 1);
        }

        return $this;
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

        $headerSize = curl_getinfo($conn, CURLINFO_HEADER_SIZE);
        $header = substr($resp, 0, $headerSize);
        $body = substr($resp, $headerSize);

        $this->X_TTL = (int) static::getHeader($header, 'X-Ttl');
        $this->X_RL = (int) static::getHeader($header, 'X-Rl');

        if (curl_errno($conn)) {
            throw new \Exception(curl_error($conn));
        }

        return json_decode($body);
    }

    /**
     * Extract a value from headers by key.
     *
     * @param string $headers
     * @param string $key
     * @return mixed
     */
    private static function getHeader($headers, $key)
    {
        return preg_replace("/.*\n{$key}:\s+([^\n]+)\n.*/s", '$1', $headers);
    }
}
