# IpApi

Fetch geolocation data for IP addresses from [ip-api.com](https://ip-api.com/).

# Usage

### Installation

```bash
$ composer require chrisullyott/php-ip-api
```

### Instantiation

```php
$api = new ChrisUllyott\IpApi();

// Output language and fields (optional):
$api->setLanguage('en');
$api->setFields(['query', 'country', 'city']);
```

### Request one

```php
$response = $api->get('91.198.174.192');
print_r($response);
```

```
stdClass Object
(
    [country] => Netherlands
    [city] => Amsterdam
    [query] => 91.198.174.192
)
```

### Request many

```php
$ips = [
    '100.142.29.254',
    '100.142.39.218'
];

$response = $api->get($ips);
print_r($response);
```

```
Array
(
    [0] => stdClass Object
        (
            [country] => United States
            [city] => Chicago
            [query] => 100.142.29.254
        )

    [1] => stdClass Object
        (
            [country] => United States
            [city] => Chicago
            [query] => 100.142.39.218
        )
)
```
