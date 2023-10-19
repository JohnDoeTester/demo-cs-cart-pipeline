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

namespace Tygh\Enum\Addons\Searchanise;

/**
 * Class QueueActions contains searchanise queue actions
 *
 * @package Tygh\Enum\Addons\Searchanise
 */
class QueueActions
{
    public const UPDATE_PRODUCTS     = 'update';
    public const UPDATE_CATEGORIES   = 'categories_update';
    public const UPDATE_PAGES        = 'pages_update';
    public const UPDATE_VENDORS      = 'vendors_update';
    public const UPDATE_FACETS       = 'facet_update';

    public const DELETE_PRODUCTS     = 'delete';
    public const DELETE_CATEGORIES   = 'categories_delete';
    public const DELETE_PAGES        = 'pages_delete';
    public const DELETE_VENDORS      = 'vendors_delete';
    public const DELETE_FACETS       = 'facet_delete';

    public const DELETE_PRODUCTS_ALL = 'delete_all';
    public const DELETE_FACETS_ALL   = 'facet_delete_all';

    public const PREPARE_FULL_IMPORT = 'prepare_full_import';
    public const START_FULL_IMPORT   = 'start_full_import';
    public const END_FULL_IMPORT     = 'end_full_import';
}
