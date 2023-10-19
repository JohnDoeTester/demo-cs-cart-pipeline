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

namespace Tygh\Addons\Gdpr\DataExtractor;

/**
 * The interface of the data extractor class responsible for extracting data from collection.
 *
 * @package Tygh\Addons\Gdpr\DataExtractor
 */
interface IDataExtractor
{
    /**
     * Extracts data from from collection
     *
     * @param array $params Parameters
     *
     * @return mixed
     */
    public function extract(array $params);
}
