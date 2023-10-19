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

namespace Tygh\Template\Document\Variables;

use Tygh\Template\Document\Order\Context;
use Tygh\Template\IActiveVariable;

/**
 * Class PickpupPointVariable provides pickup point data storage for templates of e-mail notifications and documents.
 *
 * @package Tygh\Template\Document\Variables
 */
class PickpupPointVariable extends GenericVariable implements IActiveVariable
{
    /** @inheritdoc */
    public function __construct(Context $context, array $config)
    {
        parent::__construct($context, $config);

        $order = $context->getOrder();
        $lang_code = $context->getLangCode();

        $this->data = [];
        $this->data['raw'] = [];

        $this->init($order->data, $lang_code);
    }

    /** @inheritdoc */
    public static function attributes()
    {
        return [
            'is_selected',
            'name',
            'phone',
            'full_address',
            'open_hours',
            'description',
            'raw' => [
                'open_hours',
                'description',
            ],
        ];
    }

    /**
     * Initializes pickup point data.
     *
     * @param array  $order     Order data
     * @param string $lang_code Two-letter language code
     */
    protected function init($order, $lang_code)
    {
        $is_selected = false;
        $name = $phone = $full_address = $open_hours = $description_raw = $description = '';
        $open_hours_raw = [];

        /**
         * Executes when rendering the document template and filling the pickup_point variable,
         * allows you to specify that a pickup point is selected as the shipping destination point and to set the pickup
         * point data such as its address, phone, open hours, etc.
         *
         * @param \Tygh\Template\Document\Variables\PickpupPointVariable $this            Variable instance
         * @param array                                                  $order           Order data
         * @param string                                                 $lang_code       Two-letter language code
         * @param bool                                                   $is_selected     Whether a pickup point is
         *                                                                                selected as the shipping
         *                                                                                destination point
         * @param string                                                 $name            Pickup point name
         * @param string                                                 $phone           Pickup point phone
         * @param string                                                 $full_address    Pickup point full address
         * @param string[]                                               $open_hours_raw  List of open hours
         * @param string                                                 $open_hours      Formatted open hours
         * @param string                                                 $description_raw Pickup point description
         * @param string                                                 $description     Formatted pickup point description
         */
        fn_set_hook(
            'pickup_point_variable_init',
            $this,
            $order,
            $lang_code,
            $is_selected,
            $name,
            $phone,
            $full_address,
            $open_hours_raw,
            $open_hours,
            $description_raw,
            $description
        );

        $this->data['is_selected'] = $is_selected;

        $this->data['name'] = $name;

        $this->data['phone'] = $phone;

        $this->data['full_address'] = $full_address;

        $this->data['raw']['open_hours'] = $open_hours_raw;
        $this->data['open_hours'] = $open_hours;

        $this->data['raw']['description'] = $description_raw;
        $this->data['description'] = $description;
    }
}
