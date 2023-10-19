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
use Tygh\Registry;

/**
 * Class SraLanguages
 *
 * @package Tygh\Api\Entities\v40
 *
 * phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
 */
class SraLanguages extends ASraEntity
{
    /**
     * @var bool[] Language properties to keep in the API response
     */
    private $public_properties = [
        'lang_code'  => true,
        'is_default' => true,
        'name'       => true,
    ];

    /**
     * Gets list of languages or a single language.
     *
     * @param int                   $id     Language identifier
     * @param array<string, string> $params Search parameters
     *
     * @return array<string, int|string|array<string, string>|array<array<string, string>>>
     */
    public function index($id = 0, $params = [])
    {
        $languages = Registry::get('languages');
        foreach ($languages as &$language) {
            $language['is_default'] = $language['lang_code'] === DEFAULT_LANGUAGE;
        }
        unset($language);

        $is_single_language_required = false;
        if ($id) {
            $languages = $this->filterMatchingId($languages, $id);
            $is_single_language_required = true;
        }

        $languages = $this->cleanupPrivateData($languages);
        $languages = array_values($languages);

        $response = [
            'data'   => [],
            'status' => Response::STATUS_OK,
        ];

        if (!$languages) {
            $response['status'] = Response::STATUS_NOT_FOUND;
        } else {
            if ($is_single_language_required) {
                $response['data'] = reset($languages);
            } else {
                $response['data']['languages'] = $languages;
            }
        }

        return $response;
    }

    /**
     * Forbids creating languages via API.
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
     * Forbids updating languages via API.
     *
     * @param int                   $id     Language ID
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
     * Forbids removing languages via API.
     *
     * @param int $id Language ID
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
     * Removes private language data from the API response.
     *
     * @param array<array<string, string>> $languages Languages list
     *
     * @return array<array<string, string>>
     */
    private function cleanupPrivateData(array $languages)
    {
        foreach ($languages as $id => $language) {
            foreach (array_keys($language) as $property) {
                if (isset($this->public_properties[$property])) {
                    continue;
                }
                unset($languages[$id][$property]);
            }
        }

        return $languages;
    }

    /**
     * Removes languages that don't have matching language ID from the list of found ones.
     *
     * @param array<array<string, string>> $languages Languages list
     * @param int                          $id        Language identifier
     *
     * @return array<array<string, string>>
     */
    private function filterMatchingId(array $languages, $id)
    {
        return array_filter(
            $languages,
            static function (array $language) use ($id) {
                return (int) $language['lang_id'] === (int) $id;
            }
        );
    }
}
