<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Tygh\Shippings\Services;

use XMLDocument\Node;
use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Shippings\IService;
use Tygh\Http;
use Tygh\Tools\SecurityHelper;
use XMLDocument\XMLDocument;
use XMLDocument\XMLParser;

/**
 * DHL shipping service.
 * Uses Capability and Quote Service of DHL XML Services v6.3.
 */
class Dhl implements IService
{
    /**
     * @const IMPERIAL_UNITS Imperial units
     */
    const IMPERIAL_UNITS = 'I';

    /**
     * @const METRIC_UNITS Metric units
     */
    const METRIC_UNITS = 'M';

    /**
     * @const IS_DUTIABLE Dutiable package
     */
    const DUTIABLE = 'Y';

    /**
     * @const NOT_DUTIABLE Not dutiable package
     */
    const NOT_DUTIABLE = 'N';

    const DUTIABLE_SERVICE_CODES = ['P', 'M', 'H', 'E'];

    /**
     * Availability multithreading in this module
     *
     * @var array $_allow_multithreading
     */
    private $_allow_multithreading = true;

    public $calculation_currency;

    public function __construct($calculation_currency = null)
    {
        if ($calculation_currency === null) {
            $calculation_currency = CART_PRIMARY_CURRENCY;
        }

        $this->calculation_currency = $calculation_currency;
    }

