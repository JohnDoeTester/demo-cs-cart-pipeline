<?php

namespace Tygh\Tests\Unit\Addons\AdvancedImport\Readers;

use Tygh\Tests\Unit\ATestCase;
use Tygh\Addons\AdvancedImport\Readers\Xml;

class XmlReaderTest extends ATestCase
{
    /** @var Xml */
    protected $reader;

    protected function setUp(): void
    {
        $filePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ym.xml';
        $this->reader = new Xml($filePath, array('target_node' => 'yml_catalog->shop->offers->offer'));
    }

    public function testParse()
    {
        $result = $this->reader->parse();
        $expected = include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ym.php');
        $this->assertEquals($expected, $result);
    }
}
