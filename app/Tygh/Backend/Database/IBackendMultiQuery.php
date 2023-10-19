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

namespace Tygh\Backend\Database;

/**
 * Interface IBackendMultiQuery
 *
 * phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
 */
interface IBackendMultiQuery
{
    /**
     * Executes a multi query
     *
     * @param array<array-key, string> $multi_query The multi query to execute
     *
     * @return bool
     */
    public function multiQuery(array $multi_query);

    /**
     * Get a multi-query result
     *
     * @return mixed
     */
    public function getMultiQueryResult();

    /**
     * Check whether there are more results available
     *
     * @return bool
     */
    public function hasMoreResults();

    /**
     * Traverse to the next result
     *
     * @return bool
     */
    public function nextResult();
}
