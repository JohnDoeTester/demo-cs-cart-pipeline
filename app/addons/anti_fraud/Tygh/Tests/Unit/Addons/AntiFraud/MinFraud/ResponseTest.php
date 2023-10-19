<?php


namespace Tygh\Tests\Unit\Addons\AntiFraud;


use PHPUnit\Framework\TestCase;
use Tygh\Addons\AntiFraud\MinFraud\Response;

class ResponseTest extends TestCase
{
    /**
     * @param $raw_response
     * @dataProvider dpCreate
     */
    public function testCreate($raw_response)
    {
        $data = json_decode($raw_response, true);
        $response = new Response($raw_response);

        if (isset($data['id'])) {
            $this->assertEquals($data['id'], $response->getId());
        }

        if (isset($data['risk_score'])) {
            $this->assertEquals($data['risk_score'], $response->getRiskScore());
        }

        if (isset($data['credits_remaining'])) {
            $this->assertEquals($data['credits_remaining'], $response->getCreditsRemaining());
        }

        if (isset($data['ip_address'])) {
            $this->assertEquals($data['ip_address'], $response->getIpAddressData());
        }

        if (isset($data['email'])) {
            $this->assertEquals($data['email'], $response->getEmailData());
        }

        if (isset($data['shipping_address'])) {
            $this->assertEquals($data['shipping_address'], $response->getShippingAddressData());
        }

        if (isset($data['billing_address'])) {
            $this->assertEquals($data['billing_address'], $response->getBillingAddressData());
        }

        if (isset($data['warnings'])) {
            $this->assertEquals($data['warnings'], $response->getWarnings());
        }

        if (isset($data['error'])) {
            $this->assertTrue($response->hasError());
            $this->assertEquals($data['code'], $response->getErrorCode());
            $this->assertEquals($data['error'], $response->getErrorMessage());
        }
    }

    public function dpCreate()
    {
        return array(
            array(json_encode(array(
                "id" => "5bc5d6c2-b2c8-40af-87f4-6d61af86b6ae",
                "risk_score" => 0.01,
                "credits_remaining"  => 1212,
                "ip_address" => array(
                    "risk" => 0.01,
                ),
                "email" => array(
                    "is_free" => false,
                    "is_high_risk" => true
                ),
                "shipping_address" => array(
                    "is_high_risk" => true,
                    "is_postal_in_city" => true,
                    "latitude" => 37.632,
                    "longitude" => -122.313,
                    "distance_to_ip_location" => 15,
                    "distance_to_billing_address" => 22,
                    "is_in_ip_country" => true
                ),
                "billing_address" => array(
                    "is_postal_in_city" =>  true,
                    "latitude" => 37.545,
                    "longitude" => -122.421,
                    "distance_to_ip_location" => 100,
                    "is_in_ip_country" => true
                ),
                "warnings" => array(
                    array(
                        "code" => "INPUT_INVALID",
                        "warning" => "Encountered value at /shipping/city that does not meet the required constraints",
                    )
                )
            ))),
            array(json_encode(array(
                'code' => 'IP_ADDRESS_INVALID',
                'error' => 'You have not supplied a valid IPv4 or IPv6 address.'
            )))
        );
    }
}