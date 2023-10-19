<?php

namespace Tygh\Tests\Unit\Functions\Users;

use Tygh\Tests\Unit\ATestCase;

class GroupUsergroupPrivilegesTest extends ATestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->requireCore('functions/fn.users.php');
        $this->requireMockFunction('__');
        $this->requireMockFunction('fn_preload_lang_vars');
    }

    public function testGroupUsergroupPrivileges()
    {
        $user_groups = require(__DIR__ . '/fixtures/raw_user_groups.php');
        $grouped = fn_group_usergroup_privileges($user_groups);

        $ignore_order = true;
        $expected = require(__DIR__ . '/fixtures/grouped_expected.php');
        $this->assertEquals($expected, $grouped, '', 0.0, 10, $ignore_order);
    }
}