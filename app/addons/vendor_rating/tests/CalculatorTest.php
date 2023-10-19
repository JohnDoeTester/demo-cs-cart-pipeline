<?php

namespace Tygh\Addons\VendorRating\Tests\Unit;

use Tygh\Addons\VendorRating\Calculator\BackendInterface;
use Tygh\Addons\VendorRating\Calculator\Calculator;
use Tygh\Addons\VendorRating\Calculator\Variable;
use Tygh\Addons\VendorRating\Exception\UnknownVariableException;
use Tygh\Tests\Unit\ATestCase;

class CalculatorTest extends ATestCase
{
    /**
     * @var \Tygh\Addons\VendorRating\Calculator\Calculator
     */
    protected $calculator;

    public function setUp(): void
    {
        $this->calculator = new Calculator($this->createMock(BackendInterface::class));
    }

    public function testInitVariables()
    {
        $variables = $this->calculator->initVariables(
            [
                'x'   => 1,
                'xx'  => 2,
                'xxx' => 3,
            ]
        );

        $this->assertEquals(
            [
                new Variable('a', 'xxx', 3),
                new Variable('b', 'xx', 2),
                new Variable('c', 'x', 1),

            ],
            $variables
        );
    }

    public function testSetVariables()
    {
        $variables = $this->calculator->initVariables(
            [
                'foo'   => 1,
                'bar'  => 2,
                'baz' => 3,
            ]
        );

        $formula = $this->calculator->setVariables("foo + bar + baz", $variables);
        $this->assertEquals("a + b + c + 0", $formula);

        $formula = $this->calculator->setVariables('foo + foo + foo', $variables);
        $this->assertEquals('a + a + a + 0', $formula);

        $this->expectException(UnknownVariableException::class);
        $this->calculator->setVariables('foo * unknown', $variables);
    }

    public function testGetVariables()
    {
        $variables = $this->calculator->extractVariables('fooBar + bar + baz + baz + baz');

        $this->assertEquals(['fooBar', 'bar', 'baz'], $variables);

        $variables = $this->calculator->extractVariables('5 + 4');

        $this->assertEquals([], $variables);
    }
}
