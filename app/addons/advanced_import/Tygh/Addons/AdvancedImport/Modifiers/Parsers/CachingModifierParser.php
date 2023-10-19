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

namespace Tygh\Addons\AdvancedImport\Modifiers\Parsers;

/**
 * The class decorates a parser and allows to cache the parsed results.
 *
 * @package Tygh\Addons\AdvancedImport\Modifiers\Parsers
 */
class CachingModifierParser implements IModifierParser
{
    /** @var IModifierParser */
    protected $parser;

    /** @var array Array that contains parsed modifiers */
    protected $cache = array();

    /**
     * CachingModifierParser constructor.
     *
     * @param \Tygh\Addons\AdvancedImport\Modifiers\Parsers\IModifierParser $parser
     */
    public function __construct(IModifierParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @inheritdoc
     */
    public function parse($modifier)
    {
        $hash = $this->getModifierHash($modifier);

        if (!isset($this->cache[$hash])) {
            $this->cache[$hash] = $this->parser->parse($modifier);
        }

        return $this->cache[$hash];
    }

    /**
     * Generates modifier hash
     *
     * @param string $modifier Modifier
     *
     * @return string
     */
    protected function getModifierHash($modifier)
    {
        return md5(trim($modifier));
    }
}