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

namespace Tygh\Api\Entities\v40;

use Tygh\Addons\StorefrontRestApi\ASraEntity;
use Tygh\Api\Response;
use Tygh\Enum\YesNo;
use Tygh\Registry;

/**
 * Class SraCurrencies
 *
 * @package Tygh\Api\Entities\v40
 *
 * phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
 */
class SraCurrencies extends ASraEntity
{
    /**
     * @var bool[] Currency properties to keep in the API response
     */
    private $public_properties = [
        'currency_code' => true,
        'description'   => true,
        'is_primary'    => true,
        'symbol'        => true,
    ];

    /**
     * Gets list of currencies or a single currency.
     *
     * @param int                   $id     Currency identifier
     * @param array<string, string> $params Search parameters
     *
     * @return array<string, int|string|array<string, string>|array<array<string, string>>>
     */
    public function index($id = 0, $params = [])
    {
        $currencies = Registry::get('currencies');
        foreach ($currencies as &$currency) {
            $currency['is_primary'] = YesNo::toBool($currency['is_primary']);
        }
        unset($currency);

        $is_single_currency_required = false;
        if ($id) {
            $currencies = $this->filterMatchingId($currencies, $id);
            $is_single_currency_required = true;
        }

        $currencies = $this->cleanupPrivateData($currencies);
        $currencies = array_values($currencies);

        $response = [
            'data'   => [],
            'status' => Response::STATUS_OK,
        ];

        if (!$currencies) {
            $response['status'] = Response::STATUS_NOT_FOUND;
        } else {
            if ($is_single_currency_required) {
                $response['data'] = reset($currencies);
            } else {
                $response['data']['currencies'] = $currencies;
            }
        }

        return $response;
    }

    /**
     * Forbids creating currencies via API.
     *
     * @param array<string, string> $params Request parameters
     *
     * @return array<string, int>
     */
    public function create($params)
    {
        return [
            'status' => Response::STATUS_FORBIDDEN,
        ];
    }

    /**
     * Forbids updating currencies via API.
     *
     * @param int                   $id     Currency ID
     * @param array<string, string> $params Request parameters
     *
     * @return array<string, int>
     */
    public function update($id, $params)
    {
        return [
            'status' => Response::STATUS_FORBIDDEN,
        ];
    }

    /**
     * Forbids removing currencies via API.
     *
     * @param int $id Currency ID
     *
     * @return array<string, int>
     */
    public function delete($id)
    {
        return [
            'status' => Response::STATUS_FORBIDDEN,
        ];
    }

    /**
     * @return array<string, bool>
     */
    public function privileges()
    {
        return [
            'index' => true,
        ];
    }

    /**
     * @return array<string, bool>
     */
    public function privilegesCustomer()
    {
        return [
            'index' => true,
        ];
    }

    /**
     * Removes private currency data from the API response.
     *
     * @param array<array<string, string>> $currencies Currencies list
     *
     * @return array<array<string, string>>
     */
    private function cleanupPrivateData(array $currencies)
    {
        foreach ($currencies as $id => $currency) {
            foreach (array_keys($currency) as $property) {
                if (isset($this->public_properties[$property])) {
                    continue;
                }
                unset($currencies[$id][$property]);
            }
        }

        return $currencies;
    }

    /**
     * Removes currencies that don't have matching currency ID from the list of found ones.
     *
     * @param array<array<string, string>> $currencies Currencies list
     * @param int                          $id         Currency identifier
     *
     * @return array<array<string, string>>
     */
    private function filterMatchingId(array $currencies, $id)
    {
        return array_filter(
            $currencies,
            static function (array $currency) use ($id) {
                return (int) $currency['currency_id'] === (int) $id;
            }
        );
    }
}
