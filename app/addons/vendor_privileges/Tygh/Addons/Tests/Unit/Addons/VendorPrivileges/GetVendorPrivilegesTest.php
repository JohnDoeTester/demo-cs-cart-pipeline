<?php

namespace Tygh\Tests\Unit\Addons\VendorPrivileges;

use Tygh\Addons\VendorPrivileges\Privileges;
use Tygh\Tests\Unit\ATestCase;

class GetVendorPrivilegesTest extends ATestCase
{
    protected $vendor_schema;
    protected $admin_schema;

    protected function setUp(): void
    {
        $this->vendor_schema = include(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'fixtures', 'vendor_schema.php')));
        $this->admin_schema = include(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'fixtures', 'admin_schema.php')));
    }

    public function testGetVendorPrivileges()
    {
        $privileges = new Privileges($this->admin_schema, $this->vendor_schema);

        $vendor_privileges = $privileges->getVendorPrivileges();
        $expected = include(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'fixtures', 'vendor_privileges_expecred.php')));

        $expected_to_actual_diff = array_diff($expected, $vendor_privileges);
        $actual_to_expected_diff = array_diff($vendor_privileges, $expected);

        $this->assertTrue(
            count($expected_to_actual_diff) === 0 && count($actual_to_expected_diff) === 0,
            sprintf("Missing privileges: %s, extra privileges: %s", implode(', ', $expected_to_actual_diff), implode(', ', $actual_to_expected_diff))
        );
    }
}
