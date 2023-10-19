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


use Tygh\Enum\ProfileTypes;
use Tygh\Enum\SiteArea;
use Tygh\Template\IActiveVariable;
use Tygh\Template\IContext;

/**
 * The class of the `company` variable; it allows access to company data in the document editor.
 *
 * @package Tygh\Template\Document\Variables
 */
class CompanyVariable implements IActiveVariable, \ArrayAccess
{
    /** @var array */
    public $logos = array();

    /** @var string */
    public $storefront_url;

    public $logo_mail = array();

    /** @var array */
    protected $data = array();

    /** @var array  */
    protected $config = array();

    /**
     * CompanyVariable constructor.
     *
     * @param array<string|array<string>> $config     Variable config.
     * @param int                         $company_id Company identifier.
     * @param string                      $lang_code  Language code.
     * @param IContext                    $context    Context.
     */
    public function __construct(array $config, $company_id, $lang_code, IContext $context)
    {
        $company_params = [];

        // Use multilingual company fields if context area is storefront.
        // If context doesn't contain area, use current area from constant.
        if (
            fn_allowed_for('MULTIVENDOR')
            && (!empty($context->getArea())
            && SiteArea::isStorefront($context->getArea())
            || empty($context->getArea())
            && SiteArea::isStorefront(AREA))
        ) {
            $company_params['use_i18n_company_fields'] = true;
        }

        $this->data = fn_get_company_placement_info($company_id, $lang_code, $company_params);
        $this->logos = fn_get_logos($company_id);

        if (fn_allowed_for('ULTIMATE')) {
            $this->storefront_url = fn_url('?company_id=' . $company_id, 'C');
        } else {
            $this->storefront_url = fn_url('', 'C');
        }

        if (fn_allowed_for('MULTIVENDOR')) {
            $company = fn_get_company_data($company_id, $lang_code);

            $fields = self::getProfileFields();

            foreach ($fields as $field) {
                $value = fn_get_profile_field_value($company, $field);

                if (in_array($field['field_type'], ['A', 'O'])) {
                    $this->data['company_' . $field['field_name'] . '_descr'] = $value;
                    $this->data['company_' . $field['field_name']] = isset($company[$field['field_name']]) ? $company[$field['field_name']] : $value;
                } else {
                    $this->data['company_' . $field['field_name']] = $value;
                }
            }

            if (
                !empty($context->getArea())
                && SiteArea::isStorefront($context->getArea())
                || empty($context->getArea())
                && SiteArea::isStorefront(AREA)
            ) {
                $this->data = fn_filter_company_data_by_profile_fields(
                    $this->data,
                    [
                        'field_prefix' => 'company_',
                    ]
                );
            }
        }

        if (!empty($this->logos['mail']['image'])) {
            $this->logo_mail = [
                'path'   => $this->logos['mail']['image']['image_path'],
                'alt'    => $this->logos['mail']['image']['alt'],
                'height' => $this->logos['mail']['image']['image_y'],
                'width'  => $this->logos['mail']['image']['image_x'],
            ];
        }
        $this->config = $config;

        $email_fields = isset($this->config['email_fields'])
            ? (array) $this->config['email_fields']
            : [
                'company_users_department', 'company_site_administrator', 'company_orders_department',
                'company_support_department', 'company_newsletter_email'
            ];

        foreach ($email_fields as $field) {
            $this->data[$field . '_display'] = strtr($this->data[$field], [',' => $this->config['email_separator'], ' ' => '']);
        }
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        $offset = $this->normalizeOffset($offset);

        return isset($this->data[$offset]);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        $offset = $this->normalizeOffset($offset);

        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        $offset = $this->normalizeOffset($offset);
        $this->data[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset): void
    {
        $offset = $this->normalizeOffset($offset);
        unset($this->data[$offset]);
    }

    /**
     * @param string $offset
     * @return string
     */
    protected function normalizeOffset($offset)
    {
        if (strpos($offset, 'default_') === 0) {
            $offset = 'company_default_' . substr($offset, 8);
        } else {
            $offset = 'company_' . $offset;
        }

        return $offset;
    }

    /**
     * @inheritDoc
     */
    public static function attributes()
    {
        $attributes = [
            'name', 'address', 'city', 'country', 'country_descr', 'state', 'state_descr', 'zipcode', 'phone', 'phone_2',
            'website', 'storefront_url', 'start_year', 'users_department', 'site_administrator', 'orders_department',
            'support_department', 'newsletter_email', 'users_department_display', 'site_administrator_display', 'orders_department_display',
            'support_department_display', 'newsletter_email_display', 'tax_number',
            'logo_mail' => [
                'path', 'alt', 'width', 'height'
            ],
            'logos' => [
                'theme' => [
                    'logo_id', 'layout_id', 'style_id', 'type',
                    'image' => [
                        'image_path', 'alt', 'image_x', 'image_y',
                        'http_image_path', 'https_image_path', 'absolute_path',
                        'relative_path'
                    ]
                ],
                'mail' => [
                    'logo_id', 'layout_id', 'style_id', 'type',
                    'image' => [
                        'image_path', 'alt', 'image_x', 'image_y',
                        'http_image_path', 'https_image_path', 'absolute_path',
                        'relative_path'
                    ]
                ],
                'favicon' => [
                    'logo_id', 'layout_id', 'style_id', 'type',
                    'image' => [
                        'image_path', 'alt', 'image_x', 'image_y',
                        'http_image_path', 'https_image_path', 'absolute_path',
                        'relative_path'
                    ]
                ]
            ]
        ];

        if (fn_allowed_for('MULTIVENDOR')) {
            $fields = self::getProfileFields();

            foreach ($fields as $field) {
                if (!empty($field['field_name'])) {
                    $attributes[] = $field['field_name'];

                    if (in_array($field['field_type'], array('A', 'O'))) {
                        $attributes[] = $field['field_name'] . '_descr';
                    }
                }
            }
        }

        return $attributes;
    }

    /**
     * @return array
     */
    protected static function getProfileFields()
    {
        $group_fields = fn_get_profile_fields('A', [], CART_LANGUAGE, [
            'profile_type' => ProfileTypes::CODE_SELLER,
            'get_custom'   => true
        ]);
        $section = 'C';

        return isset($group_fields[$section]) ? $group_fields[$section] : [];
    }
}