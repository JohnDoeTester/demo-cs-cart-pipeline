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

namespace Tygh\Notifications\Transports\Internal;


use Tygh\Notifications\Data;
use Tygh\Notifications\DataValue;
use Tygh\Notifications\Transports\BaseMessageSchema;

class InternalMessageSchema extends BaseMessageSchema
{
    /**
     * @var string|array
     */
    public $title;

    /**
     * @var string|array
     */
    public $message;

    /**
     * @var string
     */
    public $severity;

    /**
     * @var string
     */
    public $section;

    /**
     * @var string
     */
    public $area;

    /**
     * @var string
     */
    public $action_url;

    /**
     * @var bool
     */
    public $is_read;

    /**
     * @var int
     */
    public $timestamp;

    /**
     * @var string
     */
    public $tag;

    /**
     * @var string
     */
    public $language_code;

    /**
     * @var string
     */
    public $template_code;

    /**
     * @var int
     */
    public $to_company_id;

    /**
     * @var bool
     */
    public $pinned;

    /**
     * @var bool
     */
    public $remind;

    public function init(Data $data)
    {
        $self = parent::init($data);
        $data = new Data($self->data);

        if (is_array($self->title)) {
            $self->title = $self->getText($data, $self->title);
        }

        if (is_array($self->message)) {
            $self->message = $self->getText($data, $self->message);
        }

        if (!isset($self->timestamp)) {
            $self->timestamp = time();
        }

        return $self;
    }

    protected function getText(Data $data, array $language_variable)
    {
        $template = isset($language_variable['template']) ? $language_variable['template'] : '';
        $params = isset($language_variable['params']) ? $language_variable['params'] : [];

        if (!$template) {
            return '';
        }

        foreach ($params as $key => &$value) {
            if ($value instanceof DataValue) {
                $value = $this->retrieveDataValue($value, $data);
            }
        }
        unset($value);

        return __($template, $params, $this->language_code);
    }

    public static function create(array $schema)
    {
        $self = new self();

        $self->tag = self::get($schema, 'tag');
        $self->title = self::get($schema, 'title');
        $self->message = self::get($schema, 'message');
        $self->severity = self::get($schema, 'severity');
        $self->section = self::get($schema, 'section');
        $self->area = self::get($schema, 'area');
        $self->action_url = self::get($schema, 'action_url');
        $self->is_read = self::get($schema, 'is_read');
        $self->timestamp = self::get($schema, 'timestamp');
        $self->language_code = self::get($schema, 'language_code');
        $self->template_code = self::get($schema, 'template_code');
        $self->to_company_id = self::get($schema, 'to_company_id');
        $self->data_modifier = self::get($schema, 'data_modifier');
        $self->pinned = self::get($schema, 'pinned');
        $self->remind = self::get($schema, 'remind');

        return $self;
    }
}
