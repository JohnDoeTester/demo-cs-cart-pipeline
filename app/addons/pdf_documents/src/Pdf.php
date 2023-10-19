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

use Tygh\Http;
use Tygh\Registry;
use Tygh\Tygh;

class Pdf
{
    /**
     * @var string
     */
    protected static $transaction_id;

    /**
     * @var string
     */
    protected static $url;

    /**
     * Pushes HTML code to batch to render PDF later.
     *
     * @param string $html HTML code
     *
     * @return bool true if transaction created, false - otherwise
     */
    public static function batchAdd($html)
    {
        $transaction_id = Http::post(
            self::action('/pdf/batch/add'),
            json_encode(
                [
                    'transaction_id' => !empty(self::$transaction_id)
                        ? self::$transaction_id
                        : '',
                    'content'        => self::convertImages($html),
                ]
            ),
            [
                'headers'         => [
                    'Content-type: application/json',
                    'Accept: application/json',
                ],
                'binary_transfer' => true,
            ]
        );

        if (!empty($transaction_id) && empty(self::$transaction_id)) {
            self::$transaction_id = json_decode($transaction_id);
        }

        return !empty($transaction_id);
    }

    /**
     * Renders PDF document by transaction ID
     *
     * @param string                $filename Filename to save PDF or name of attachment to download
     * @param bool                  $save     Saves to file if true, outputs if not
     * @param array<string, string> $params   Params to post along with request
     *
     * @return bool|void|no-return true if document saved, false on failure or outputs document
     *
     * @psalm-suppress InvalidReturnType,NoValue
     */
    public static function batchRender($filename = '', $save = false, array $params = [])
    {
        $default_params = [
            'transaction_id' => self::$transaction_id,
            'page_size'      => 'A4',
        ];

        $params = array_merge($default_params, $params);

        $file = fn_create_temp_file();

        $response = Http::post(
            self::action('/pdf/batch/render'),
            json_encode($params),
            [
                'headers'         => [
                    'Content-type: application/json',
                    'Accept: application/pdf',
                ],
                'binary_transfer' => true,
                'write_to_file'   => $file,
            ]
        );

        self::$transaction_id = null;

        if (!empty($response)) {
            return self::output($file, $filename, $save);
        }

        return false;
    }

    /**
     * Render PDF document from HTML code
     *
     * @param string|string[]       $html     HTML code
     * @param string                $filename Filename to save PDF or name of attachment to download
     * @param bool                  $save     Saves to file if true, outputs if not
     * @param array<string, string> $params   Params to post along with request
     *
     * @return bool|no-return true if document saved, false on failure or outputs document
     *
     * @psalm-suppress InvalidReturnType,NoValue
     */
    public static function render($html, $filename = '', $save = false, array $params = [])
    {
        if (is_array($html)) {
            $html = implode("<div style='page-break-before: always;'>&nbsp;</div>", $html);
        }

        if (self::isLocalIP(gethostbyname($_SERVER['HTTP_HOST']))) {
            $html = self::convertImages($html);
        }

        $default_params = [
            'content'   => $html,
            'page_size' => 'A4',
        ];

        $params = array_merge($default_params, $params);

        $file = fn_create_temp_file();

        $response = Http::post(
            self::action('/pdf/render'),
            json_encode($params),
            [
                'headers'         => [
                    'Content-type: application/json',
                    'Accept: application/pdf',
                ],
                'binary_transfer' => true,
                'write_to_file'   => $file,
            ]
        );

        if (!empty($response)) {
            return self::output($file, $filename, $save);
        }

        return false;
    }

    /**
     * Generates service URL
     *
     * @param string $action Action
     *
     * @return string formed URL
     */
    protected static function action($action)
    {
        return Tygh::$app['addons.pdf_documents.service_url'] . $action;
    }

