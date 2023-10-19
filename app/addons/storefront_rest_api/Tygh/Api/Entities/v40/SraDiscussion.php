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

namespace Tygh\Api\Entities\v40;

use Tygh\Registry;
use Tygh\Api\Response;
use Tygh\Addons\StorefrontRestApi\ASraEntity;
use Tygh\Enum\Addons\Discussion\DiscussionObjectTypes;
use Tygh\Enum\Addons\Discussion\DiscussionTypes;

class SraDiscussion extends ASraEntity
{
    /**
     * @var array $object_types
     */
    protected $object_types;

    /**
     * @var array $rating_values
     */
    protected $rating_values;

    /**
     * @var string $vendors_discussion_type
     */
    protected static $vendors_discussion_type;

    /** @inheritdoc * */
    public function index($id = '', $params = array())
    {
        if ($id) {
            return array(
                'status' => Response::STATUS_METHOD_NOT_ALLOWED,
                'data'   => array(
                    'message' => __('api_not_need_id'),
                ),
            );
        }

        $status = Response::STATUS_BAD_REQUEST;
        $data = array();

        $params = array_merge(array(
            'object_id'   => null,
            'object_type' => null,
            'get_posts'   => true,
            'params'      => array(),
        ), $params);

        $valid_params = true;
        if ($params['object_type'] === null) {
            $valid_params = false;
            $data['message'] = __('api_required_field', array(
                '[field]' => 'object_type',
            ));
        } elseif (!$this->isValidObjectType($params['object_type'])) {
            $valid_params = false;
            $data['message'] = __('api_invalid_value_w_valid_list', array(
                '[field]'      => 'object_type',
                '[value]'      => (string) $params['object_type'],
                '[valid_list]' => implode(', ', array_keys($this->object_types)),
            ));
        }

        if ($valid_params && $params['object_id'] === null) {
            $valid_params = false;
            $data['message'] = __('api_required_field', array(
                '[field]' => 'object_id',
            ));
        }

        if ($valid_params) {

            $data = $this->getThread(
                $params['object_id'],
                $params['object_type'],
                $params['get_posts'],
                $params['params']
            );

            if ($data && $data['type'] != DiscussionTypes::TYPE_DISABLED) {
                $status = Response::STATUS_OK; // comments are enabled
            } else {
                $status = Response::STATUS_FORBIDDEN; // comments are disabled or not configured for the object yet
                $data = array();
            }
        }

        return array(
            'status' => $status,
            'data'   => $data,
        );
    }

    /**
     * Checks whether object type is valid.
     *
     * @param string $type Type ID
     *
     * @return bool
     */
    protected function isValidObjectType($type)
    {
        $type = (string) $type;

        if ($this->object_types === null) {
            $this->object_types = fn_get_discussion_objects();
        }

        return isset($this->object_types[$type]);
    }

    /**
     * Gets thread associated with an object.
     *
     * @param int    $object_id   Discussed object ID
     * @param string $object_type Discussed object type
     * @param bool   $get_posts   Whether to fetch posts in thread
     * @param array  $params      Additional search params
     *
     * @return array|bool
     */
    protected function getThread($object_id, $object_type, $get_posts = false, array $params = array())
    {
        $discussion = fn_get_discussion($object_id, $object_type, $get_posts, $params);
        if (!$discussion) {
            return null;
        }

        if ($get_posts) {
            foreach ($discussion['posts'] as &$post) {
                unset($post['ip_address'], $post['status']);
            }
            unset($post);
        }

        $discussion['disable_adding'] = !$this->isPostingAllowed($discussion);
        $discussion['object_id'] = $object_id;
        $discussion['object_type'] = $object_type;

        return $discussion;
    }

