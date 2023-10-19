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


namespace Tygh\Addons\ProductVariations\Product\Group;

use ArrayAccess;
use Iterator;
use Countable;
use Tygh\Exceptions\InputException;

/**
 * Class GroupFeatureCollection
 *
 * @package Tygh\Addons\ProductVariations\Product\Group
 */
class GroupFeatureCollection implements ArrayAccess, Iterator, Countable
{
    /**
     * @var \Tygh\Addons\ProductVariations\Product\Group\GroupFeature[]
     */
    protected $features = [];

    /**
     * GroupFeatureList constructor
     *
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeature[] $group_features
     */
    public function __construct(array $group_features = [])
    {
        $this->setFeatures($group_features);
    }

    /**
     * Sets group feature list
     *
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeature[] $group_features
     */
    public function setFeatures(array $group_features)
    {
        foreach ($group_features as $group_feature) {
            $this->addFeature($group_feature);
        }
    }

    /**
     * Adds group feature to list
     *
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeature $group_feature
     */
    public function addFeature(GroupFeature $group_feature)
    {
        $this->features[$group_feature->getFeatureId()] = $group_feature;
    }

    /**
     * Gets feature identifier list
     *
     * @return int[]
     */
    public function getFeatureIds()
    {
        return array_keys($this->features);
    }

    /**
     * Gets group features
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeature[]
     */
    public function getFeatures()
    {
        return $this->features;
    }

    /**
     * @param int         $feature_id
     * @param string|null $feature_purpose
     *
     * @return bool
     */
    public function hasFeature($feature_id, $feature_purpose = null)
    {
        if ($feature_purpose !== null) {
            return isset($this->features[$feature_id])
                && $this->features[$feature_id]->getFeaturePurpose() === $feature_purpose;
        } else {
            return isset($this->features[$feature_id]);
        }
    }

    /**
     * @param string $purpose
     *
     * @return array
     */
    public function getFeatureIdsByPurpose($purpose)
    {
        $feature_ids = [];

        foreach ($this->features as $feature_id => $feature) {
            if ($feature->getFeaturePurpose() != $purpose) {
                continue;
            }
            $feature_ids[$feature_id] = $feature_id;
        }

        return $feature_ids;
    }

    /**
     * @param int $feature_id
     *
     * @return string|null
     */
    public function getFeaturePurpose($feature_id)
    {
        return isset($this->features[$feature_id]) ? $this->features[$feature_id]->getFeaturePurpose() : null;
    }

    /**
     * @param int $feature_id
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeature|null
     */
    public function getFeature($feature_id)
    {
        return $this->hasFeature($feature_id) ? $this->features[$feature_id] : null;
    }

    /**
     * @param string $feature_purpose
     *
     * @return bool
     */
    public function hasFeaturePurpose($feature_purpose)
    {
        foreach ($this->features as $feature) {
            if ($feature->getFeaturePurpose() == $feature_purpose) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->features);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        return next($this->features);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->features);
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        return key($this->features) !== null;
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        reset($this->features);
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return isset($this->features[$offset]);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return isset($this->features[$offset]) ? $this->features[$offset] : null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        $this->addFeature($value);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset): void
    {
        unset($this->features[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->features);
    }

    /**
     * @param bool $deep
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeature[]
     */
    public function toArray($deep = false)
    {
        if (!$deep) {
            return $this->features;
        }

        return array_map(function (GroupFeature $group_feature) {
            return $group_feature->toArray();
        }, $this->features);
    }

    /**
     * @param array $data_list
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection
     */
    public static function createFromFeatureList(array $data_list)
    {
        $self = new self();

        foreach ($data_list as $data) {
            try {
                $group_feature = GroupFeature::createFromArray($data);
                $self->addFeature($group_feature);
            } catch (InputException $exception) {
            }
        }

        return $self;
    }
}
