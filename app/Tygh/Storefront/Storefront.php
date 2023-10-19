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

namespace Tygh\Storefront;

use Exception;

/**
 * Class Storefront represents a separate storefront with the unique URL.
 * Each storefront displays a part of the whole catalogue.
 *
 * @method string[]   getCountryCodes()
 * @method Storefront setCountryCodes(string|string[] $codes)
 * @method Storefront removeCountryCodes(string|string[] $codes)
 * @method Storefront addCountryCodes(string|string[] $codes)
 * @method int[]      getLanguageIds()
 * @method int[]      getAvailableLanguageIds()
 * @method Storefront setLanguageIds(int|int[] $ids)
 * @method Storefront removeLanguageIds(int|int[] $ids)
 * @method Storefront addLanguageIds(int|int[] $ids)
 * @method int[]      getCurrencyIds()
 * @method int[]      getAvailableCurrencyIds()
 * @method Storefront setCurrencyIds(int|int[] $ids)
 * @method Storefront removeCurrencyIds(int|int[] $ids)
 * @method Storefront addCurrencyIds(int|int[] $ids)
 * @method int[]      getCompanyIds()
 * @method Storefront setCompanyIds(int|int[] $ids)
 * @method Storefront removeCompanyIds(int|int[] $ids)
 * @method Storefront addCompanyIds(int|int[] $ids)
 * @method int[]      getPaymentIds()
 * @method Storefront setPaymentIds(int|int[] $ids)
 * @method Storefront removePaymentIds(int|int[] $ids)
 * @method Storefront addPaymentIds(int|int[] $ids)
 * @method int[]      getShippingIds()
 * @method Storefront setShippingIds(int|int[] $ids)
 * @method Storefront removeShippingIds(int|int[] $ids)
 * @method Storefront addShippingIds(int|int[] $ids)
 * @method int[]      getPromotionIds()
 * @method Storefront setPromotionIds(int|int[] $ids)
 * @method Storefront removePromotionIds(int|int[] $ids)
 * @method Storefront addPromotionIds(int|int[] $ids)
 *
 * @package Tygh\Storefront
 *
 * phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
 */
class Storefront
{
    /**
     * Storefront URL
     *
     * @var string
     */
    public $url;

    /**
     * Storefront ID
     *
     * @var int
     */
    public $storefront_id;

    /**
     * @var bool
     */
    public $redirect_customer;

    /**
     * @var bool
     */
    public $is_default;

    /**
     * @var string
     */
    public $status;

    /**
     * @var string
     */
    public $access_key;

    /**
     * @var string
     */
    public $theme_name;

    /**
     * @var string
     */
    public $name;

    /**
     * @var \Tygh\Storefront\RelationsManager
     */
    protected $relations_manager;

    /**
     * @var array
     */
    public $extra = [];

    /**
     * @var array[]
     */
    protected $relations = [];

    /**
     * @var array<array-key, array<array-key, mixed>|null>
     */
    protected $stored_relations = [];

    /**
     * @var bool
     */
    public $is_accessible_for_authorized_customers_only = false;

    /**
     * Storefront constructor.
     *
     * @param int                               $storefront_id
     * @param string                            $url
     * @param bool                              $is_default
     * @param bool                              $redirect_customer
     * @param string                            $status
     * @param string                            $access_key
     * @param \Tygh\Storefront\RelationsManager $relation_manager
     * @param string                            $name
     * @param string                            $theme_name
     * @param array[]                           $relations
     * @param bool                              $is_accessible_for_authorized_customers_only
     */
    public function __construct(
        $storefront_id,
        $url,
        $is_default,
        $redirect_customer,
        $status,
        $access_key,
        RelationsManager $relation_manager,
        $name,
        $theme_name,
        array $relations = [],
        $is_accessible_for_authorized_customers_only = false
    ) {
        $this->storefront_id = $storefront_id;
        $this->url = $url;
        $this->relations_manager = $relation_manager;
        $this->is_default = $is_default;
        $this->redirect_customer = $redirect_customer;
        $this->status = $status;
        $this->access_key = $access_key;
        $this->name = $name;
        $this->theme_name = $theme_name;
        $this->is_accessible_for_authorized_customers_only = $is_accessible_for_authorized_customers_only;

        if ($this->relations_manager->getRelations()) {
            foreach ($this->relations_manager->getRelations() as $relation_name) {
                $this->relations[$relation_name] = isset($relations[$relation_name])
                    ? $relations[$relation_name]
                    : null;
            }

            if ($storefront_id) {
                $this->stored_relations = $this->relations;
            }
        }
    }

