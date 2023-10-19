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

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

/**
 * Installs alpha bank payment processor.
 *
 * @return void
 */
function fn_alpha_bank_install()
{
    /** @var \Tygh\Database\Connection $db */
    $db = Tygh::$app['db'];

    if ($db->getField('SELECT processor_id FROM ?:payment_processors WHERE processor_script = ?s', 'alpha_bank.php')) {
        return;
    }

    $db->query(
        'INSERT INTO ?:payment_processors ?e',
        [
            'processor'          => 'Alpha Bank',
            'processor_script'   => 'alpha_bank.php',
            'processor_template' => 'views/orders/components/payments/cc_outside.tpl',
            'admin_template'     => 'alpha_bank.tpl',
            'callback'           => 'N',
            'type'               => 'P',
            'addon'              => 'alpha_bank',
        ]
    );
}

/**
 * Disables alpha bank payment methods upon add-on uninstallation.
 *
 * @return void
 */
function fn_alpha_bank_uninstall()
{
    /** @var \Tygh\Database\Connection $db */
    $db = Tygh::$app['db'];

    $processor_id = $db->getField(
        'SELECT processor_id FROM ?:payment_processors WHERE processor_script = ?s',
        'alpha_bank.php'
    );

    if (!$processor_id) {
        return;
    }

    $db->query('DELETE FROM ?:payment_processors WHERE processor_id = ?i', $processor_id);
    $db->query(
        'UPDATE ?:payments SET ?u WHERE processor_id = ?i',
        [
            'processor_id'     => 0,
            'processor_params' => '',
            'status'           => 'D',
        ],
        $processor_id
    );
}
