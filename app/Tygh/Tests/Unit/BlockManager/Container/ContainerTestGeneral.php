<?php

namespace Tygh\Tests\Unit\BlockManager\Container;

abstract class ContainerTestGeneral extends \Tygh\Tests\Unit\ATestCase
{
    protected $productEdition;

    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    protected function setUp(): void
    {
        define('AREA', 'A');
        define('TIME', time());

        if ($this->productEdition) {
            define('PRODUCT_EDITION', $this->productEdition);
        }

        $this->requireMockFunction('fn_allowed_for');
        $this->requireMockFunction('__');
    }

    /**
     * @dataProvider getTestData
     */
    public function testUsesDefaultContent($company_id, $container, $dynamic_object, $expected)
    {
        $actual = Container::usesDefaultContent($company_id, $container, $dynamic_object);

        $this->assertEquals($expected['uses_default_content'], $actual);
    }

    /**
     * @dataProvider getTestData
     */
    public function testHasDisplayableContent($company_id, $container, $dynamic_object, $expected)
    {
        $actual = Container::hasDisplayableContent($company_id, $container, $dynamic_object);

        $this->assertEquals($expected['has_displayable_content'], $actual);
    }

    /**
     * @dataProvider getTestData
     */
    public function testCanBeResetToDefault($company_id, $container, $dynamic_object, $expected)
    {
        $actual = Container::canBeResetToDefault($company_id, $container, $dynamic_object);

        $this->assertEquals($expected['can_be_reset_to_default'], $actual);
    }

    /**
     * @dataProvider getTestData
     */
    public function testGetLinkedMessage($company_id, $container, $dynamic_object, $expected)
    {
        $actual = Container::getLinkedMessage($company_id, $container, $dynamic_object);

        $this->assertEquals($expected['linked_message'], $actual);
    }

    public function getTestData()
    {
        return array();
    }

}
