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

namespace Tygh\Gdpr\DataCollector;

/**
 * The interface of the data collector class responsible for fetching data.
 *
 * @package Tygh\Gdpr\DataCollector
 */
interface IDataCollector
{
    /**
     * Collects user data
     *
     * @param array $params Parameters
     *
     * @return array
     */
    public function collect(array $params);
}
