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


namespace Tygh\Addons\ProductVariations\Product\Group;

use Tygh\Tools\SecurityHelper;

/**
 * Class GroupCodeGenerator
 *
 * @package Tygh\Addons\ProductVariations\Product\Group
 */
class GroupCodeGenerator
{
    /**
     * @var \Tygh\Addons\ProductVariations\Product\Group\Repository
     */
    protected $repository;

    /**
     * @var \Tygh\Tools\SecurityHelper
     */
    protected $security_helper;

    /**
     * GroupCodeGenerator constructor.
     *
     * @param \Tygh\Addons\ProductVariations\Product\Group\Repository $repository
     * @param \Tygh\Tools\SecurityHelper                              $security_helper
     */
    public function __construct(Repository $repository, SecurityHelper $security_helper)
    {
        $this->repository = $repository;
        $this->security_helper = $security_helper;
    }

    public function next()
    {
        do {
            $code = $this->generate();
        } while ($this->repository->exists($code));

        return $code;
    }

    protected function generate()
    {
        return sprintf('PV-%s', strtoupper(substr($this->security_helper->generateRandomString(), 0, 9)));
    }
}