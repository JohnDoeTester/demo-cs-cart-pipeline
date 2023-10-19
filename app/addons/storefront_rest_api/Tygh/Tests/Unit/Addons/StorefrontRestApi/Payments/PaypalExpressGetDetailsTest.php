<?php

namespace Tygh\Tests\Unit\Addons\StorefrontRestApi\Payments;

use Tygh\Tests\Unit\ATestCase;

class PaypalExpressGetDetailsTest extends ATestCase
{
    public $runTestInSeparateProcess = true;

    public $backupGlobals = false;

    public $preserveGlobalState = false;

    protected function setUp(): void
    {
        define('AREA', 'C');
        define('TIME', time());
    }

    /**
     * @dataProvider getTestGetDetails
     */
    public function testGetDetails($order_info, $auth_info, $payment_info, $request, $expected_result)
    {
        $pp = new PaypalExpress();
        $actual_result = $pp
            ->setOrderInfo($order_info)
            ->setAuthInfo($auth_info)
            ->setPaymentInfo($payment_info)
            ->getDetails($request);

        $this->assertEquals($expected_result['is_success'], $actual_result->isSuccess());
        $this->assertEquals($expected_result['data'], $actual_result->getData());
        $this->assertEquals($expected_result['errors'], $actual_result->getErrors());
    }

    public function getTestGetDetails()
    {
        return array(
            array(
                array('order_id' => 1),
                array(),
                array(
                    'payment_id'       => 1,
                    'processor_params' => array(
                        'mode' => 'test',
                    ),
                ),
                array(
                    'paypal_response' => array(
                        'ACK'   => 'Success',
                        'TOKEN' => 'TEST-TOKEN-1',
                    ),
                ),
                array(
                    'is_success' => true,
                    'data'       => array(
                        'method'           => 'GET',
                        'payment_url'      => 'https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=TEST-TOKEN-1',
                        'query_parameters' => array(),
                        'return_url'       => 'payment_notification.notify?payment=paypal_express&order_id=1&is_mobile_app=1',
                        'cancel_url'       => 'payment_notification.cancel?payment=paypal_express&order_id=1&is_mobile_app=1',
                    ),
                    'errors'     => array(),
                ),
            ),

            array(
                array('order_id' => 2,),
                array(),
                array(
                    'payment_id'       => 1,
                    'processor_params' => array(
                        'mode' => 'live',
                    ),
                ),
                array(
                    'paypal_response' => array(
                        'ACK'   => 'Success',
                        'TOKEN' => 'TEST-TOKEN-2',
                    ),
                ),
                array(
                    'is_success' => true,
                    'data'       => array(
                        'method'           => 'GET',
                        'payment_url'      => 'https://www.paypal.com/webscr?cmd=_express-checkout&token=TEST-TOKEN-2',
                        'query_parameters' => array(),
                        'return_url'       => 'payment_notification.notify?payment=paypal_express&order_id=2&is_mobile_app=1',
                        'cancel_url'       => 'payment_notification.cancel?payment=paypal_express&order_id=2&is_mobile_app=1',
                    ),
                    'errors'     => array(),
                ),
            ),

            array(
                array('order_id' => 3,),
                array(),
                array(
                    'payment_id'       => 1,
                    'processor_params' => array(
                        'mode' => 'live',
                    ),
                ),
                array(
                    'paypal_response' => array(
                        'ACK'             => 'Failure',
                        'L_ERRORCODE0'    => '111',
                        'L_SHORTMESSAGE0' => 'Short',
                        'L_LONGMESSAGE0'  => 'Long',
                    ),
                ),
                array(
                    'is_success' => false,
                    'data'       => null,
                    'errors'     => array(
                        111 => 'Short: Long',
                    ),
                ),
            ),
        );
    }
}
