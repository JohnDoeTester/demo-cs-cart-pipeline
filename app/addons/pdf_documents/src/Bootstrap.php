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

namespace Tygh\Addons\PdfDocuments;

use Tygh\Core\ApplicationInterface;
use Tygh\Core\BootstrapInterface;
use Tygh\Core\HookHandlerProviderInterface;

class Bootstrap implements BootstrapInterface, HookHandlerProviderInterface
{
    /** @inheritDoc */
    public function boot(ApplicationInterface $app)
    {
        $app->register(new ServiceProvider());
    }

    /** @inheritDoc */
    public function getHookHandlerMap()
    {
        return [
            /** @see \Tygh\Addons\PdfDocuments\HookHandlers\OrdersHookHandler::printOrderInvoicesNormalizeParameters() */
            'print_order_invoices_normalize_parameters'      => [
                'addons.pdf_documents.hook_handlers.orders',
                'printOrderInvoicesNormalizeParameters',
            ],
            /** @see \Tygh\Addons\PdfDocuments\HookHandlers\OrdersHookHandler::printOrderInvoicesPre() */
            'print_order_invoices_pre'                       => [
                'addons.pdf_documents.hook_handlers.orders',
                'printOrderInvoicesPre',
            ],
            /** @see \Tygh\Addons\PdfDocuments\HookHandlers\OrdersHookHandler::printOrderInvoicesPost() */
            'print_order_invoices_post'                      => [
                'addons.pdf_documents.hook_handlers.orders',
                'printOrderInvoicesPost',
            ],
            /** @see \Tygh\Addons\PdfDocuments\HookHandlers\OrdersHookHandler::printOrderPackingSlipsNormalizeParameters() */
            'print_order_packing_slips_normalize_parameters' => [
                'addons.pdf_documents.hook_handlers.orders',
                'printOrderPackingSlipsNormalizeParameters',
            ],
            /** @see \Tygh\Addons\PdfDocuments\HookHandlers\OrdersHookHandler::printOrderPackingSlipsPre() */
            'print_order_packing_slips_pre'                  => [
                'addons.pdf_documents.hook_handlers.orders',
                'printOrderPackingSlipsPre',
            ],
            /** @see \Tygh\Addons\PdfDocuments\HookHandlers\OrdersHookHandler::printOrderPackingSlipsPost() */
            'print_order_packing_slips_post'                 => [
                'addons.pdf_documents.hook_handlers.orders',
                'printOrderPackingSlipsPost',
            ],
            /** @see \Tygh\Addons\PdfDocuments\HookHandlers\OrdersHookHandler::sendOrderInvoice() */
            'send_order_invoice'                             => [
                'addons.pdf_documents.hook_handlers.orders',
                'sendOrderInvoice',
            ],
            /** @see \Tygh\Addons\PdfDocuments\HookHandlers\OrdersHookHandler::sendMailPre() */
            'mailer_send_pre'                                => [
                'addons.pdf_documents.hook_handlers.orders',
                'sendMailPre',
            ],

            /** @see \Tygh\Addons\PdfDocuments\HookHandlers\ShipmentsHookHandler::printShipmentPackagingSlipsNormalizeParameters() */
            'print_shipment_packing_slips_normalize_params'  => [
                'addons.pdf_documents.hook_handlers.shipments',
                'printShipmentPackagingSlipsNormalizeParameters',
            ],
            /** @see \Tygh\Addons\PdfDocuments\HookHandlers\ShipmentsHookHandler::printShipmentPackagingSlipsPre() */
            'print_shipment_packing_slips_pre'               => [
                'addons.pdf_documents.hook_handlers.shipments',
                'printShipmentPackagingSlipsPre',
            ],
            /** @see \Tygh\Addons\PdfDocuments\HookHandlers\ShipmentsHookHandler::printShipmentPackagingSlipsPost() */
            'print_shipment_packing_slips_post'              => [
                'addons.pdf_documents.hook_handlers.shipments',
                'printShipmentPackagingSlipsPost',
            ],
        ];
    }
}
