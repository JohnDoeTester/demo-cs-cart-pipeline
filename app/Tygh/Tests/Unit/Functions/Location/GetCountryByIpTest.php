<?php

namespace Tygh\Tests\Unit\Functions\Location;

use Tygh\Registry;
use Tygh\Tests\Unit\ATestCase;

class GetCountryByIpTest extends ATestCase
{
    /** @inheridoc */
    protected function setUp(): void
    {
        $this->requireCore('functions/fn.locations.php');
        Registry::set('config.dir.lib', realpath(__DIR__ . '/../../../../../lib') . '/');
    }

    /**
     * @param string $ip               IP
     * @param string $expected_country ISO country code
     *
     * @return void
     *
     * @dataProvider dpTestGeneral
     */
    public function testGeneral(string $ip, string $expected_country)
    {
        $actual_country = fn_get_country_by_ip(ip2long($ip));

        $this->assertEquals(
            $expected_country,
            $actual_country
        );
    }

    public function dpTestGeneral()
    {
        return [
            ['69.162.81.155', 'US'],
            ['192.199.248.75', 'US'],
            ['162.254.206.227', 'US'],
            ['209.142.68.29', 'US'],
            ['207.250.234.100', 'US'],
            ['184.107.126.165', 'CA'],
            ['206.71.50.230', 'US'],
            ['65.49.22.66', 'US'],
            ['23.81.0.59', 'US'],
            ['207.228.238.7', 'US'],
            ['131.255.7.26', 'AR'],
            ['95.142.107.181', 'NL'],
            ['185.206.224.67', 'DK'],
            ['195.201.213.247', 'DE'],
            ['5.152.197.179', 'GB'],
            ['195.12.50.155', 'ES'],
            ['92.204.243.227', 'FR'],
            ['46.248.187.100', 'PL'],
            ['197.221.23.194', 'ZA'],
            ['47.94.129.116', 'CN'],
            ['47.108.182.80', 'CN'],
            ['8.134.33.121', 'CN'],
            ['47.104.1.98', 'CN'],
            ['47.119.149.69', 'CN'],
            ['103.1.14.238', 'HK'],
            ['103.159.84.142', 'IN'],
            ['106.14.156.213', 'CN'],
            ['110.50.243.6', 'JP'],
            ['223.252.19.130', 'AU'],
            ['101.0.86.43', 'AU'],
            ['185.229.226.83', 'IL'],
        ];
    }
}
