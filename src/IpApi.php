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
     * The request headers.
     *
     * @var array
     */
    private static $headers = [
        'User-Agent: PHP-IP-API',
        'Content-Type: application/json',
        'Accept: application/json'
    ];

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
        $payload = $this->buildPayload($query);
        $data = $this->wait()->request($payload);

        return !is_array($query) ? $data[0] : $data;
    }

    /**
     * Build the payload data for this request. Each IP address submitted must
     * individually contain the desired fields and language.
     *
     * @param  string|array $query
     * @return array
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

        return $payload;
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
     * Submit a request to the server.
     *
     * @param  array $payload
     * @return array
     * @throws Exception
     */
    private function request(array $payload)
    {
        $response = \Requests::post(
            static::$endpoint,
            static::$headers,
            json_encode($payload)
        );

        $this->X_TTL = (int) $response->headers['x-ttl'];
        $this->X_RL = (int) $response->headers['x-rl'];

        return json_decode($response->body);
    }
}