    /** @inheritdoc */
    public function create($params)
    {
        $status = Response::STATUS_BAD_REQUEST;
        $data = array();

        $params = array_merge(array(
            'thread_id'    => null,
            'object_id'    => null,
            'object_type'  => null,
            'name'         => '',
            'rating_value' => 0,
            'message'      => '',
        ), $params);

        $params['rating_value'] = (int) $params['rating_value'];

        unset($params['post_id']);

        $valid_params = true;

        // validate object
        if (!$params['thread_id'] && !$params['object_type'] && !$params['object_id']) {
            $valid_params = false;
            $data['message'] = __('api_required_fields', array(
                '[fields]' => 'thread_id / object_id + object_type',
            ));
        }

        // validate name
        if ($valid_params && !fn_string_not_empty($params['name'])) {
            $valid_params = false;
            $data['message'] = __('api_required_field', array(
                '[field]' => 'name',
            ));
        }

        // validate thread existence
        $thread = $this->getThreadObject($params);

        if ($valid_params && !$thread) {
            $valid_params = false;
            $status = Response::STATUS_NOT_FOUND;
        }

        // check if posting is enabled and allowed
        if ($valid_params && !$this->isPostingAllowed($thread)) {
            $valid_params = false;
            $status = Response::STATUS_FORBIDDEN;
        }

        // check if object requires message
        if ($valid_params
            && !fn_string_not_empty($params['message'])
            && $this->isMessageRequired($thread['type'])
        ) {
            $valid_params = false;
            $data['message'] = __('api_required_field', array(
                '[field]' => 'message',
            ));
        }

        // check if object requires rating
        if ($valid_params
            && !$params['rating_value']
            && $this->isRatingRequired($thread['type'])
        ) {
            $valid_params = false;
            $data['message'] = __('api_required_field', array(
                '[field]' => 'rating_value',
            ));
        }

        // check that rating value is acceptable
        if ($valid_params
            && $params['rating_value']
            && !$this->isValidRatingValue($params['rating_value'])
        ) {
            $valid_params = false;
            $data['message'] = __('api_invalid_value_w_valid_list', array(
                '[field]'      => 'rating_value',
                '[value]'      => (string) $params['rating_value'],
                '[valid_list]' => implode(', ', array_keys($this->rating_values)),
            ));
        }

        // add post to thread
        if ($valid_params) {
            $params['thread_id'] = $thread['thread_id'];
            if ($post_id = fn_add_discussion_post($params)) {
                $status = Response::STATUS_CREATED;
                $data['thread_id'] = $params['thread_id'];
                $data['post_id'] = $post_id;
                $data['is_approval_required'] = $this->getDiscussionApprovalRequired($params);
            } else {
                $status = Response::STATUS_CONFLICT;
            }
        }

        return array(
            'status' => $status,
            'data'   => $data,
        );
    }

    /**
     * Gets discussion object.
     *
     * @param array $params Array containing 'thread_id' or 'object_type' and 'object_id'
     *
     * @return array|null
     */
    protected function getThreadObject(array $params)
    {
        if ($params['thread_id']) {
            if ($thread = fn_discussion_get_object(array('thread_id' => $params['thread_id']))) {
                $params = array_merge($params, $thread);
            } else {
                return null;
            }
        }

        return $this->getThread($params['object_id'], $params['object_type']);
    }

    /**
     * Determines whether a message text is required for the discussion type.
     *
     * @param string $discussion_type Discussion type ID (see \Tygh\Enum\Addons\StorefrontRestApi\DiscussionTypes)
     *
     * @return bool
     */
    protected function isMessageRequired($discussion_type)
    {
        return $discussion_type === DiscussionTypes::TYPE_COMMUNICATION
            || $discussion_type === DiscussionTypes::TYPE_COMMUNICATION_AND_RATING;
    }

    /**
     * Determines whether a rating value is required for the discussion type.
     *
     * @param string $discussion_type Discussion type ID (see \Tygh\Enum\Addons\StorefrontRestApi\DiscussionTypes)
     *
     * @return bool
     */
    protected function isRatingRequired($discussion_type)
    {
        return $discussion_type === DiscussionTypes::TYPE_RATING
            || $discussion_type === DiscussionTypes::TYPE_COMMUNICATION_AND_RATING;
    }

