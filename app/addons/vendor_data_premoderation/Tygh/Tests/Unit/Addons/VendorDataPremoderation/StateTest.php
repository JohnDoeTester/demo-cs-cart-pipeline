<?php

namespace Tygh\Tests\Unit\Addons\VendorDataPremoderation;

use Tygh\Addons\VendorDataPremoderation\State;
use Tygh\Exceptions\DeveloperException;
use Tygh\Tests\Unit\ATestCase;

class StateTest extends ATestCase
{
    /**
     * @var \Tygh\Addons\VendorDataPremoderation\State
     */
    protected $state;

    public function setUp(): void
    {
        $this->state = new State(
            [
                'src1' => [],
                'src2' => [
                    [
                        'field1' => 1,
                        'field2' => 2,
                        'field3' => 3,
                    ],
                ],
            ]
        );
    }
    public function testGetSources()
    {
        $this->assertEquals($this->state->getSources(), ['src1', 'src2']);
    }

    public function testGetSourceSchema()
    {
        $this->assertEquals($this->state->getSourceSchema('src2'), ['field1', 'field2', 'field3']);
        $this->assertEquals($this->state->getSourceSchema('src1'), []);
    }

    public function testException()
    {
        $this->expectException(DeveloperException::class);
        $this->state->getSourceSchema('missing_source');
    }
}
