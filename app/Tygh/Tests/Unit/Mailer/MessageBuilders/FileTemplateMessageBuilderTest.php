<?php


namespace Tygh\Tests\Unit\Mailer\MessageBuilders;


use Tygh\SmartyEngine\Core;
use Tygh\Storefront\Repository;
use Tygh\Tests\Unit\ATestCase;

class FileTemplateMessageBuilderTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    /** @var  \PHPUnit\Framework\MockObject\MockObject|Core */
    protected $smarty;

    public function setUp(): void
    {
        define('CART_LANGUAGE', 'en');
        define('AREA', 'C');
        $this->smarty = $this->getMockBuilder('\Tygh\SmartyEngine\Core')
            ->setMethods(array('displayMail'))
            ->getMock();

        $this->smarty->method('displayMail')->willReturnCallback(array($this, 'smartyRender'));
        $this->requireMockFunction('fn_disable_live_editor_mode');
        $this->requireMockFunction('fn_filter_company_data_by_profile_fields');
        $this->requireMockFunction('fn_get_storefront_protocol');
    }

    public function smartyRender($template, $to_screen, $area, $company_id, $lang_code)
    {
        $result = '';

        switch ($template) {
            case 'example.tpl':
                $result = 'example_body';
                break;
            case 'example_subj.tpl':
                $result = 'example_subj';
                break;
        }

        return $result;
    }

    public function testCreateMessage()
    {
        $builder = new FileTemplateMessageBuilder($this->smarty, array(), $this->createMock(Repository::class));

        $message = $builder->createMessage(
            array(
                'to' => 'example@example.com',
                'from' => 'example@example.com',
            ),
            'C',
            'en'
        );

        $this->assertEmpty($message->getBody());
        $this->assertEmpty($message->getSubject());


        $message = $builder->createMessage(
            array(
                'to' => 'example@example.com',
                'from' => 'example@example.com',
                'tpl' => 'example.tpl',
            ),
            'C',
            'en'
        );

        $this->assertEquals('example.tpl', $message->getId());
        $this->assertEquals('example_body', $message->getBody());
        $this->assertEquals('example_subj', $message->getSubject());
    }
}