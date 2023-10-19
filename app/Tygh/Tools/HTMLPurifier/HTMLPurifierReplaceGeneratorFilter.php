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

namespace Tygh\Tools\HTMLPurifier;

use HTMLPurifier;
use HTMLPurifier_Config;
use HTMLPurifier_Context;
use HTMLPurifier_Exception;

class HTMLPurifierReplaceGeneratorFilter
{
    /**
     * @var HTMLPurifier
     */
    private $purifier;

    /**
     * @param HTMLPurifier $purifier HTML Purifier instance
     */
    public function __construct(HTMLPurifier $purifier)
    {
        $this->purifier = $purifier;
    }

    /**
     * Pre-processor function, handles HTML before HTML Purifier
     *
     * @param string               $html    HTML
     * @param HTMLPurifier_Config  $config  Config
     * @param HTMLPurifier_Context $context Context
     *
     * @return string
     */
    public function preFilter($html, HTMLPurifier_Config $config, HTMLPurifier_Context $context)
    {
        $replacer = function () use ($config, $context) {
            /** @psalm-suppress UndefinedThisPropertyAssignment */
            $this->generator = new HTMLPurifierGenerator($config, $context);
        };

        $replacer->call($this->purifier);

        return $html;
    }

    /**
     * Post-processor function, handles HTML after HTML Purifier
     *
     * @param string               $html    HTML
     * @param HTMLPurifier_Config  $config  Config
     * @param HTMLPurifier_Context $context Context
     *
     * @return string
     */
    public function postFilter($html, HTMLPurifier_Config $config, HTMLPurifier_Context $context)
    {
        return $html;
    }

    /**
     * Adds HTMLPurifier filter
     *
     * @param HTMLPurifier $purifier HTML Purifier instance
     *
     * @return void
     */
    public static function addFilter(HTMLPurifier $purifier)
    {
        $self = new self($purifier);

        $config = $purifier->config;

        try {
            $filters = (array) $config->get('Filter.Custom');
        } catch (HTMLPurifier_Exception $exception) {
            $filters = [];
        }

        $filters[] = $self;

        $config->set('Filter.Custom', $filters);
    }
}
