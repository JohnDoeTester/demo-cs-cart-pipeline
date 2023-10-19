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

namespace Tygh;

use Tygh\Enum\YesNo;

class SoftwareRestrictions
{
    /**
     * @var array{is_copyright_removal_restricted: bool, template?: string}
     */
    protected $copyright = [
        'is_copyright_removal_restricted' => false,
    ];

    /**
     * @param string|bool|array<string, array<string, string>> $restrictions Array of restrictions data
     *
     * @return void
     */
    public function __construct($restrictions)
    {
        if (!isset($restrictions['copyright']['is_copyright_removal_restricted'])) {
            return;
        }

        $restrictions['copyright']['is_copyright_removal_restricted'] = YesNo::toBool($restrictions['copyright']['is_copyright_removal_restricted']);
        $this->copyright = $restrictions['copyright'];
    }

    /**
     * @param string $html HTML document in string format.
     *
     * @return string $html
     */
    public function checkRestrictionsAndAddCopyright($html)
    {
        if ($this->isNoNeedToAddCopyright()) {
            return $html;
        }

        $close_body_tag_position = strpos($html, '</body>');
        $copyright = $this->getCopyright();

        if ($close_body_tag_position !== false) {
            $html = substr_replace($html, $copyright, $close_body_tag_position, 0);
        } else {
            $html .= $copyright;
        }

        return $html;
    }

    /**
     * @return bool
     */
    protected function isNeedToAddCopyright()
    {
        return $this->copyright['is_copyright_removal_restricted'];
    }

    /**
     * @return bool
     */
    protected function isNoNeedToAddCopyright()
    {
        return !$this->isNeedToAddCopyright();
    }

    /**
     * @return string
     */
    protected function getCopyright()
    {
        if (isset($this->copyright['template'])) {
            $copyright_template = $this->copyright['template'];

            return Tygh::$app['view']->fetch('string:' . $copyright_template);
        }

        return Tygh::$app['view']->fetch('backend:common/copyright.tpl');
    }
}
