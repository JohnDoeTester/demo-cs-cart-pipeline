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

namespace Tygh\BlockManager;

/**
 * Trait TDeviceAvailabiltiy provides a set of methods to determine a block visibility on different devices.
 *
 * @package Tygh\BlockManager
 */
trait TDeviceAvailabiltiy
{
    /**
     * @var self
     */
    protected static $availability_instance;

    /**
     * Visibility classes
     *
     * FIXME: Probably should be loaded from a theme scheme.
     *
     * @var array
     */
    protected $default_hidden_classes = [
        'phone'   => ['hidden-phone'],
        'tablet'  => ['hidden-tablet'],
        'desktop' => ['hidden-desktop'],
    ];

    /**
     * Provides static instance of the class.
     *
     * @return \Tygh\BlockManager\TDeviceAvailabiltiy
     */
    public static function getAvailabilityInstance()
    {
        if (static::$availability_instance === null) {
            static::$availability_instance = new self;
        }

        return static::$availability_instance;
    }

    /**
     * Provides the class specific visibility CSS classes or the default classes if none specified.
     *
     * @return array
     */
    protected function getHiddenClasses()
    {
        if (isset($this->hidden_classes)) {
            return $this->hidden_classes;
        }

        return $this->default_hidden_classes;
    }

    /**
     * Provides device availability specification.
     *
     * @param array $item Block to check availability for
     *
     * @return array
     */
    public function getAvailability(array $item)
    {
        $item['user_class'] = isset($item['user_class'])
            ? $item['user_class']
            : '';

        $item_classes = array_filter(explode(' ', $item['user_class']), function ($class) {
            return trim($class) !== '';
        });

        $availability = [];
        foreach ($this->getHiddenClasses() as $device => $indicator_classes) {
            $availability[$device] = $this->isAvailable($item_classes, $indicator_classes);
        }

        return $availability;
    }

    /**
     * @param string[] $item_classes
     * @param string[] $hidden_classes
     *
     * @return bool
     */
    protected function isAvailable($item_classes, $hidden_classes)
    {
        $common_classes = array_intersect($item_classes, $hidden_classes);

        return count($common_classes) < count($hidden_classes);
    }

    /**
     * Provides CSS classes string that indicates if the block must be hidden on the specific device.
     *
     * @param string $device
     *
     * @return string
     */
    public function getHiddenClass($device)
    {
        $hidden_classes = $this->getHiddenClasses();
        if (isset($hidden_classes[$device])) {
            return implode(' ', $hidden_classes[$device]);
        }

        return '';
    }
}