    /**
     * Saves PDF document or outputs it
     *
     * @param string $file     File with PDF document
     * @param string $filename Filename to save PDF or name of attachment to download
     * @param bool   $save     Saves to file if true, outputs if not
     *
     * @return bool|no-return true if document saved, false on failure or outputs document
     */
    protected static function output($file, $filename = '', $save = false)
    {
        if (!empty($filename) && strpos($filename, '.pdf') === false) {
            $filename .= '.pdf';
        }

        if (!empty($filename) && $save) {
            return fn_rename($file, $filename);
        }

        if (!empty($filename)) {
            $filename = fn_basename($filename);
            header(fn_get_content_disposition_header($filename));
        }

        header('Content-type: application/pdf');
        readfile($file);
        fn_rm($file);
        exit(0);
    }

    /**
     * Converts images links to image:data attribute
     *
     * @param string $html HTML code
     *
     * @return string html code with converted links
     */
    protected static function convertImages($html)
    {
        $http_location = Registry::get('config.http_location');
        $https_location = Registry::get('config.https_location');
        $http_path = Registry::get('config.http_path');
        $https_path = Registry::get('config.https_path');
        $files = [];

        if (preg_match_all("/(?<=\ssrc=|\sbackground=)('|\")(.*)\\1/SsUi", $html, $matches)) {
            $files = fn_array_merge($files, $matches[2], false);
        }

        if (preg_match_all("/(?<=\sstyle=)('|\").*url\(('|\"|\\\\\\1)(.*)\\2\).*\\1/SsUi", $html, $matches)) {
            $files = fn_array_merge($files, $matches[3], false);
        }

        if (empty($files)) {
            return $html;
        }

        $files = array_unique($files);

        foreach ($files as $_path) {
            $path = str_replace('&amp;', '&', $_path);

            $real_path = '';
            // Replace url path with filesystem if this url is NOT dynamic
            if (strpos($path, '?') === false && strpos($path, '&') === false) {
                if (($i = strpos($path, $http_location)) !== false) {
                    $real_path = substr_replace(
                        $path,
                        Registry::get('config.dir.root'),
                        $i,
                        strlen($http_location)
                    );
                } elseif (($i = strpos($path, $https_location)) !== false) {
                    $real_path = substr_replace(
                        $path,
                        Registry::get('config.dir.root'),
                        $i,
                        strlen($https_location)
                    );
                } elseif (!empty($http_path) && ($i = strpos($path, $http_path)) !== false) {
                    $real_path = substr_replace($path, Registry::get('config.dir.root'), $i, strlen($http_path));
                } elseif (!empty($https_path) && ($i = strpos($path, $https_path)) !== false) {
                    $real_path = substr_replace($path, Registry::get('config.dir.root'), $i, strlen($https_path));
                }
            }

            if (empty($real_path)) {
                $real_path = (strpos($path, '://') === false)
                    ? $http_location . '/' . $path
                    : $path;
            }

            list($width, , $mime_type) = fn_get_image_size($real_path);
            if (empty($width)) {
                continue;
            }

            $content = fn_get_contents($real_path);
            $html = preg_replace(
                "/(['\"])" . str_replace('/', '\/', preg_quote($_path)) . "(['\"])/Ss",
                "\\1data:{$mime_type};base64," . base64_encode($content) . '\2',
                $html
            );
        }

        return $html;
    }

    /**
     * Checks if server IP address is local
     *
     * @param string $ip IP address
     *
     * @return bool true if IP is local, false - if public
     */
    protected static function isLocalIP($ip)
    {
        $ranges = [
            '10'  => [
                'min' => ip2long('10.0.0.0'),
                'max' => ip2long('10.255.255.255'),
            ],
            '192' => [
                'min' => ip2long('192.168.0.0'),
                'max' => ip2long('192.168.255.255'),
            ],
            '127' => [
                'min' => ip2long('127.0.0.0'),
                'max' => ip2long('127.255.255.255'),
            ],
            '172' => [
                'min' => ip2long('172.16.0.0'),
                'max' => ip2long('172.31.255.255'),
            ],
        ];

        $ip = ip2long($ip);

        foreach ($ranges as $range) {
            if ($ip >= $range['min'] && $ip <= $range['max']) {
                return true;
            }
        }

        return false;
    }
}
