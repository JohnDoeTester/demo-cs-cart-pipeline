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

namespace Tygh\Addons\PdfDocuments\HookHandlers;

use Tygh\Addons\PdfDocuments\Pdf;

class ShipmentsHookHandler
{
    /**
     * The "print_order_invoices_normalize_parameters" hook handler.
     *
     * Actions:
     * - Normalizes pdf parameter passed to the legacy function call.
     *
     * @param array<int, string|bool>    $args   Function arguments
     * @param array<string, string|bool> $params Normalized parameters
     *
     * @return void
     *
     * @see \fn_print_shipment_packing_slips()
     */
    public function printShipmentPackagingSlipsNormalizeParameters(array $args, &$params)
    {
        if (!isset($args[1])) {
            return;
        }

        $params['pdf'] = $args[1];
    }

    /**
     * The "print_order_invoices_pre" hook handler.
     *
     * Actions performed:
     * - Populates print parameters.
     * - Disables live editor.
     *
     * @param array<int>            $shipment_ids Order IDs to print invoices for
     * @param array<string, string> $params       Print parameters
     *
     * @return void
     *
     * @see \fn_print_shipment_packing_slips()
     *
     * @psalm-suppress ReferenceConstraintViolation
     */
    public function printShipmentPackagingSlipsPre(array $shipment_ids, array &$params)
    {
        $params = array_merge(
            [
                'pdf'  => false,
                'save' => false, // Save PDF
            ],
            $params
        );

        if (isset($params['format']) && $params['format'] === 'pdf') {
            $params['pdf'] = true;
        }

        if (!$params['pdf']) {
            return;
        }

        $params['add_page_break'] = false;

        fn_disable_live_editor_mode();
    }

    /**
     * The "print_order_invoices_post" hook handler.
     *
     * Actions performed:
     * - Renders shipment packaging slip into PDF.
     *
     * @param array<int>                 $shipment_ids Shipment IDs to print invoices for
     * @param array<string, string|bool> $params       Print parameters
     * @param array<string>              $html         Invoice HTML
     * @param string                     $output       Generated invoices
     *
     * @return void
     *
     * @see \fn_print_shipment_packing_slips()
     */
    public function printShipmentPackagingSlipsPost(array $shipment_ids, array $params, array $html, $output)
    {
        if (!$params['pdf']) {
            return;
        }

        Pdf::render($html, __('shipments') . '-' . implode('-', $shipment_ids));
    }
}
