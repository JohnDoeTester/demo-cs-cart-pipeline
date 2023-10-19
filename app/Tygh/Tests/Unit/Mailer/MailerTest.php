<?php
namespace Tygh\Tests\Unit\Mailer;


use Tygh\Mailer\IMessageBuilder;
use Tygh\Mailer\IMessageBuilderFactory;
use Tygh\Mailer\ITransport;
use Tygh\Mailer\ITransportFactory;
use Tygh\Mailer\ICompanyTransportFactory;
use Tygh\Mailer\Mailer;
use Tygh\Mailer\Message;
use Tygh\Mailer\SendResult;
use Tygh\Tests\Unit\ATestCase;

class MailerTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;
    protected $transport_factory;
    protected $message_builder_factory;
    protected $transport_settings;

    protected function setUp(): void
    {
        $this->requireMockFunction('fn_set_hook');
        $this->requireMockFunction('fn_set_notification');
        $this->transport_factory = new TransportFactory();
        $this->message_builder_factory = new MessageBuilderFactory();
    }


    /**
     * @param $params
     * @param $transport_settings
     * @param $allow_db_templates
     * @param $expected
     * @dataProvider dpSend
     */
    public function testSend($params, $transport_settings, $allow_db_templates, $expected)
    {
        $mailer = new Mailer($this->message_builder_factory, $this->transport_factory, $transport_settings, $allow_db_templates, 'en');

        $this->assertEquals($expected, $mailer->send($params));
    }

    public function dpSend()
    {
        $params = array(
            'to' => 'to@example.com',
            'from' => 'from@example.com',
            'body' => 'body',
            'template_code' => 'example',
            'tpl' => 'undefined.tpl',
            'company_id' => 1
        );

        $message = new Message();
        $message->setCompanyId($params['company_id']);
        $message->setFrom($params['from']);
        $message->addTo($params['to']);
        $message->setBody($params['body']);

        return array(
            array(
                array(
                    'to' => 'to@example.com',
                    'from' => 'from@example.com',
                    'body' => 'body',
                    'subject' => 'subject'
                ),
                array('result' => true),
                true,
                true,
            ),
            array( //Transport not sent
                array(
                    'to' => 'to@example.com',
                    'from' => 'from@example.com',
                    'body' => 'body',
                    'subject' => 'subject'
                ),
                array('result' => false),
                true,
                false,
            ),
            array( //undefined to address
                array(
                    'from' => 'from@example.com',
                    'body' => 'body',
                    'subject' => 'subject'
                ),
                array('result' => true),
                true,
                false,
            ),
            array( //undefined from address
                array(
                    'to' => 'to@example.com',
                    'body' => 'body',
                    'subject' => 'subject'
                ),
                array('result' => true),
                true,
                false,
            ),
            array( //empty body
                array(
                    'to' => 'to@example.com',
                    'from' => 'from@example.com',
                    'subject' => 'subject'
                ),
                array('result' => true),
                true,
                false,
            ),
            array( //db template and empty body and disallow db templates
                array(
                    'to' => 'to@example.com',
                    'from' => 'from@example.com',
                    'template_code' => 'example'
                ),
                array('result' => true),
                false,
                false,
            ),
            array( //db template and empty body and allow db templates
                array(
                    'to' => 'to@example.com',
                    'from' => 'from@example.com',
                    'template_code' => 'example'
                ),
                array('result' => true),
                true,
                true,
            ),
            array( //db template and file tpl and empty body and disallow db templates
                array(
                    'to' => 'to@example.com',
                    'from' => 'from@example.com',
                    'template_code' => 'example',
                    'tpl' => 'example.tpl'
                ),
                array('result' => true),
                false,
                true,
            ),
            array( //db template and file tpl and empty body and disallow db templates and undefined tpl
                array(
                    'to' => 'to@example.com',
                    'from' => 'from@example.com',
                    'template_code' => 'example',
                    'tpl' => 'undefined.tpl'
                ),
                array('result' => true),
                false,
                false,
            ),
            array( //check transport by company id
                array(
                    'to' => 'to@example.com',
                    'from' => 'from@example.com',
                    'template_code' => 'example',
                    'tpl' => 'undefined.tpl',
                    'company_id' => 1
                ),
                array('result' => false),
                true,
                true,
            ),
            array( //check transport by company id to Message
                $message,
                array('result' => false),
                true,
                true,
            ),
        );
    }

    public function testSendSeparateTransport()
    {
        $mailer = new Mailer($this->message_builder_factory, $this->transport_factory, array('result' => false), true, 'en');

        $this->assertFalse($mailer->send(array(
            'to' => 'to@example.com',
            'from' => 'from@example.com',
            'body' => 'body',
            'subject' => 'subject'
        )));

        $this->assertTrue($mailer->send(
            array(
                'to' => 'to@example.com',
                'from' => 'from@example.com',
                'body' => 'body',
                'subject' => 'subject'
            ),
            'C', 'en', array('result' => true)
        ));
    }

    /**
     * @dataProvider dpSendTransportByCompanyId
     */
    public function testGetTransportByCompanyId($params, $expected)
    {
        $mailer = new Mailer($this->message_builder_factory, $this->transport_factory, array('result' => false), false, 'en');

        $transport = $mailer->getTransportByCompanyId($params['company_id']);

        $this->assertEquals($expected, $transport);
    }

    public function dpSendTransportByCompanyId()
    {
        $transport_settings = array(
            'mailer_send_method' => 'smtp',
            'mailer_smtp_host' => 'test',
            'mailer_smtp_username' => 'test',
            'mailer_smtp_password' => 'test',
            'mailer_smtp_ecrypted_connection' => false,
            'result' => true
        );
        $transport_company1 = new Transport($transport_settings);

        $transport_settings = array(
            'mailer_send_method' => 'smtp',
            'mailer_smtp_host' => 'test2',
            'mailer_smtp_username' => 'test2',
            'mailer_smtp_password' => 'test2',
            'mailer_smtp_ecrypted_connection' => false,
            'result' => true
        );
        $transport_company2 = new Transport($transport_settings);

        return array(
            array(
                array(
                    'to' => 'to@example.com',
                    'from' => 'from@example.com',
                    'body' => 'body',
                    'subject' => 'subject',
                    'company_id' => 0
                ),
                $transport_company1,
            ),
            array(
                array(
                    'to' => 'to@example.com',
                    'from' => 'from@example.com',
                    'body' => 'body',
                    'subject' => 'subject',
                    'company_id' => 1
                ),
                $transport_company2,
            )
        );
    }
}