    /**
     * Checks whether posting to a thread is allowed.
     *
     * @param array $thread Thread object from \Tygh\Api\Entities\v40\SraDiscussion::getThread
     *
     * @return bool
     */
    protected function isPostingAllowed($thread)
    {
        $can_post = $thread['type'] !== DiscussionTypes::TYPE_DISABLED && empty($thread['disable_adding']);

        $is_review_for_product = isset($thread['object_type']) && $thread['object_type'] === DiscussionObjectTypes::PRODUCT;
        if ($can_post && $is_review_for_product) {
            $can_post = fn_discussion_is_user_eligible_to_write_review_for_product($this->auth['user_id'], $thread['object_id']);
        }

        $is_review_for_company = isset($thread['object_type']) && $thread['object_type'] === DiscussionObjectTypes::COMPANY;
        if ($can_post && $is_review_for_company) {
            $can_post = fn_discussion_is_user_eligible_to_write_review_for_company($this->auth['user_id'], $thread['object_id']);
        }

        if ($can_post && $this->area === 'C') {
            $can_post = fn_discussion_is_user_can_leave_review_from_ip($this->auth['ip'], $thread['object_type'], $thread['thread_id']);
        }

        return $can_post;
    }

    /**
     * Checks whether rating value is valid.
     *
     * @param int $value Rating value
     *
     * @return bool
     */
    protected function isValidRatingValue($value)
    {
        $value = (int) $value;

        if ($this->rating_values === null) {
            $this->rating_values = fn_get_discussion_ratings();
        }

        return isset($this->rating_values[$value]);
    }

    /** @inheritdoc */
    public function update($id, $params)
    {
        return array(
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        );
    }

    /** @inheritdoc */
    public function delete($id)
    {
        return array(
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        );
    }

    /** @inheritdoc */
    public function privilegesCustomer()
    {
        if (!static::isAddonEnabled()) {
            return array();
        }

        return array(
            'index'  => true,
            'create' => true,
            'update' => false,
            'delete' => false,
        );
    }

    /**
     * Checks whether the Comments and reviews add-on enabled.
     *
     * @return bool
     */
    public static function isAddonEnabled()
    {
        return Registry::ifGet('addons.discussion.status', 'D') === 'A';
    }

    /**
     * Sets missing discussion type to D.
     *
     * @param array  $object      Object to check
     * @param string $object_type Discussion object type
     *
     * @return array Object with discussion type set
     */
    public static function setDiscussionType(array $object, $object_type)
    {
        if (isset($object['discussion_type'])) {
            return $object;
        }

        if (static::$vendors_discussion_type === null) {
            static::$vendors_discussion_type = Registry::ifGet(
                'addons.discussion.company_discussion_type',
                DiscussionTypes::TYPE_DISABLED
            );
        }

        if ($object_type == DiscussionObjectTypes::COMPANY) {
            $object['discussion_type'] = static::$vendors_discussion_type;
        } else {
            $object['discussion_type'] = DiscussionTypes::TYPE_DISABLED;
        }

        return $object;
    }

    /**
     * Gets the admin approval requirement
     *
     * @param array $params Discussion message params
     *
     * @return bool Approval requirement value
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public function getDiscussionApprovalRequired(array $params)
    {
        $discussion_settings = Registry::get('addons.discussion');
        $discussion_object_types = fn_get_discussion_objects();
        $object_name = $discussion_object_types[$params['discussionType']];

        if (
            $discussion_settings[$object_name . '_post_approval'] === 'any'
            || $discussion_settings[$object_name . '_post_approval'] === 'anonymous' && !$this->auth['user_id']
        ) {
            return true;
        }

        return false;
    }
}
