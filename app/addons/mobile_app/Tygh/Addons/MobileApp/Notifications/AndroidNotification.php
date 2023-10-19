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

namespace Tygh\Addons\MobileApp\Notifications;

class AndroidNotification implements INotification
{
    /**
     * @var string $target_screen
     */
    protected $target_screen;

    /**
     * @var string $sound
     */
    protected $sound = 'default';

    /**
     * @var string $title
     */
    protected $title;

    /**
     * @var string $message
     */
    protected $message;

    /**
     * @var string $icon
     */
    protected $icon = 'ic_notification';

    /**
     * @var string $priority
     */
    protected $priority = 10;

    /**
     * @var bool $show_in_foreground
     */
    protected $show_in_foreground = true;

    /** @inheritdoc */
    public function getBody()
    {
        return array(
            'notification' => array(
                'body'               => $this->getMessage(),
                'title'              => $this->getTitle(),
                'icon'               => $this->icon,
                'sound'              => $this->sound,
                'show_in_foreground' => $this->show_in_foreground,
            ),
            'data'         => array(
                'targetScreen' => $this->getTargetScreen(),
            ),
            'priority'     => $this->priority,
        );
    }

    /** @inheritdoc */
    public function setTargetScreen($screen)
    {
        $this->target_screen = $screen;
    }

    /** @inheritdoc */
    public function getTargetScreen()
    {
        return $this->target_screen;
    }

    /** @inheritdoc */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /** @inheritdoc */
    public function getTitle()
    {
        return $this->title;
    }

    /** @inheritdoc */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /** @inheritdoc */
    public function getMessage()
    {
        return $this->message;
    }
}