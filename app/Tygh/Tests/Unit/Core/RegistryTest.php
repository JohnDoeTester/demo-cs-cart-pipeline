<?php


namespace Tygh\Tests\Unit\Core;

use Tygh\Registry;
use Tygh\Tests\Unit\ATestCase;

class RegistryTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    public function testGet()
    {
        $data = array(
            'config' => array(
                'dir' => array(
                    'root' => __DIR__,
                    'lib' => __DIR__ . '/lib/'
                ),
                'forbidden_file_extensions' => array(
                    'php',
                    'php3',
                    'pl',
                    'com',
                    'exe',
                    'bat',
                    'cgi',
                    'htaccess'
                ),
                'debugger_token' => 'debug'
            ),
            'runtime' => array(
                'company_id' => 1,
            ),
            'settings' => array(
                'Company' => array(
                    'company_name' => 'Simtech',
                ),
                'Image_verification' => array(
                    'use_for' => array(
                        'register' => 'Y',
                        'form_builder' => 'Y'
                    )
                )
            )
        );

        foreach ($data as $key => $value) {
            Registry::set($key, $value);
        }

        $this->assertEquals($data['config']['dir']['root'], Registry::get('config.dir.root'));
        $this->assertEquals($data['config']['dir']['lib'], Registry::get('config.dir.lib'));
        $this->assertEquals($data['config']['forbidden_file_extensions'], Registry::get('config.forbidden_file_extensions'));
        $this->assertEquals($data['config']['debugger_token'], Registry::get('config.debugger_token'));
        $this->assertEquals($data['runtime']['company_id'], Registry::get('runtime.company_id'));
        $this->assertEquals($data['settings']['Company'], Registry::get('settings.Company'));
        $this->assertEquals($data['settings']['Company']['company_name'], Registry::get('settings.Company.company_name'));
        $this->assertEquals($data['settings']['Image_verification']['use_for']['form_builder'], Registry::get('settings.Image_verification.use_for.form_builder'));

        $this->assertNull(Registry::get('undefined'));
        $this->assertNull(Registry::get('undefined.undefined'));
        $this->assertNull(Registry::get('config.undefined'));
        $this->assertNull(Registry::get('config.dir.undefined'));
    }

    public function testSet()
    {
        Registry::set('config', array('dir' => array('root' => __DIR__)));

        $this->assertEquals(__DIR__, Registry::get('config.dir.root'));

        Registry::set('config', array('debugger_token' => 'debug'));

        $this->assertNull(Registry::get('config.dir.root'));
        $this->assertNull(Registry::get('config.dir'));
        $this->assertEquals('debug', Registry::get('config.debugger_token'));
    }

    public function testDel()
    {
        Registry::set('config', array('dir' => array('root' => __DIR__)));
        $this->assertTrue(Registry::del('config.dir.root'));

        $this->assertNull(Registry::get('config.dir.root'));

        Registry::set('config', array('dir' => array('root' => __DIR__)));
        $this->assertTrue(Registry::del('config.dir'));

        $this->assertNull(Registry::get('config.dir.root'));
        $this->assertNull(Registry::get('config.dir'));

        Registry::set('config', array('dir' => array('root' => __DIR__)));
        $this->assertTrue(Registry::del('config'));

        $this->assertNull(Registry::get('config.dir.root'));
        $this->assertNull(Registry::get('config.dir'));
        $this->assertNull(Registry::get('config'));

        $this->assertFalse(Registry::del('undefined'));
    }


    public function testIfGet()
    {
        Registry::set('config', array('dir' => array('root' => __DIR__)));

        $this->assertEquals(__DIR__, Registry::ifGet('config.dir.root', '/'));
        $this->assertEquals('/', Registry::ifGet('config.dir.lib', '/'));

        Registry::del('config');

        $this->assertEquals('/', Registry::ifGet('config.dir.root', '/'));
    }
}