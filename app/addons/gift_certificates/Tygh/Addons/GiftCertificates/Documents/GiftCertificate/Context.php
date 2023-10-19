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


namespace Tygh\Addons\GiftCertificates\Documents\GiftCertificate;


use Tygh\Template\IContext;

/**
 * Class Context
 * @package Tygh\Addons\GiftCertifications\Documents\GiftCertificate
 */
class Context implements IContext
{
    /** @var string */
    protected $lang_code;

    /** @var array{products?: array<array<string|int>>, company_id?: int} */
    protected $gift_certificate_data;

    /** @var string */
    protected $area;

    /**
     * Context constructor.
     *
     * @param array{products?: array<array<string|int>>, company_id?: int} $gift_certificate_data Gift certificate data.
     * @param string                                                       $lang_code             Language code.
     * @param string                                                       $area                  Area identifier.
     */
    public function __construct(array $gift_certificate_data, $lang_code, $area = AREA)
    {
        if (!empty($gift_certificate_data['products']) && isset($gift_certificate_data['company_id'])) {
            foreach ($gift_certificate_data['products'] as &$product) {
                $product['company_id'] = $gift_certificate_data['company_id'];
            }

            unset($product);
        }
        $this->gift_certificate_data = $gift_certificate_data;
        $this->lang_code = $lang_code;
        $this->area = $area;
    }

    /**
     * Gets gift certificate data.
     *
     * @return array
     */
    public function getCertificateData()
    {
        return $this->gift_certificate_data;
    }

    /**
     * @inheritDoc
     */
    public function getLangCode()
    {
        return $this->lang_code;
    }

    /**
     * @inheritDoc
     */
    public function getArea()
    {
        return $this->area;
    }
}
