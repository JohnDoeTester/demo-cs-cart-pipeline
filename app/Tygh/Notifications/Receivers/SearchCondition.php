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

namespace Tygh\Notifications\Receivers;

use Tygh\Exceptions\DeveloperException;

/**
 * Class SearchCondition represents a message receiver search condition.
 *
 * @package Tygh\Notifications\Receivers
 */
class SearchCondition
{
    /**
     * @var string
     *
     * @see \Tygh\Enum\ReceiverSearchMethods
     */
    protected $method;

    /** @var string */
    protected $criterion;

    /**
     * ReceiverSearchCondition constructor.
     *
     *
     * @param string $method
     * @param string $criterion
     */
    public function __construct($method, $criterion)
    {
        $this->method = $method;
        $this->criterion = $criterion;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getCriterion()
    {
        return $this->criterion;
    }

    /**
     * @param array $data
     *
     * @return \Tygh\Notifications\Receivers\SearchCondition
     */
    public static function makeOne(array $data)
    {
        if (!isset($data['method']) || !isset($data['criterion'])) {
            throw new DeveloperException('`method` and `criterion` must be specified for \Tygh\Notifications\Receivers\SearchCondition');
        }

        return new self((string) $data['method'], (string) $data['criterion']);
    }

    /**
     * @param array $list
     *
     * @return \Tygh\Notifications\Receivers\SearchCondition[]
     */
    public static function makeList(array $list)
    {
        foreach ($list as &$data) {
            $data = static::makeOne($data);
        }
        unset($data);

        return $list;
    }
}
