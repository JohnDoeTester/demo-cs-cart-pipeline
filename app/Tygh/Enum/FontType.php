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

namespace Tygh\Enum;

/**
 * Contains font types.
 *
 * @package Tygh\Enum
 */
class FontType
{
    const WOFF = 'font/woff';
    const WOFF2 = 'font/woff2';
    const TTF = 'font/ttf';
    const OTF = 'font/otf';
    const SVG = 'image/svg+xml';
    const FALLBACK = null;

    const EXT_WOFF = 'woff';
    const EXT_WOFF2 = 'woff2';
    const EXT_TTF = 'ttf';
    const EXT_OTF = 'otf';
    const EXT_SVG = 'svg';

    /**
     * Gets font types sorted by their support, the most supported first.
     *
     * @return array<string|null>
     */
    public static function getAllBySupport()
    {
        // woff2 and woff seem to be the most supported font types, so use them first
        return [
            self::WOFF2,
            self::WOFF,
            self::TTF,
            self::OTF,
            self::SVG,
            self::FALLBACK,
        ];
    }

    /**
     * Checks whether type is the fallback one.
     *
     * @param string|null $type Font type
     *
     * @return bool
     */
    public static function isFallback($type)
    {
        return $type === self::FALLBACK;
    }

    /**
     * Gets font type by font file extension.
     *
     * @param string $extension Extension
     *
     * @return string|null
     */
    public static function getByExtension($extension)
    {
        switch ($extension) {
            case self::EXT_WOFF2:
                return self::WOFF2;
            case self::EXT_WOFF:
                return self::WOFF;
            case self::EXT_TTF:
                return self::TTF;
            case self::EXT_OTF:
                return self::OTF;
            case self::EXT_SVG:
                return self::SVG;
            default:
                return self::FALLBACK;
        }
    }
}
