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

namespace Tygh\NotificationsCenter\NotificationBuilders;

use Tygh\NotificationsCenter\IFactory;
use Tygh\Template\Collection;
use Tygh\Template\Internal\Context;
use Tygh\Template\Internal\Repository;
use Tygh\Template\Internal\Template;
use Tygh\Template\Renderer;

/**
 * Class DBTemplateNotificationBuilder builds on-site notifications based on the Twig templates from the database.
 *
 * @package Tygh\NotificationsCenter\NotificationBuilders
 */
class DBTemplateNotificationBuilder implements INotificationBuilder
{
    /**
     * @var \Tygh\Template\Renderer
     */
    protected $renderer;

    /**
     * @var \Tygh\Template\Internal\Repository
     */
    protected $template_repository;

    /**
     * @var \Tygh\NotificationsCenter\IFactory
     */
    protected $factory;

    public function __construct(IFactory $factory, Renderer $renderer, Repository $template_repository)
    {
        $this->factory = $factory;
        $this->renderer = $renderer;
        $this->template_repository = $template_repository;
    }

    public function createNotification($params, $area, $lang_code)
    {
        if (empty($params['template_code'])) {
            return null;
        }

        if (isset($params['template']) && $params['template'] instanceof Template) {
            $notification_template = $params['template'];
        } else {
            $notification_template = $this->getTemplate($params['template_code'], $area);
        }

        if (!$notification_template) {
            return null;
        }

        $context = $this->getContext($params['data'], $area, $lang_code);
        $collection = new Collection($context->data);

        $params['title'] = $this->renderer->render($notification_template->getSubject(), $collection->getAll());
        $params['message'] = $this->renderer->renderTemplate($notification_template, $context, $collection);

        return $this->factory->fromArray($params);
    }

    /**
     * Gets email template context.
     *
     * @param array  $data
     * @param string $area
     * @param string $lang_code
     *
     * @return \Tygh\Template\Internal\Context
     */
    protected function getContext($data, $area, $lang_code)
    {
        return new Context($data, $area, $lang_code);
    }

    /**
     * Get active email template model by template code and area
     *
     * @param string $code Code identifier of template
     * @param string $area Current working area
     *
     * @return \Tygh\Template\Internal\Template|null
     */
    public function getTemplate($code, $area)
    {
        return $this->template_repository->findActiveByCodeAndArea($code, $area);
    }
}
