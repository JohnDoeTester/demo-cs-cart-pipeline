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
use Tygh\Enum\YesNo;
use Tygh\Mailer\ITransport;
use Tygh\Mailer\Mailer;
use Tygh\Mailer\Message;
use Tygh\Registry;
use Tygh\Tygh;

class OrdersHookHandler
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
     * @see \fn_print_order_invoices()
     */
    public function printOrderInvoicesNormalizeParameters(array $args, &$params)
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
     * @param array<int>                 $order_ids Order IDs to print invoices for
     * @param array<string, bool|string> $params    Print parameters
     *
     * @return void
     *
     * @see \fn_print_order_invoices()
     */
    public function printOrderInvoicesPre(array $order_ids, array &$params)
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
     * - Renders order invoice into PDF.
     *
     * @param array<int>                 $order_ids Order IDs to print invoices for
     * @param array<string, string|bool> $params    Print parameters
     * @param array<string>              $html      Invoice HTML
     * @param string                     $output    Generated invoices
     *
     * @return void
     *
     * @see \fn_print_order_invoices()
     */
    public function printOrderInvoicesPost(array $order_ids, array $params, array $html, &$output)
    {
        if (!$params['pdf']) {
            return;
        }

        $repository = Tygh::$app['template.document.repository'];
        $filename = __('invoices') . '-' . implode('-', $order_ids);

        if (isset($params['template_code'])) {
            $document = $repository->findByTypeAndCode('order', $params['template_code']);
            $filename = $document->getName() . '-' . implode('-', $order_ids);
        }
        if ($params['save']) {
            fn_mkdir(fn_get_files_dir_path());
            $filename = fn_get_files_dir_path() . $filename . '.pdf';
        }
        /** @var string $result */
        $result = Pdf::render($html, $filename, (bool) $params['save']);

        $output = $params['save']
            ? $filename
            : $result;
    }

    /**
     * The "print_order_packing_slips_normalize_parameters" hook handler.
     *
     * Actions:
     * - Normalizes pdf parameter passed to the legacy function call.
     *
     * @param array<int, string|bool>    $args   Function arguments
     * @param array<string, string|bool> $params Normalized parameters
     *
     * @return void
     *
     * @see \fn_print_order_packing_slips()
     */
    public function printOrderPackingSlipsNormalizeParameters(array $args, &$params)
    {
        if (!isset($args[1])) {
            return;
        }

        $params['pdf'] = $args[1];
    }

    /**
     * The "print_order_packing_slips_pre" hook handler.
     *
     * Actions performed:
     * - Populates print parameters.
     * - Disables live editor.
     *
     * @param array<int>                 $order_ids Order IDs to print slips for
     * @param array<string, string|bool> $params    Print parameters
     *
     * @return void
     *
     * @see \fn_print_order_packing_slips()
     *
     * @psalm-suppress ReferenceConstraintViolation
     */
    public function printOrderPackingSlipsPre(array $order_ids, array &$params)
    {
        $params = array_merge(
            [
                'pdf'  => false,
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
     * The "print_order_packing_slips_post" hook handler.
     *
     * Actions performed:
     * - Renders order invoice into PDF.
     *
     * @param array<int>                 $order_ids Order IDs to print invoices for
     * @param array<string, string|bool> $params    Print parameters
     * @param array<string>              $html      Invoice HTML
     * @param string                     $output    Generated invoices
     *
     * @return void
     *
     * @see \fn_print_order_packing_slips()
     */
    public function printOrderPackingSlipsPost(array $order_ids, array $params, array $html, $output)
    {
        if (!$params['pdf']) {
            return;
        }

        Pdf::render($html, __('packing_slip') . '-' . implode('-', $order_ids));
    }

    /**
     * The "send_order_invoice" hook handler.
     *
     * Actions performed:
     * - Renders PDF invoice and attaches it to a mail.
     *
     * @param array<string, string> $order_info  Order information
     * @param array<string, string> $params      Invoice parameters
     * @param string                $subject     Mail subject
     * @param string                $invoice     Invoice body
     * @param string                $email       Invoice receiver
     * @param array<string, string> $attachments Email attachments
     *
     * @return void
     *
     * @see \fn_send_order_invoice()
     */
    public function sendOrderInvoice(array $order_info, array $params, $subject, $invoice, $email, array &$attachments)
    {
        if (
            empty($params['attach'])
            || !YesNo::toBool($params['attach'])
        ) {
            return;
        }

        fn_mkdir(fn_get_files_dir_path());
        $filename = __('invoice') . '-' . $order_info['order_id'] . '.pdf';
        $filepath = fn_get_files_dir_path() . $filename;

        if (!Pdf::render($invoice, $filepath, true)) {
            return;
        }

        $attachments[$filename] = $filepath;
    }

    /**
     * The "mailer_send_pre" hook handler.
     *
     * Actions performed:
     * - Attaches PDF invoice to order notifications.
     *
     * @param \Tygh\Mailer\Mailer     $mailer    Mail service instance
     * @param \Tygh\Mailer\ITransport $transport Mail transport
     * @param \Tygh\Mailer\Message    $message   Sent message
     * @param string                  $area      Site area email is sent from
     * @param string                  $lang_code Language of the email
     *
     * @return void
     *
     * @see \Tygh\Mailer\Mailer::send()
     */
    public function sendMailPre(Mailer $mailer, ITransport $transport, Message $message, $area, $lang_code)
    {
        if (strpos($message->getId(), 'order_notification') !== 0) {
            return;
        }

        $data = $message->getData();
        $params = $message->getParams();
        if (empty($params['attach_order_document']) || empty($data['order_info']['order_id'])) {
            return;
        }

        $secondary_currency = CART_SECONDARY_CURRENCY;
        if (!empty($data['order_info']['secondary_currency']) && Registry::get("currencies.{$data['order_info']['secondary_currency']}")) {
            $secondary_currency = $data['order_info']['secondary_currency'];
        }

        $invoice_path = fn_print_order_invoices(
            $data['order_info']['order_id'],
            [
                'pdf'                => true,
                'save'               => true,
                'lang_code'          => $lang_code,
                'template_code'      => $params['attach_order_document'],
                'secondary_currency' => $secondary_currency,
                'area'               => $area,
            ]
        );

        $message->addAttachment($invoice_path, fn_basename($invoice_path));

        register_shutdown_function(static function () use ($invoice_path) {
            fn_rm($invoice_path);
        });
    }
}