    /**
     * Description
     *
     * @param  string $response Response from Shipping service server
     * @return array  Array of shipping rates
     */
    private function _getRates($response)
    {
        // Parse XML message returned by DHL
        $doc = new XMLDocument();
        $xp = new XMLParser();
        $xp->setDocument($doc);
        $xp->parse($response);
        $doc = $xp->getDocument();
        $return = array();

        if (is_object($doc->root)) {
            /** @var \XMLDocument\Node $root */
            $root = $doc->getRoot();
            if ($root->name != 'res:ErrorResponse') {
                $path = array('GetQuoteResponse', 'BkgDetails');
                foreach ($path as $node) {
                    $root = $root->getElementsByName($node);
                    if ($root) {
                        $root = $root[0];
                    } else {
                        break;
                    }
                }
                $currencies = Registry::get('currencies');
                if ($root) {
                    $shipments = $root->getElementsByName('QtdShp');
                    /** @var \XMLDocument\Node $root */
                    foreach ($shipments as $shipment) {
                        $name = $shipment->getValueByPath('ProductShortName');
                        $code = $shipment->getValueByPath('GlobalProductCode');
                        $rate = $this->getShipmentRate($shipment, $currencies, CART_PRIMARY_CURRENCY);
                        $date = $shipment->getValueByPath('DeliveryDate');
                        $time = $shipment->getValueByPath('DeliveryTime');
                        if ($time) {
                            $time = new \DateInterval($time);
                            $date .= ', ' . $time->format('%H:%I');
                        }
                        if ($rate) {
                            $return[$code] = array(
                                'rate' => $rate,
                                'delivery_time' => $date,
                                'name' => $name
                            );
                        }
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Sets data to internal class variable
     *
     * @param array $shipping_info Shipping method configuration
     */
    public function prepareData($shipping_info)
    {
        $this->_shipping_info = $shipping_info;
    }

    /**
     * Gets shipping cost and information about possible errors
     *
     * @param  string $response Response from Shipping service server
     * @return array  Shipping cost and errors
     */
    public function processResponse($response)
    {
        $return = array(
            'cost' => false,
            'error' => false,
            'delivery_time' => false,
        );

        $rates = $this->_getRates($response);

        if (!empty($rates[$this->_shipping_info['service_code']])) {
            $return['cost'] = $rates[$this->_shipping_info['service_code']]['rate'];

            if (isset($rates[$this->_shipping_info['service_code']]['delivery_time'])) {
                $return['delivery_time'] = $rates[$this->_shipping_info['service_code']]['delivery_time'];
            }
        } else {
            $return['error'] = $this->processErrors($response);
        }

        return $return;
    }

    /**
     * Gets error message from shipping service server
     *
     * @param  string $response Response from Shipping service server
     * @return string Text of error or false if no errors
     */
    public function processErrors($response)
    {
        $doc = new XMLDocument();
        $xp = new XMLParser();
        $xp->setDocument($doc);
        $xp->parse($response);
        $doc = $xp->getDocument();
        $return = array();

        if (is_object($doc->root)) {
            $root = $doc->getRoot();
            // distinguish error reports from not available services
            if ($root->name == 'res:ErrorResponse') {
                $path = array('Response', 'Status');
            } else {
                $path = array('GetQuoteResponse', 'Note');
            }
            /** @var \XMLDocument\Node $root */
            foreach ($path as $node) {
                $root = $root->getElementsByName($node);
                if ($root) {
                    $root = $root[0];
                } else {
                    break;
                }
            }
            if ($root) {
                $conditions = $root->getElementsByName('Condition');
                /** @var \XMLDocument\Node $condition */
                foreach ($conditions as $condition) {
                    $error_code = trim($condition->getValueByPath('ConditionCode'));
                    $error_text = trim($condition->getValueByPath('ConditionData'));
                    $return[] = "({$error_code}) {$error_text}";
                }
            }
        }

        return implode(' / ', $return);
    }

    /**
     * Checks if shipping service allows to use multithreading
     *
     * @return bool true if allow
     */
    public function allowMultithreading()
    {
        return $this->_allow_multithreading;
    }

    /**
     * Prepare request information
     *
     * @return array Prepared data
     */
    public function getRequestData()
    {
        $params = $this->_shipping_info['service_params'];

        // Account information
        $site_id = !empty($params['system_id']) ? $params['system_id'] : '';
        $password = !empty($params['password']) ? $params['password'] : '';
        $account_number = !empty($params['account_number']) ? $params['account_number'] : '';
        $system_of_measurement = !empty($params['system_of_measurement']) ? $params['system_of_measurement'] : self::IMPERIAL_UNITS;

        // Sender and receiver
        $shipper = $this->prepareAddress($this->_shipping_info['package_info']['origination']);
        $consignee = $this->prepareAddress($this->_shipping_info['package_info']['location']);

        $account_country = !empty($params['account_country']) ? $params['account_country'] : $shipper['country'];

        $service_type = $this->_shipping_info['service_code'];

        // Weight of package
        if ($system_of_measurement === self::IMPERIAL_UNITS) {
            $weight_data = fn_convert_weight_to_imperial_units($this->_shipping_info['package_info']['W']);
            $shipment_weight = $weight_data['full_pounds'];
        } else {
            $weight_data = fn_convert_weight_to_metric_units($this->_shipping_info['package_info']['W']);
            $shipment_weight = $weight_data['full_kilograms'];
        }

        // Ship date
        $ship_date = date("Y-m-d", TIME + (date('w', TIME) == 0 ? 86400 : 0));
        $ready_time = 'PT' . date('H') . 'H' . date('i') . 'M';

        if ($account_number) {
            $payment_account_number = <<<XML
            <PaymentAccountNumber>{$account_number}</PaymentAccountNumber>
XML;
        } else {
            $payment_account_number = '';
        }

        // Pieces
        $cost = 0;
        $packages = $this->_shipping_info['package_info']['packages'];
        if ($packages) {
            $pieces = <<<XML
            <Pieces>
XML
                . PHP_EOL;
            foreach ($packages as $i => $package_item) {
                $piece_id = $i + 1;
                $width = empty($package_item['shipping_params']['box_width']) ? floatval($params['width']): $package_item['shipping_params']['box_width'];
                $height = empty($package_item['shipping_params']['box_height']) ? floatval($params['height']) : $package_item['shipping_params']['box_height'];
                $depth = empty($package_item['shipping_params']['box_length']) ? floatval($params['length']) : $package_item['shipping_params']['box_length'];

                if ($system_of_measurement === self::IMPERIAL_UNITS) {
                    $weight_data = fn_convert_weight_to_imperial_units($package_item['weight']);
                    $package_weight = $weight_data['full_pounds'];
                } else {
                    $weight_data = fn_convert_weight_to_metric_units($package_item['weight']);
                    $package_weight = $weight_data['full_kilograms'];
                }

                $cost += $package_item['cost'];
                $pieces .= <<<XML
                <Piece>
                    <PieceID>{$piece_id}</PieceID>
                    <Height>{$height}</Height>
                    <Depth>{$depth}</Depth>
                    <Width>{$width}</Width>
                    <Weight>{$package_weight}</Weight>
                </Piece>
XML;
            }
            $pieces .= PHP_EOL . <<<XML
            </Pieces>
XML;
        } else {
            $max_piece_height = max(floatval($params['height']), 0);
            $max_piece_width  = max(floatval($params['width']), 0);
            $max_piece_depth  = max(floatval($params['length']), 0);

            $pieces = PHP_EOL. <<<XML
            <NumberOfPieces>1</NumberOfPieces>
            <ShipmentWeight>{$shipment_weight}</ShipmentWeight>
            <MaxPieceWeight>{$shipment_weight}</MaxPieceWeight>
            <MaxPieceHeight>{$max_piece_height}</MaxPieceHeight>
            <MaxPieceDepth>{$max_piece_depth}</MaxPieceDepth>
            <MaxPieceWidth>{$max_piece_width}</MaxPieceWidth>
XML;
        }

        if ($system_of_measurement === self::IMPERIAL_UNITS) {
            $units = PHP_EOL . <<<XML
            <DimensionUnit>IN</DimensionUnit>
            <WeightUnit>LB</WeightUnit>
XML;
        } else {
            $units = PHP_EOL . <<<XML
            <DimensionUnit>CM</DimensionUnit>
            <WeightUnit>KG</WeightUnit>
XML;
        }

        // Message time
        $message_time = date('Y-m-d') . 'T' . date('H:i:sP');
        $message_reference = SecurityHelper::generateRandomString();

        $is_dutiable = in_array($service_type, self::DUTIABLE_SERVICE_CODES)
            ? self::DUTIABLE
            : self::NOT_DUTIABLE;

        $currency_code = CART_PRIMARY_CURRENCY;
        $dutiable = '';
        if ($is_dutiable === self::DUTIABLE) {
            $dutiable = <<<XML
        <Dutiable>
            <DeclaredCurrency>{$currency_code}</DeclaredCurrency>
            <DeclaredValue>{$cost}</DeclaredValue>
        </Dutiable>
XML;
        }

        $software_version = PRODUCT_VERSION;

        $request = <<<EOT
<?xml version="1.0" encoding="UTF-8" ?>
<p:DCTRequest xmlns:p="http://www.dhl.com" 
    xmlns:p1="http://www.dhl.com/datatypes" 
    xmlns:p2="http://www.dhl.com/DCTRequestdatatypes"
    schemaVersion="2.0"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://www.dhl.com DCT-req.xsd "
>
    <GetQuote>
        <Request>
            <ServiceHeader>
                <MessageTime>{$message_time}</MessageTime>
                <MessageReference>{$message_reference}</MessageReference>
                <SiteID>{$site_id}</SiteID>
                <Password>{$password}</Password>
            </ServiceHeader>
            <MetaData>
                <SoftwareName>CS-Cart</SoftwareName>
                <SoftwareVersion>{$software_version}</SoftwareVersion>
            </MetaData>
        </Request>
        <From>
            <CountryCode>{$shipper['country']}</CountryCode>
            <Postalcode>{$shipper['zipcode']}</Postalcode>
            <City>{$shipper['city']}</City>
        </From>
        <BkgDetails>
            <PaymentCountryCode>{$account_country}</PaymentCountryCode>
            <Date>{$ship_date}</Date>
            <ReadyTime>{$ready_time}</ReadyTime>
{$units}
{$pieces}
{$payment_account_number}
            <IsDutiable>{$is_dutiable}</IsDutiable>
            <QtdShp>
                <GlobalProductCode>{$service_type}</GlobalProductCode>
            </QtdShp>
        </BkgDetails>
        <To>
            <CountryCode>{$consignee['country']}</CountryCode>
            <Postalcode>{$consignee['zipcode']}</Postalcode>
            <City>{$consignee['city']}</City>
        </To>
{$dutiable}
    </GetQuote>
</p:DCTRequest>
EOT;

        // Request url
        if (!empty($params['test_mode']) && $params['test_mode'] === YesNo::YES) {
            $url = 'https://xmlpitest-ea.dhl.com/XMLShippingServlet?isUTF8Support=true';
        } else {
            $url = 'https://xmlpi-ea.dhl.com/XMLShippingServlet?isUTF8Support=true';
        }

        $request_data = array(
            'method' => 'post',
            'url' => $url,
            'data' => $request,
            'headers' => array(
                'Content-type: text/xml'
            )
        );

        return $request_data;
    }

    /**
     * Process simple request to shipping service server
     *
     * @return string Server response
     */
    public function getSimpleRates()
    {
        $data = $this->getRequestData();
        $response = Http::post($data['url'], $data['data'], array('headers' => 'Content-type: text/xml'));

        return $response;
    }

    /**
     * Fill required address fields
     * TODO: Add to \Tygh\Shippings\IService
     *
     * @param array $address Address data
     *
     * @return array Filled address data
     */
    public function prepareAddress($address)
    {
        $default_fields = array(
            'zipcode' => '',
            'country' => '',
            'city' => '',
        );

        return array_merge($default_fields, $address);
    }

    /**
     * Returns shipping service information
     * @return array information
     */
    public static function getInfo()
    {
        return array(
            'name' => __('carrier_dhl'),
            'tracking_url' => 'http://www.dhl.com/content/g0/en/express/tracking.shtml?AWB=%s&brand=DHL'
        );
    }

    /**
     * Returns shipment rate calculated in the primary currency.
     *
     * @param Node  $shipment          QtdShp node from service XML response
     * @param array  $currencies       Store currencies
     * @param string $primary_currency Store primary currency
     *
     * @return float|null Shipment rate or null when none found
     */
    protected function getShipmentRate(Node $shipment, array $currencies, $primary_currency)
    {
        $rates_list = $this->collectShipmentRatesFromResponse($shipment);

        // reorder rates, put the one in the primary currency first
        $rates_list = $this->sortRatesByCurrency($rates_list, $primary_currency);

        foreach ($rates_list as $currency_code => $rate) {
            // check if specified currency exists in the store and convert to primary
            if (!empty($currencies[$currency_code])) {
                // convert to primary
                return $rate * $currencies[$currency_code]['coefficient'] / $currencies[$primary_currency]['coefficient'];
            }
        }

        return null;
    }

    /**
     * Collects shipments rates in all provided currencies from the service XML response.
     *
     * @param Node  $shipment         QtdShp node from service XML response
     *
     * @return array Shipment rates; keys of the array are currency codes and values are amounts
     */
    protected function collectShipmentRatesFromResponse(Node $shipment)
    {
        $rates_list = array(
            $shipment->getValueByPath('CurrencyCode') => (float) $shipment->getValueByPath('ShippingCharge')
        );

        // collect rates in all provided currencies
        $addition_currency_rates = $shipment->getElementsByName('QtdSInAdCur');
        if ($addition_currency_rates) {
            /** @var Node $additional_rate */
            foreach ($addition_currency_rates as $additional_rate) {
                $rates_list[$additional_rate->getValueByPath('CurrencyCode')] =
                    (float) $additional_rate->getValueByPath('TotalAmount');
            }
        }

        return $rates_list;
    }

    /**
     * Reorders rates, puts the ones in the specified currency first.
     *
     * @param array  $rates_list    Rates obtained from the shipping service
     * @param string $currency_code Currency code
     *
     * @return array Sorted list of rates
     */
    public function sortRatesByCurrency($rates_list, $currency_code)
    {
        if (isset($rates_list[$currency_code])) {
            $sorted_rates = array(
                $currency_code => $rates_list[$currency_code]
            );
            unset($rates_list[$currency_code]);

            $rates_list = array_merge($sorted_rates, $rates_list);
        }

        return $rates_list;
    }
}
