<?php

namespace Tygh\Tests\Unit\BlockManager;

use Tygh\BlockManager\TDeviceAvailabiltiy;

class TestClass
{
    use TDeviceAvailabiltiy;

    protected $hidden_classes = [
        's' => ['s'],
        'm' => ['m1', 'm2'],
    ];
}

class DeviceAvailabilityTest extends \Tygh\Tests\Unit\ATestCase
{
    /**
     * @dataProvider dpTestAvailability
     */
    public function testAvailability($item, $expected_availability)
    {
        $test_obj = new TestClass();
        $this->assertEquals(
            $expected_availability,
            $test_obj->getAvailability($item)
        );
    }

    /**
     * @dataProvider dpTestGetHiddenClass
     */
    public function testGetHiddenClass($device, $expected_class)
    {
        $test_obj = new TestClass();
        $this->assertEquals(
            $expected_class,
            $test_obj->getHiddenClass($device)
        );
    }

    public function dpTestAvailability()
    {
        return [
            [
                [],
                ['s' => true, 'm' => true],
            ],
            [
                ['user_class' => ''],
                ['s' => true, 'm' => true],
            ],
            [
                ['user_class' => 's'],
                ['s' => false, 'm' => true],
            ],
            [
                ['user_class' => 'm1'],
                ['s' => true, 'm' => true],
            ],
            [
                ['user_class' => 'm1 m2'],
                ['s' => true, 'm' => false],
            ],
            [
                ['user_class' => 'm2 m1'],
                ['s' => true, 'm' => false],
            ],
            [
                ['user_class' => 'm2 s m1'],
                ['s' => false, 'm' => false],
            ],
        ];
    }

    public function dpTestGetHiddenClass()
    {
        return [
            [
                's',
                's',
            ],
            [
                'm',
                'm1 m2',
            ],
        ];
    }
}
