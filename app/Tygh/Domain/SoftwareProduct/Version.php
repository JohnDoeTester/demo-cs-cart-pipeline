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


namespace Tygh\Domain\SoftwareProduct;

/**
 * Provides means to work with the product version, e.g., to perform version comparisons.
 *
 * @package Tygh\Domain\SoftwareProduct
 */
class Version
{
    /**
     * @var string $version Raw version representation
     */
    protected $version;

    /**
     * @var string $release Product release version
     */
    protected $release;

    /**
     * @var int $service_pack Product service pack version
     */
    protected $service_pack;

    /**
     * Version constructor.
     *
     * @param string $version Raw version representation
     */
    public function __construct($version)
    {
        $this->version = $version;
        $this->parseVersionComponents();
    }

    /**
     * Parses raw version data and sets up object fields.
     */
    protected function parseVersionComponents()
    {
        $version_components = explode('.sp', strtolower($this->version), 2);

        $this->release = $version_components[0];
        $this->service_pack = isset($version_components[1]) ? (int)$version_components[1] : 0;
    }

    /**
     * Provides release version.
     *
     * @return string
     */
    public function getRelease()
    {
        return $this->release;
    }

    /**
     * Provides service pack version.
     *
     * @return int
     */
    public function getServicePack()
    {
        return $this->service_pack;
    }

    /**
     * Performs comparison with another version object.
     *
     * @param Version     $version  Version to compare with
     * @param string|null $operator Comparison operator
     *
     * @return int|bool See ::version_compare()
     */
    public function compareWith(Version $version, $operator = null)
    {
        if (version_compare($this->getRelease(), $version->getRelease(), '=')) {
            return version_compare($this->getServicePack(), $version->getServicePack(), $operator);
        }

        return version_compare($this->getRelease(), $version->getRelease(), $operator);
    }

    /**
     * Checks if the version is greater than another one.
     *
     * @param Version $version Version to compare with
     *
     * @return bool
     */
    public function greaterThan(Version $version)
    {
        return $this->compareWith($version, '>');
    }

    /**
     * Checks if the version is greater or equals to another one.
     *
     * @param Version $version Version to compare with
     *
     * @return bool
     */
    public function greaterOrEqualsTo(Version $version)
    {
        return $this->compareWith($version, '>=');
    }

    /**
     * Checks if the version is lower than another one.
     *
     * @param Version $version Version to compare with
     *
     * @return bool
     */
    public function lowerThan(Version $version)
    {
        return $this->compareWith($version, '<');
    }

    /**
     * Checks if the version is lower or equals to another one.
     *
     * @param Version $version Version to compare with
     *
     * @return bool
     */
    public function lowerOrEqualsTo(Version $version)
    {
        return $this->compareWith($version, '<=');
    }

    /**
     * Checks if the version equals to another one.
     *
     * @param Version $version Version to compare with
     *
     * @return bool
     */
    public function equalsTo(Version $version)
    {
        return $this->compareWith($version, '=');
    }

    public static function compare($version1, $version2, $operator = null)
    {
        $version1 = new Version($version1);
        $version2 = new Version($version2);

        return $version1->compareWith($version2, $operator);
    }
}