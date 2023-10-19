<?php


namespace Tygh\Tests\Unit\Mailer\MessageBuilders;


use Tygh\Mailer\Message;
use Tygh\Storefront\Repository;
use Tygh\Template\Mail\Template;
use Tygh\Tests\Unit\ATestCase;

class DBTemplateMessageBuilderTest extends ATestCase
{
    public $runTestInSeparateProcess = true;

    public $backupGlobals = false;

    public $preserveGlobalState = false;

    protected $repository;

    protected $renderer;

    protected $message_style_formatter;

    protected $storefront_repository;

    public function setUp(): void
    {
        define('CART_LANGUAGE', 'en');

        $this->repository = $this->getMockBuilder('\Tygh\Template\Mail\Repository')
            ->disableOriginalConstructor()
            ->setMethods(['findActiveByCodeAndArea'])
            ->getMock();

        $this->repository->method('findActiveByCodeAndArea')->willReturnCallback(
            [$this, 'templateFindActiveByCodeAndArea']
        );

        $this->renderer = $this->getMockBuilder('\Tygh\Template\Renderer')
            ->disableOriginalConstructor()
            ->setMethods(['render', 'renderTemplate'])
            ->getMock();

        $this->renderer->method('render')->willReturnCallback([$this, 'rendererRender']);
        $this->renderer->method('renderTemplate')->willReturnCallback([$this, 'rendererRenderTemplate']);

        $this->message_style_formatter = $this->getMockBuilder('\Tygh\Mailer\MessageStyleFormatter')
            ->setMethods(['convert'])
            ->getMock();

        $this->message_style_formatter->method('convert')->willReturnCallback([$this, 'styleFormatterConvert']);
        $this->requireMockFunction('fn_allowed_for');
        $this->requireMockFunction('fn_disable_live_editor_mode');
        $this->requireMockFunction('fn_filter_company_data_by_profile_fields');
        $this->requireMockFunction('fn_get_storefront_protocol');

        $this->storefront_repository = $this->createMock(Repository::class);
    }

    public function templateFindActiveByCodeAndArea($code, $area)
    {
        $result = new Template();

        switch ($code) {
            case 'example0':
                $result->setTemplate('example0_body');
                $result->setSubject('example0_subject');
                $result->setParams(['attach_invoice' => 'Y']);
                break;
            case 'example1':
                $result->setTemplate('example1_body');
                $result->setSubject('example1_subject');
                break;
        }

        $result->setCode($code);

        return $result;
    }

    public function rendererRenderTemplate(Template $template)
    {
        return $template->getTemplate() . ' rendered';
    }

    public function rendererRender($template, $data)
    {
        return $template . ' rendered';
    }

    public function styleFormatterConvert(Message $message)
    {
        $message->setBody($message->getBody() . ' formatted');
    }

    public function testCreateMessage()
    {
        $builder = new DBTemplateMessageBuilder(
            $this->renderer,
            $this->repository,
            $this->message_style_formatter,
            [],
            $this->storefront_repository
        );

        // Test create empty message
        $message = $builder->createMessage([], 'C', 'en');

        $this->assertEmpty($message->getBody());
        $this->assertEmpty($message->getSubject());

        // Test create message by db template example0
        $message = $builder->createMessage(
            [
                'template_code' => 'example0'
            ],
            'C',
            'en'
        );

        $this->assertEquals('example0', $message->getId());
        $this->assertEquals(['attach_invoice' => 'Y'], $message->getParams());
        $this->assertEquals('example0_body rendered formatted', $message->getBody());
        $this->assertEquals('example0_subject rendered', $message->getSubject());

        // Test create message by db template example1
        $message = $builder->createMessage(
            [
                'template_code' => 'example1'
            ],
            'C',
            'en'
        );

        $this->assertEquals('example1', $message->getId());
        $this->assertEquals('example1_body rendered formatted', $message->getBody());
        $this->assertEquals('example1_subject rendered', $message->getSubject());

        // Test create message by template object
        $email_template = new Template();
        $email_template->setTemplate('example2_body');
        $email_template->setSubject('example2_subject');
        $email_template->setCode('example2');

        $message = $builder->createMessage(
            [
                'template_code' => $email_template->getCode(),
                'template'      => $email_template
            ],
            'C',
            'en'
        );

        $this->assertEquals('example2', $message->getId());
        $this->assertEquals('example2_body rendered formatted', $message->getBody());
        $this->assertEquals('example2_subject rendered', $message->getSubject());
    }
}
