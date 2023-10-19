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

namespace Tygh\Addons\VendorRating\Service;

use Tygh\Addons\VendorRating\Calculator\Calculator;
use Tygh\Addons\VendorRating\Criteria\CriteriaInterface;
use Tygh\Addons\VendorRating\Rating\Storage;
use Tygh\Application;
use Tygh\Exceptions\DeveloperException;

/**
 * Class VendorService provides means to operate vendors rating.
 *
 * @package Tygh\Addons\VendorRating\Service
 */
class VendorService
{
    const RATING_STORAGE_OBJECT_TYPE = 'company';
    const MIN_RATING = 0;

    /**
     * @var \Tygh\Addons\VendorRating\Calculator\Calculator
     */
    protected $calculator;

    /**
     * @var string
     */
    protected $formula;

    /**
     * @var array
     */
    protected $criteria_schema;

    /**
     * @var \Tygh\Application
     */
    protected $service_container;

    /**
     * @var int
     */
    protected $start_rating_period;

    /**
     * @var \Tygh\Addons\VendorRating\Rating\Storage
     */
    protected $rating_storage;

    /**
     * @var int
     */
    protected $max_rating = null;

    /**
     * Service constructor.
     *
     * @param \Tygh\Addons\VendorRating\Calculator\Calculator $calculator
     * @param \Tygh\Addons\VendorRating\Rating\Storage        $rating_storage
     * @param \Tygh\Application                               $service_container
     * @param string                                          $formula
     * @param array                                           $criteria_schema
     * @param int                                             $start_rating_period
     */
    public function __construct(
        Calculator $calculator,
        Storage $rating_storage,
        Application $service_container,
        $formula,
        array $criteria_schema,
        $start_rating_period
    ) {
        $this->calculator = $calculator;
        $this->rating_storage = $rating_storage;
        $this->service_container = $service_container;
        $this->formula = $formula;
        $this->start_rating_period = $start_rating_period;
        $this->criteria_schema = $criteria_schema;
    }

    /**
     * @param int $company_id
     *
     * @return int
     */
    public function getManualRating($company_id)
    {
        return $this->rating_storage->getManualRating($company_id, self::RATING_STORAGE_OBJECT_TYPE);
    }

    /**
     * @param int       $company_id
     * @param int|float $rating
     *
     * @return int
     */
    public function setManualRating($company_id, $rating)
    {
        $rating = $this->roundRating($rating);

        return $this->rating_storage->setManualRating($company_id, self::RATING_STORAGE_OBJECT_TYPE, $rating);
    }

    /**
     * @param int $company_id
     *
     * @return float
     * @throws \Tygh\Addons\VendorRating\Exception\CalculationException
     * @throws \Tygh\Addons\VendorRating\Exception\VariableCountException
     * @throws \Tygh\Addons\VendorRating\Exception\UnknownVariableException
     */
    public function calculateAbsoluteRating($company_id)
    {
        $used_criteria = $this->calculator->extractVariables($this->formula);

        $criteria_values = $this->collectCriteriaValues($company_id, $used_criteria);

        return $this->calculator->calculate($this->formula, $criteria_values);
    }

    /**
     * @param int $company_id
     *
     * @return int
     */
    public function getAbsouluteRating($company_id)
    {
        return $this->rating_storage->getAbsoluteRating($company_id, self::RATING_STORAGE_OBJECT_TYPE);
    }

    /**
     * @param int       $company_id
     * @param int|float $rating
     *
     * @return int
     */
    public function setAbsouluteRating($company_id, $rating)
    {
        $rating = $this->roundRating($rating);

        $this->resetMaxAbsoluteRating();

        return $this->rating_storage->setAbsoluteRating($company_id, self::RATING_STORAGE_OBJECT_TYPE, $rating);
    }

    /**
     * @param int $company_id
     *
     * @return int
     */
    public function getRelativeRating($company_id)
    {
        $absoulte_rating = $this->getAbsouluteRating($company_id);
        $max_absolute_rating = $this->getMaxAbsoluteRating();

        $relative_rating = (int) round($absoulte_rating * 100 / $max_absolute_rating);

        return min(100, $relative_rating);
    }

    /**
     * @param int $company_id
     * @param string[] $critiera
     *
     * @return int[]|float[]
     */
    protected function collectCriteriaValues($company_id, $critiera)
    {
        $values = [];

        foreach ($this->criteria_schema as $criterion) {
            $variable = $criterion['variable_name'];
            if (!in_array($variable, $critiera)) {
                continue;
            }

            list($provider_id, $method) = $criterion['value_provider'];
            /** @var \Tygh\Addons\VendorRating\Criteria\CriteriaInterface $value_provider */
            $value_provider = $this->service_container[$provider_id];
            if (!$value_provider instanceof CriteriaInterface) {
                throw new DeveloperException('Criteria provider must implement CriteriaInterface');
            }

            $value_provider->init($company_id, $this->start_rating_period);
            $value = call_user_func([$value_provider, $method]);

            $values[$variable] = $value;
        }

        return $values;
    }

    /**
     * @param int $company_id
     */
    public function deleteManualRating($company_id)
    {
        $this->rating_storage->deleteManualRating($company_id, self::RATING_STORAGE_OBJECT_TYPE);
    }

    /**
     * @param int $company_id
     */
    public function deleteAbsoluteRating($company_id)
    {
        $this->rating_storage->deleteAbsoluteRating($company_id, self::RATING_STORAGE_OBJECT_TYPE);
    }

    /**
     * @param int $company_id
     *
     * @return int
     */
    public function getAbsouluteRatingUpdatedAt($company_id)
    {
        return $this->rating_storage->getAbsoluteRatingUpdatedAt($company_id, self::RATING_STORAGE_OBJECT_TYPE);
    }

    /**
     * @param int|float $rating
     *
     * @return int
     */
    protected function roundRating($rating)
    {
        $rating = max($rating, self::MIN_RATING);

        $rating = (int) round($rating);

        return $rating;
    }

    /**
     * Resets cached max rating value.
     */
    protected function resetMaxAbsoluteRating()
    {
        $this->max_rating = null;
    }

    /**
     * Gets max rating value and caches it internally.
     *
     * @return int
     */
    protected function getMaxAbsoluteRating()
    {
        if ($this->max_rating === null) {
            $this->max_rating = $this->rating_storage->getMaxAbsoluteRating(self::RATING_STORAGE_OBJECT_TYPE);
        }
        if ($this->max_rating === 0) {
            $this->max_rating = 1;
        }

        return $this->max_rating;
    }
}
