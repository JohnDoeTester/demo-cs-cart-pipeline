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

use DateTimeImmutable;

/**
 * Instance of an SoftwareProductEnvironment class is intented to be used an immutable container for information
 * related to currently running software product licensing environment.
 *
 * The instance of this class will be registeted in an application container with "product.env" identifier:
 *
 * ```php
 * $environment = Tygh::$app['product.env'];
 * ```
 *
 * For example, the instance is passed to the Marketplace add-ons upgrade connector, providing information to the
 * Marketplace.
 *
 * @see \Tygh\Providers\EnvironmentProvider
 */
class SoftwareProductEnvironment
{
    /**
     * @var string The currently running product's name. This can be either "CS-Cart" or "Multivendor".
     */
    protected $product_name;

    /**
     * @var string The version of a product in a semantic versioning format, i.e. "x.x.x".
     */
    protected $product_version;

    /**
     * @var string A licensing mode currently being used. For CS-Cart this can be either "trial", "ultimate" and
     *      "professional". For Multivendor this can only accept one value - "full". Depending on this mode some
     *      store functionality is either accessible or not.
     */
    protected $store_mode;

    /**
     * @var string Development status of a product. This can be either "dev" - meaning it is a pre-release version, or
     *      an empty string, meaining it is a regular production-ready release.
     */
    protected $product_status;

    /**
     * @var string This parameter can be filled by an OEM resellers to segregate their own modified product. For
     *      example, the "CS-Cart Russian Build" fills this parameter with "ru" value.
     */
    protected $product_build;

    /**
     * @var string This is an obsolete legacy parameter which always accepts either the "ULTIMATE" value for the
     *      "CS-Cart" product and the "MULTIVENDOR" value for "Multivendor" product.
     */
    protected $product_edition;

    /**
     * @var int
     */
    protected $release_timestamp;

    /**
     * SoftwareProductEnvironment constructor.
     *
     * @param string $product_name      Display name
     * @param string $product_version   Version
     * @param string $store_mode        Store mode
     * @param string $product_status    Status
     * @param string $product_build     Build
     * @param string $product_edition   Edition
     * @param int    $release_timestamp Release timestamp
     */
    public function __construct(
        $product_name,
        $product_version,
        $store_mode,
        $product_status,
        $product_build,
        $product_edition,
        $release_timestamp = 0
    ) {
        $this->product_name = $product_name;
        $this->product_version = $product_version;
        $this->store_mode = $store_mode;
        $this->product_status = $product_status;
        $this->product_build = $product_build;
        $this->product_edition = $product_edition;
        $this->release_timestamp = $release_timestamp;
    }

    /**
     * @return string The currently running product's name. This can either be "CS-Cart" or "Multivendor".
     */
    public function getProductName()
    {
        return $this->product_name;
    }

    /**
     * @return string The version of a product in a semantic versioning format, i.e. "x.x.x".
     */
    public function getProductVersion()
    {
        return $this->product_version;
    }

    /**
     * @return string A licensing mode currently being used. For CS-Cart this can be either "trial", "ultimate" and
     *                "professional". For Multivendor this can only accept one value - "full". Depending on this mode
     *                some store functionality is either accessible or not.
     */
    public function getStoreMode()
    {
        return $this->store_mode;
    }

    /**
     * @return string Development status of a product. This can be either "dev" - meaning it is a pre-release version,
     *                or an empty string, meaining it is a regular production-ready release.
     */
    public function getProductStatus()
    {
        return $this->product_status;
    }

    /**
     * @return string This parameter can be filled by an OEM resellers to segregate their own modified product. For
     *                example, the "CS-Cart Russian Build" fills this parameter with "ru" value.
     */
    public function getProductBuild()
    {
        return $this->product_build;
    }

    /**
     * @return string This is an obsolete legacy parameter which always accepts either the "ULTIMATE" value for the
     *                "CS-Cart" product and the "MULTIVENDOR" value for "Multivendor" product. However, this is still
     *                exists and mustn't be deleted in order to preserve the backward compatibility.
     */
    public function getProductEdition()
    {
        return $this->product_edition;
    }

    /**
     * Gets release timestamp.
     *
     * @return int
     */
    public function getReleaseTimestamp()
    {
        return $this->release_timestamp;
    }

    /**
     * Gets readable message that describes how long ago was product released.
     *
     * @param int|null $now Current time
     *
     * @return array{message: string, params: array{0?: int, '[month]': string, '[year]': int}}
     */
    public function getReleaseTime($now = null)
    {
        $now = $now ?: time();

        if ($now < $this->release_timestamp) {
            $now = $this->release_timestamp;
        }

        /** @var \DateTimeImmutable $release_date */
        $release_date = DateTimeImmutable::createFromFormat('U', (string) $this->release_timestamp);
        /** @var \DateTimeImmutable $now_date */
        $now_date = DateTimeImmutable::createFromFormat('U', (string) $now);
        $diff = $release_date->diff($now_date);

        $params = [
            '[month]' => 'month_name_' . $release_date->format('n'),
            '[year]'  => (int) $release_date->format('Y'),
        ];

        if (!$diff) {
            return [
                'message' => 'product_env.released_this_month',
                'params'  => $params,
            ];
        }

        if ($diff->y > 0) {
            return [
                'message' => 'product_env.released_n_years_ago',
                'params'  => [$diff->y] + $params,
            ];
        }

        if ($diff->m > 0) {
            return [
                'message' => 'product_env.released_n_months_ago',
                'params'  => [$diff->m] + $params,
            ];
        }

        return [
            'message' => 'product_env.released_this_month',
            'params'  => $params,
        ];
    }
}
