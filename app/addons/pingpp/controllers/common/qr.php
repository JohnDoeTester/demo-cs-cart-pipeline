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

defined('BOOTSTRAP') or die('Access denied');

use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;

/** @var string $mode */

if ($mode == 'generate' && isset($_REQUEST['url'])) {
    $renderer = new Png();
    $renderer->setWidth(512);
    $renderer->setHeight(512);
    $renderer->setMargin(0);
    $writer = new Writer($renderer);


    header('Content-type: image/png');
    header('Content-Disposition: inline; filename="qr.png"');
    echo $writer->writeString($_REQUEST['url']);
    exit;
}