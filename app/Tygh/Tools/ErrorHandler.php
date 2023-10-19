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


namespace Tygh\Tools;

use Tygh\Ajax;
use Tygh\Debugger;
use Tygh\Development;
use Tygh\Exceptions\AException;
use Tygh\Exceptions\DeveloperException;
use Throwable;
use Exception;

class ErrorHandler
{
    /**
     * Logs exception
     *
     * @param \Exception|\Error $exception Catched exception
     */
    public static function logException($exception)
    {
        error_log((string) self::castToException($exception));
    }

    /**
     * Shows error message
     *
     * @param \Exception|\Error $exception Catched exception
     */
    public static function showErrorMessage($exception)
    {
        $exception = self::castToException($exception);

        if (!defined('AJAX_REQUEST') && Ajax::validateRequest($_REQUEST)) {
            // Return valid JS in ajax requests if the 'fail' status was thrown before ajax initialization
            header('Content-type: application/json');
            $message = json_encode(['error' => $exception->getMessage()]);

            if (!empty($_REQUEST['callback'])) {
                $message = $_REQUEST['callback'] . "(" . $message . ");";
            }

            echo($message);
            exit;
        } elseif (defined('CONSOLE') || Debugger::isActive() || (defined('DEVELOPMENT') && DEVELOPMENT)) {
            echo self::getDebugInfo($exception, defined('CONSOLE'));
        } else {
            $debug = "<!--\n" . self::getDebugInfo($exception, true) . "\n-->";

            Development::showStub([
                '[title]'   => 'Service unavailable',
                '[banner]'  => 'Service<br/> unavailable',
                '[message]' => 'Sorry, service is temporarily unavailable.'
            ], $debug, true);
        }
    }

    /**
     * Gets debug info
     *
     * @param \Exception|\Error $exception  Catched exception
     * @param bool              $plain_text Whether to return plain text
     */
    public static function getDebugInfo($exception, $plain_text = false)
    {
        $exception = self::castToException($exception);

        $file = str_replace(DIR_ROOT . '/', '', $exception->getFile());
        $title = self::getErrorTitle($exception);

        $trace
            = <<< EOU
<div style="margin: 0 0 30px 0; font-size: 1em; padding: 0 10px;">
<h2>{$title}</h2>

<h3>Message</h3>
<p style="margin: 0; padding: 0 0 20px 0;">{$exception->getMessage()}</p>

<h3>Error at</h3>
<p style="margin: 0; padding: 0 0 20px 0;">{$file}, line: {$exception->getLine()}</p>

<h3>Backtrace</h3>
<table cellspacing="0" cellpadding="3" style="font-size: 0.9em;">
EOU;
        $i = 0;

        if ($backtrace = $exception->getTrace()) {
            $func = '';
            foreach ($backtrace as $v) {
                if (empty($v['file'])) {
                    $func = $v['function'];
                    continue;
                } elseif (!empty($func)) {
                    $v['function'] = $func;
                    $func = '';
                }
                $i = ($i == 0) ? 1 : 0;
                $color = ($i == 0) ? "#CCCCCC" : "#EEEEEE";
                if (strpos($v['file'], DIR_ROOT) !== false) {
                    $v['file'] = str_replace(DIR_ROOT . '/', '', $v['file']);
                }

                $trace .= "<tr bgcolor='$color'><td>File:</td><td>{$v['file']}</td></tr>\n";
                $trace .= "<tr bgcolor='$color'><td>Line:</td><td>{$v['line']}</td></tr>\n";
                $trace .= "<tr bgcolor='$color'><td>Function:</td><td><b>{$v['function']}</b></td></tr>\n\n";
            }
        }

        $trace .= '</table></div>';

        if ($plain_text) {
            $trace = strip_tags($trace);
        }

        return $trace;
    }

    /**
     * Handles exception
     *
     * @param \Exception|\Error $exception Catched exception
     */
    public static function handleException($exception)
    {
        $exception = self::castToException($exception);

        if (function_exists('fn_set_hook')) {
            /**
             * Allows to process a fatal exception.
             *
             * @param Throwable $exception The un-caught exception to be handled.
             */
            fn_set_hook('error_handler_handle_exception', $exception);
        }

        self::logException($exception);
        self::showErrorMessage($exception);
    }

    /**
     * Casts to instance of exception
     *
     * @param \Exception|\Error|string $exception Catched exception
     */
    protected static function castToException($exception)
    {
        if ($exception instanceof Throwable || $exception instanceof Exception) {
            return $exception;
        }

        return new DeveloperException((string) $exception);
    }

    /**
     * Gets error title
     *
     * @param \Exception|\Error $exception Catched exception
     *
     * @return string
     */
    protected static function getErrorTitle($exception)
    {
        if ($exception instanceof AException) {
            return $exception->getErrorTitle();
        }

        return get_class($exception);
    }
}
