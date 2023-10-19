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

namespace Tygh\Twig;

use Tygh\Registry;
use Tygh\Template\Collection;
use Tygh\Template\Document\Service;
use Tygh\Template\ITemplate;
use Tygh\Tools\Url;
use Tygh\Template\IContext;
use Tygh\Template\Snippet\Service as SnippetService;
use Tygh\Template\Renderer as BaseRenderer;
use Tygh\Tygh;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * The extension class for the Twig template engine, that implements basic filters and functions.
 * @package Tygh\Twig
 */
class TwigCoreExtension extends AbstractExtension
{
    /** @inheritdoc */
    public function getFilters()
    {
        return array(
            new TwigFilter('date', [$this, 'dateFilter']),
            new TwigFilter('price', [$this, 'priceFilter']),
            new TwigFilter('filesize', [$this, 'filesizeFilter']),
            new TwigFilter('puny_decode', [$this, 'punyDecodeFilter'])
        );
    }

    /** @inheritdoc */
    public function getFunctions()
    {
        return array(
            new TwigFunction('__', [$this, 'translateFunction'], [
                'needs_environment' => true,
                'needs_context'     => true
            ]),
            new TwigFunction('snippet', [$this, 'snippetFunction'], [
                'needs_environment' => true,
                'needs_context'     => true
            ]),
            new TwigFunction('include_doc', [$this, 'includeDocFunction'], [
                'needs_environment' => true,
                'needs_context'     => true
            ]),
        );
    }

    /**
     * @param int|float $size
     * @return string
     */
    public function filesizeFilter($size)
    {
        if (empty($size)) {
            return 0;
        }

        $size = $size / 1024;
        return number_format($size, 0, '', '') . 'K';
    }

    /**
     * Formats date.
     *
     * @param int           $timestamp  UNIX timestamp
     * @param string|null   $format     Date format, similar to strftime format.
     *
     * @return string
     */
    public function dateFilter($timestamp, $format = null)
    {
        if ($format === null) {
            $format = sprintf(
                '%s, %s',
                Registry::get('settings.Appearance.date_format'),
                Registry::get('settings.Appearance.time_format')
            );
        }

        return fn_date_format($timestamp, $format);
    }

    /**
     * Formats price value.
     *
     * @param string $price     Price value
     * @param string $currency  Currency code (USD, EUR, etc). Default value - CART_PRIMARY_CURRENCY
     *
     * @return string
     */
    public function priceFilter($price, $currency = CART_PRIMARY_CURRENCY)
    {
        $currency =  Registry::get('currencies.' . $currency);
        $value = fn_format_rate_value(
            $price,
            null,
            $currency['decimals'],
            $currency['decimals_separator'],
            $currency['thousands_separator'],
            $currency['coefficient']
        );

        if ($currency['after'] == 'Y') {
            return $value . ' ' . $currency['symbol'];
        } else {
            return $currency['symbol'] . $value;
        }
    }

    /**
     * Puny decode filter.
     *
     * @param string $url
     * @return string
     */
    public function punyDecodeFilter($url)
    {
        return Url::decode($url, true);
    }

    /**
     * @param  \Twig\Environment $env          Twig configuration
     * @param string $context
     * @param string $name
     * @param array $placeholders
     * @return string
     */
    public function translateFunction($env, $context, $name, $placeholders = array())
    {
        return __($name, $placeholders, $context['lang_code']);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'tygh.core';
    }

    /**
     * @param TwigEnvironment $env
     * @param array $context
     * @param string $code
     * @param array $args
     * @return mixed
     */
    public function snippetFunction($env, $context, $code, $args = array())
    {
        if (isset($context[BaseRenderer::CONTEXT_VARIABLE_KEY])) {
            /** @var SnippetService $snippet_service */
            $snippet_service = Tygh::$app['template.snippet.service'];
            /** @var IContext $context_instance */
            $context_instance = $context[BaseRenderer::CONTEXT_VARIABLE_KEY];
            /** @var ITemplate $template_instance */
            $template_instance = $context[BaseRenderer::TEMPLATE_VARIABLE_KEY];
            /** @var Collection $variable_collection */
            $variable_collection = clone $context[BaseRenderer::VARIABLE_COLLECTION_VARIABLE_KEY];
            $type = $template_instance->getSnippetType();

            if (!empty($args)) {
                foreach ($args as $key => $val) {
                    $variable_collection->add($key, $val);
                }
            }

            return $snippet_service->renderSnippetByTypeAndCode($type, $code, $context_instance, $variable_collection);
        }

        return '';
    }

    /**
     * @param TwigEnvironment $env
     * @param array $context
     * @param string $code
     * @return string
     */
    public function includeDocFunction($env, $context, $code)
    {
        list($type_code, $template_code) = explode('.', $code, 2);

        if (empty($type_code) || empty($template_code)) {
            return '';
        }

        $params = array_slice(func_get_args(), 3);

        /** @var Service $service */
        $service = Tygh::$app['template.document.service'];

        try {
            return $service->includeDocument($type_code, $template_code, $params, $context['lang_code']);
        } catch (\Exception $e) {}

        return '';
    }
}