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


namespace Tygh\Mailer;

use Exception;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * The class responsible for converting CSS styles of the message.
 * Convert css to inline styles.
 *
 * @package Tygh\Mailer
 */
class MessageStyleFormatter
{
    /**
     * Converts message css to inline styles
     *
     * @param Message $message Instance of message
     */
    public function convert(Message $message)
    {
        $inline_css = true;

        /**
         * Executes when converting inline CSS styles in a mail message, allows you to disable inline styles conversion.
         *
         * @param Message $message The message instance.
         * @param bool $inline_css Whether to inline the CSS in the message body.
         */
        fn_set_hook('message_style_formatter_convert_pre', $message, $inline_css);

        if (!$inline_css) {
            return;
        }
        
        $content = $message->getBody();
        if (preg_match('#\<style(.*?)\>(.*?)\</style\>#s', $content, $m)) {
            try {
                $ci = new CssToInlineStyles();
                $message->setBody($ci->convert(str_replace($m[0], '', $content), $m[2]));
                libxml_clear_errors();
            } catch (Exception $e) {
            }
        }

        /**
         * Executes after converting inline CSS styles in a mail message, do mind that this is not executed if the
         * styles conversion has been disabled prior using the 'message_style_formatter_convert_pre' hook.
         *
         * @param Message $message The message instance.
         */
        fn_set_hook('message_style_formatter_convert_post', $message);
    }
}
