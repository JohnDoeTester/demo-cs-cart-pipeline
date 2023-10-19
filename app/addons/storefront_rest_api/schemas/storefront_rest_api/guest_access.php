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

/**
 * Describes access to the REST API resources in the guest mode.
 * Caution: the guest mode may be unsafe. Even though there is an application key, that key may be compromised.
 *  That's why the schema must include `readonly` methods only.
 */

return [
    'pages'              => [
        'index' => true,
    ],
    'sra_profile_fields' => [
        'index' => true,
    ],
    'sra_profile'        => [
        'create' => true,
    ],
];