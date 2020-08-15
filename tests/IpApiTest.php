<?php

/**
 * Tests for IpApi.
 */

use ChrisUllyott\IpApi;
use PHPUnit\Framework\TestCase;

class IpApiTest extends TestCase
{
    /**
     * @test Fetches an IP from the API.
     */
    public function gets_ip()
    {
        $api = new IpApi();
        $obj = $api->get('8.8.8.8');

        $this->assertObjectHasAttribute('country', $obj);
    }

    /**
     * @test Fetches multiple IPs from the API.
     */
    public function gets_ips()
    {
        $api = new IpApi();
        $items = $api->get(['8.8.8.8', '198.35.26.96']);

        $this->assertSame(count($items), 2);
    }

    /**
     * @test Properly handles invalid IPs.
     */
    public function handles_invalid_ips()
    {
        $api = new IpApi();
        $obj = $api->get('8.8.8.X');

        $this->assertSame($obj->status, 'fail');
    }
}