class Transport implements ITransport
{
    public $settings;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * @inheritDoc
     */
    public function sendMessage(Message $message)
    {
        return new SendResult($this->settings['result']);
    }
}

class TransportFactory implements ITransportFactory, ICompanyTransportFactory
{
    /**
     * @inheritDoc
     */
    public function createTransport($type, $settings)
    {
        return new Transport($settings);
    }

    /**
     * @inheritdoc
     */
    public function createTransportByCompanyId($company_id)
    {
        $transport_settings = array(
            0 => array(
                'mailer_send_method' => 'smtp',
                'mailer_smtp_host' => 'test',
                'mailer_smtp_username' => 'test',
                'mailer_smtp_password' => 'test',
                'mailer_smtp_ecrypted_connection' => false,
                'result' => true
            ),
            1 => array(
                'mailer_send_method' => 'smtp',
                'mailer_smtp_host' => 'test2',
                'mailer_smtp_username' => 'test2',
                'mailer_smtp_password' => 'test2',
                'mailer_smtp_ecrypted_connection' => false,
                'result' => true
            )
        );

        return new Transport($transport_settings[$company_id]);
    }
}

class MailerMessageBuilder implements IMessageBuilder
{
    public $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * @inheritDoc
     */
    public function createMessage($params, $area, $lang_code)
    {
        $message = new Message();

        if (isset($params['from'])) {
            $message->setFrom($params['from']);
        }

        if (isset($params['to'])) {
            $message->addTo($params['to']);
        }

        if ($this->type === 'default') {
            if (isset($params['body'])) {
                $message->setBody($params['body']);
            }

            if (isset($params['subject'])) {
                $message->setSubject($params['subject']);
            }
        } elseif ($this->type === 'db_template') {
            if ($params['template_code'] === 'example') {
                $message->setBody('example1_body');
                $message->setSubject('example1_subject');
            }
        } elseif ($this->type === 'file_template') {
            if ($params['tpl'] === 'example.tpl') {
                $message->setBody('example1_body');
                $message->setSubject('example1_subject');
            }
        }


        return $message;
    }
}

class MessageBuilderFactory implements IMessageBuilderFactory
{
    /**
     * @inheritDoc
     */
    public function createBuilder($type)
    {
        return new MailerMessageBuilder($type);
    }
}