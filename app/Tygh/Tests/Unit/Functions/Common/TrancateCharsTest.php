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

namespace Tygh\Tests\Unit\Functions\Common;


use Tygh\Tests\Unit\ATestCase;

class TruncateCharsTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        define('SECONDS_IN_HOUR', 60 * 60);

        $this->requireCore('functions/fn.common.php');
    }

    /**
     * @dataProvider dpTruncateChars
     */
    public function testTruncateChars($text, $limit, $ellipsis, $expected)
    {
        $this->assertEquals($expected, fn_truncate_chars($text, $limit, $ellipsis));
    }

    public function dpTruncateChars()
    {
        return [
            [
                "Текст описания\r\nТекст описания\r\nТекст описания\r\nТекст описания",
                30,
                '...',
                "Текст описания\r\nТекст..."
            ],
            [
                "Текст описания\nТекст описания\nТекст описания\nТекст описания",
                30,
                '___',
                "Текст описания\nТекст___"
            ],
            [
                "<ul class=\"desc_details\" style=\"margin-top: 0px; margin-bottom: 0px; margin-left: 0px; border-image: initial; outline-width: 0px; outline-style: initial; outline-color: initial; font-size: 11px; font-family: Verdana, Helvetica, Arial, sans-serif; vertical-align: baseline; list-style-position: initial; list-style-image: initial; color: #ffffff; line-height: 11px; padding: 0px; border: 0px initial initial;\"><li style=\"padding-bottom: 5px; border-image: initial; outline-width: 0px; outline-style: initial; outline-color: initial; font-style: inherit; font-family: inherit; vertical-align: baseline; line-height: 15px; clear: left; margin: 0px; border: 0px initial initial;\"><span style=\"color: #000000;\"><strong>Studio:</strong> Warner Home Video</span></li>",
                200,
                '...',
                "<ul class=\"desc_details\" style=\"margin-top: 0px; margin-bottom: 0px; margin-left: 0px; border-image: initial; outline-width: 0px; outline-style: initial; outline-color: initial; font-size: 11px; font-family:..."
            ],
            [
                "щ",
                1,
                '...',
                "щ"
            ],
            [
                "Текст описания\r\n",
                30,
                '...',
                "Текст описания\r\n"
            ],
        ];
    }
}