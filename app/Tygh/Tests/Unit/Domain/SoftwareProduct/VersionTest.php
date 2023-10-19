<?php

use Tygh\Domain\SoftwareProduct\Version;
use Tygh\Tests\Unit\ATestCase;

class VersionTest extends ATestCase
{
    /**
     * @dataProvider getGreaterThanData
     */
    public function testGreaterThan($version1, $version2, $expected)
    {
        $version = new Version($version1);

        $this->assertEquals($expected, $version->greaterThan(new Version($version2)));
    }

    /**
     * @dataProvider getGreaterOrEqualsToData
     */
    public function testGreaterOrEqualsTo($version1, $version2, $expected)
    {
        $version = new Version($version1);

        $this->assertEquals($expected, $version->greaterOrEqualsTo(new Version($version2)));
    }

    /**
     * @dataProvider getLowerThanData
     */
    public function testLowerThan($version1, $version2, $expected)
    {
        $version = new Version($version1);

        $this->assertEquals($expected, $version->lowerThan(new Version($version2)));
    }

    /**
     * @dataProvider getLowerOrEqualsToData
     */
    public function testLowerOrEqualsTo($version1, $version2, $expected)
    {
        $version = new Version($version1);

        $this->assertEquals($expected, $version->lowerOrEqualsTo(new Version($version2)));
    }

    /**
     * @dataProvider getEqualsToData
     */
    public function testEqualsTo($version1, $version2, $expected)
    {
        $version = new Version($version1);

        $this->assertEquals($expected, $version->equalsTo(new Version($version2)));
    }

    public function getGreaterThanData()
    {
        return array(
            array('4.5.1', '4.5.2', false),
            array('4.5.2', '4.5.1', true),
            array('4.5.1.SP1', '4.5.1', true),
            array('4.5.1.SP1', '4.5.2', false),
            array('4.5.1.SP2', '4.5.1.SP1', true),
            array('4.5.1.SP2', '4.5.1.SP3', false),
            array('4.5.1.SP2', '4.5.2', false),
            array('4.5.1.SP2', '4.5.2.SP1', false),
        );
    }

    public function getGreaterOrEqualsToData()
    {
        $data = $this->getGreaterThanData();
        $data[] = array('4.5.1', '4.5.1', true);
        $data[] = array('4.5.1.SP1', '4.5.1.SP1', true);

        return $data;
    }

    public function getLowerThanData()
    {
        return array(
            array('4.5.1', '4.5.2', true),
            array('4.5.2', '4.5.1', false),
            array('4.5.1.SP1', '4.5.1', false),
            array('4.5.1.SP1', '4.5.2', true),
            array('4.5.1.SP2', '4.5.1.SP1', false),
            array('4.5.1.SP2', '4.5.1.SP3', true),
            array('4.5.1.SP2', '4.5.2', true),
            array('4.5.1.SP2', '4.5.2.SP1', true),
        );
    }

    public function getLowerOrEqualsToData()
    {
        $data = $this->getLowerThanData();
        $data[] = array('4.5.1', '4.5.1', true);
        $data[] = array('4.5.1.SP1', '4.5.1.SP1', true);

        return $data;
    }

    public function getEqualsToData()
    {
        return array(
            array('4.5.1', '4.5.1', true),
            array('4.5.1', '4.5.2', false),
            array('4.5.1.SP1', '4.5.1.SP1', true),
            array('4.5.1.SP1', '4.5.1.SP2', false),
            array('4.5.1.SP1', '4.5.2.SP1', false),
        );
    }
}