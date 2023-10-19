<?php


namespace Tygh\Tests\Unit\Addons\AntiFraud;

use PHPUnit\Framework\TestCase;
use Tygh\Addons\AntiFraud\MinFraud\Request;

class RequestTest extends TestCase
{
    /**
     * @param $order
     * @param $expected
     * @dataProvider dpCreateFromOrder
     */
    public function testCreateFromOrder($order, $expected)
    {
        $request = Request::createFromOrder($order);
        $this->assertJsonStringEqualsJsonString($expected, $request->json());
    }

    public function dpCreateFromOrder()
    {
        return array(
            array(
                array(
                    'order_id' => 96,
                    'total' => 972.00,
                    'user_id' => 100,
                    'b_firstname' => 'Jon',
                    'b_lastname' => 'Black',
                    'b_address' => '44 Main street',
                    'b_address_2' => 'small line',
                    'b_city' => 'Boston',
                    'b_state' => 'MA',
                    'b_country' => 'US',
                    'b_zipcode' => '02134',
                    's_firstname' => 'Customer',
                    's_lastname' => 'Customer',
                    's_address' => '55 Main street',
                    's_address_2' => '55 Main street',
                    's_city' => 'New York',
                    's_state' => 'NY',
                    's_country' => 'US',
                    's_zipcode' => '62134',
                    'ip_address' => '8.8.8.8',
                    'secondary_currency' => 'USD',
                    'email' => 'customer@example.com'
                ),
                json_encode(array(
                    'order' => array(
                        'amount' => 972.00,
                        'currency' => 'USD',
                    ),
                    'device' => array(
                        'ip_address' => '8.8.8.8'
                    ),
                    'account' => array(
                        'user_id' => 100,
                    ),
                    'email' => array(
                        'address' => 'customer@example.com',
                        'domain' => 'example.com'
                    ),
                    'billing' => array(
                        'first_name' => 'Jon',
                        'last_name' => 'Black',
                        'address' => '44 Main street',
                        'address_2' => 'small line',
                        'city' => 'Boston',
                        'region' => 'MA',
                        'country' => 'US',
                        'postal' => '02134',
                    ),
                    'shipping' => array(
                        'first_name' => 'Customer',
                        'last_name' => 'Customer',
                        'address' => '55 Main street',
                        'address_2' => '55 Main street',
                        'city' => 'New York',
                        'region' => 'NY',
                        'country' => 'US',
                        'postal' => '62134',
                    )
                ))
            ),
            array(
                array(),
                json_encode(array())
            )
        );
    }
}