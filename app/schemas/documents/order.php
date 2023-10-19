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

return [
    'order'        => [
        'class'     => '\Tygh\Template\Document\Order\Variables\OrderVariable',
        'arguments' => ['#context', '#config', '@formatter'],
        'alias'     => 'o',
    ],
    'company'      => [
        'class'           => '\Tygh\Template\Document\Order\Variables\CompanyVariable',
        'alias'           => 'c',
        'email_separator' => '<br/>',
    ],
    'user'         => [
        'class'      => '\Tygh\Template\Document\Variables\GenericVariable',
        'alias'      => 'u',
        'data'       => function (\Tygh\Template\Document\Order\Context $context) {
            return $context->getOrder()->getUser();
        },
        'attributes' => function () {
            $attributes = ['email', 'firstname', 'lastname', 'phone'];
            $group_fields = fn_get_profile_fields('I');
            $sections = ['C', 'B', 'S'];

            foreach ($sections as $section) {
                if (isset($group_fields[$section])) {
                    foreach ($group_fields[$section] as $field) {
                        if (!empty($field['field_name'])) {
                            $attributes[] = $field['field_name'];

                            if (in_array($field['field_type'], ['A', 'O'])) {
                                $attributes[] = $field['field_name'] . '_descr';
                            }
                        }
                    }
                }

                $attributes[strtolower($section) . '_fields']['[0..N]'] = [
                    'name',
                    'value',
                ];
            }

            return $attributes;
        },
    ],
    'payment'      => [
        'class'      => '\Tygh\Template\Document\Variables\GenericVariable',
        'alias'      => 'p',
        'data'       => function (\Tygh\Template\Document\Order\Context $context) {
            $payment = $context->getOrder()->getPayment();

            if (empty($payment['surcharge_title'])) {
                $payment['surcharge_title'] = __('payment_surcharge', [], $context->getLangCode());
            }

            return $payment;
        },
        'attributes' => [
            'payment_id',
            'payment',
            'description',
            'payment_category',
            'surcharge_title',
            'instructions',
            'status',
            'a_surcharge',
            'p_surcharge',
            'processor',
            'processor_type',
            'processor_status',
        ],
    ],
    'settings'     => [
        'class' => '\Tygh\Template\Document\Variables\SettingsVariable',
    ],
    'currencies'   => [
        'class' => '\Tygh\Template\Document\Variables\CurrenciesVariable',
    ],
    'runtime'      => [
        'class' => '\Tygh\Template\Document\Variables\RuntimeVariable',
    ],
    'pickup_point' => [
        'class' => '\Tygh\Template\Document\Variables\PickpupPointVariable',
    ],
];