    public function toArray($get_id = true, $prefetch = false)
    {
        // entity fields
        $storefront_data = [
            'url'                                         => $this->url,
            'redirect_customer'                           => $this->redirect_customer,
            'is_default'                                  => $this->is_default,
            'status'                                      => $this->status,
            'access_key'                                  => $this->access_key,
            'name'                                        => $this->name,
            'theme_name'                                  => $this->theme_name,
            'is_accessible_for_authorized_customers_only' => $this->is_accessible_for_authorized_customers_only
        ];

        // lazy-loaded fields
        foreach ($this->relations_manager->getRelations() as $relation_name) {
            $current_value = null;
            if (isset($this->relations[$relation_name])) {
                $current_value = $this->relations[$relation_name];
            }

            $storefront_data[$relation_name] = $prefetch
                ? $this->getRelationValue($relation_name)
                : $current_value;
        }

        if ($get_id) {
            $storefront_data['storefront_id'] = $this->storefront_id;
        }

        return $storefront_data;
    }

    /**
     * Allows getting and setting relation values by aliases.
     *
     * @param string $method_name
     * @param array  $args
     *
     * @return array|\Tygh\Storefront\Storefront
     * @throws \Exception
     */
    public function __call($method_name, $args)
    {
        if (strpos($method_name, 'getAvailable') === 0) {
            $relation_name = $this->relations_manager->resolveName(substr($method_name, 12));

            return $this->getAvailableRelationValue($relation_name);
        }

        if (strpos($method_name, 'get') === 0) {
            $relation_name = $this->relations_manager->resolveName(substr($method_name, 3));

            return $this->getRelationValue($relation_name);
        }

        if (strpos($method_name, 'set') === 0) {
            $relation_name = $this->relations_manager->resolveName(substr($method_name, 3));
            $value = reset($args);

            return $this->setRelationValue($relation_name, $value);
        }

        if (strpos($method_name, 'remove') === 0) {
            $relation_name = $this->relations_manager->resolveName(substr($method_name, 6));
            $removed_values = reset($args);

            return $this->removeRelationValues($relation_name, $removed_values);
        }

        if (strpos($method_name, 'add') === 0) {
            $relation_name = $this->relations_manager->resolveName(substr($method_name, 3));
            $added_values = reset($args);

            return $this->addRelationValues($relation_name, $added_values);
        }

        throw new Exception();
    }

    /**
     * Gets value of the related entity.
     *
     * @param string $relation_name
     *
     * @return array
     */
    public function getRelationValue($relation_name)
    {
        if (!isset($this->relations[$relation_name])) {
            $this->relations[$relation_name] = $this->relations_manager->getRelationValue(
                $this->storefront_id,
                $relation_name
            );
        }

        return $this->relations[$relation_name];
    }

    /**
     * Gets value of the available entity.
     *
     * @param string $relation_name Relation name
     *
     * @return array
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getAvailableRelationValue($relation_name)
    {
        if (!isset($this->relations[$relation_name])) {
            $this->relations[$relation_name] = $this->relations_manager->getAvailableRelationValue(
                $this->storefront_id,
                $relation_name
            );
        }

        return $this->relations[$relation_name];
    }

    /**
     * Sets value of the related entity.
     *
     * @param string $relation_name
     * @param mixed  $value
     *
     * @return \Tygh\Storefront\Storefront
     */
    public function setRelationValue($relation_name, $value)
    {
        $this->relations[$relation_name] = $value;

        return $this;
    }

    /**
     * Removes a relation value from set.
     *
     * @param string $relation_name
     * @param mixed  $removed_values
     *
     * @return \Tygh\Storefront\Storefront
     */
    public function removeRelationValues($relation_name, $removed_values)
    {
        if (!$removed_values) {
            return $this;
        }

        $current_values = $this->getRelationValue($relation_name);
        if (!$current_values) {
            return $this;
        }

        $removed_values = (array) $removed_values;

        $new_values = array_filter(
            $current_values,
            function ($current_value) use ($removed_values) {
                return !in_array($current_value, $removed_values);
            }
        );

        return $this->setRelationValue($relation_name, array_values($new_values));
    }

    /**
     * Adds a relation value to set.
     *
     * @param string $relation_name
     * @param mixed  $added_values
     *
     * @return \Tygh\Storefront\Storefront
     */
    public function addRelationValues($relation_name, $added_values)
    {
        if (!$added_values) {
            return $this;
        }

        $current_values = $this->getRelationValue($relation_name);

        $added_values = (array) $added_values;

        $new_values = array_unique(array_merge($current_values, $added_values));

        return $this->setRelationValue($relation_name, array_values($new_values));
    }

    /**
     * @param string $relation_name Relation name
     *
     * @return bool
     */
    public function isReleationChanged($relation_name)
    {
        $stored_value = isset($this->stored_relations[$relation_name]) ? $this->stored_relations[$relation_name] : null;
        $current_value = isset($this->relations[$relation_name]) ? $this->relations[$relation_name] : null;

        return $stored_value !== $current_value;
    }

    /**
     * Sets value as stored value for relation
     *
     * @param string $relation_name Relation name
     * @param mixed  $value         Value
     */
    public function setStoredRelationValue($relation_name, $value)
    {
        $this->stored_relations[$relation_name] = $value;
    }

    /**
     * Gets stored relation value
     *
     * @param string $relation_name Relation name
     *
     * @return mixed
     */
    public function getStoredRelationValue($relation_name)
    {
        return isset($this->stored_relations[$relation_name]) ? $this->stored_relations[$relation_name] : null;
    }
}
