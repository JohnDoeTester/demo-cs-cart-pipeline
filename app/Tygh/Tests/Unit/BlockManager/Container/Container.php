<?php

namespace Tygh\Tests\Unit\BlockManager\Container;

class Container extends \Tygh\BlockManager\Container
{
    public static function getOwned()
    {
        if (fn_allowed_for('MULTIVENDOR')) {
            return array('CONTENT');
        }

        return array();
    }
}