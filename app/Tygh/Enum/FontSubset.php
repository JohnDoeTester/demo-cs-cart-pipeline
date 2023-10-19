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
 * Contains unicode range definitions used by Google Fonts in font subsets.
 *
 * @package Tygh\Enum
 */
class FontSubset
{
    const LATIN = 'U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD';
    const LATIN_EXT = 'U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF';
    const CYRILLIC = 'U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116';
    const CYRILLIC_EXT = 'U+0460-052F, U+1C80-1C88, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F';
    const GREEK = 'U+0370-03FF';
    const GREEK_EXT = 'U+1F00-1FFF';
    const VIETNAMESE = 'U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+1EA0-1EF9, U+20AB';

    /**
     * Gets subsets used in the language.
     *
     * @param string $language_code Two-letter language code
     *
     * @return array<string> Subsets by their priority
     */
    public static function getByLanguageUsage($language_code)
    {
        switch (strtolower($language_code)) {
            case 'ru':
            case 'uk':
            case 'be':
            case 'kk':
                $subsets = [self::CYRILLIC, self::LATIN, self::CYRILLIC_EXT];
                break;
            case 'vi':
                $subsets = [self::VIETNAMESE, self::LATIN];
                break;
            case 'el':
                $subsets = [self::GREEK, self::LATIN, self::GREEK_EXT];
                break;
            default:
                $subsets = [self::LATIN];
        }

        /**
         * Executes after font subset used by a language is determined, allows you to add or remove subsets.
         *
         * @param string        $language_code Two-letter language code
         * @param array<string> $subsets       Subsets by their priority
         */
        fn_set_hook('font_subset_get_by_language_usage_post', $language_code, $subsets);

        return $subsets;
    }
}
