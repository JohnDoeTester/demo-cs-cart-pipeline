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

use Tygh\Enum\UserTypes;
use Tygh\Mailer\Message;
use Tygh\Notifications\Transports\Mail\MailTransport;
use Tygh\Providers\EventDispatcherProvider;
use Tygh\Registry;
use Tygh\Template\Collection;
use Tygh\Template\Internal\Context as NotificationContext;
use Tygh\Template\Internal\Template;
use Tygh\Tools\Url;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * @var string $mode
 */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    fn_trusted_vars('internal_template', 'snippet_data');

    if ($mode == 'update') {
        if (empty($_REQUEST['template_id'])) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        /** @var \Tygh\Template\Internal\Service $service */
        $service = Tygh::$app['template.internal.service'];
        /** @var \Tygh\Template\Internal\Repository $repository */
        $repository = Tygh::$app['template.internal.repository'];

        $template = $repository->findById($_REQUEST['template_id']);

        if (!$template) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        $result = $service->updateTemplate($template, $_REQUEST['internal_template']);

        if (!$result->isSuccess()) {
            fn_save_post_data('internal_template');
            $result->showNotifications();
        }

        return array(CONTROLLER_STATUS_OK, 'internal_templates.update?template_id=' . $template->getId());
    }

    if ($mode == 'preview') {
        /** @var \Tygh\Template\Renderer $renderer */
        $renderer = Tygh::$app['template.renderer'];
        /** @var \Tygh\Template\Internal\Repository $repository */
        $repository = Tygh::$app['template.internal.repository'];
        /** @var \Tygh\SmartyEngine\Core $view */
        $view = Tygh::$app['view'];

        $internal_template = $repository->findById($_REQUEST['template_id']);

        if (!$internal_template) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        if (isset($_REQUEST['internal_template'])) {
            if (isset($_REQUEST['internal_template']['template'])) {
                $result = $renderer->validate($_REQUEST['internal_template']['template']);

                if (!$result->isSuccess()) {
                    $result->showNotifications();
                    exit;
                }
            }

            $internal_template->loadFromArray($_REQUEST['internal_template']);
        }

        $variables = $renderer->retrieveVariables($internal_template->getTemplate() . "\n" . $internal_template->getSubject());
        $variables = array_combine($variables, $variables);

        $context = new NotificationContext($variables, $internal_template->getArea(), DESCR_SL);
        $collection = new Collection($context->data);

        $message = new Message();
        $message->setBody($renderer->renderTemplate($internal_template, $context, $collection));
        $message->setSubject($renderer->render($internal_template->getSubject(), $collection->getAll()));

        $view->assign('preview', $message);
        $view->display('views/internal_templates/preview.tpl');
        exit;
    }

    if ($mode == 'restore') {
        /** @var \Tygh\Template\Internal\Service $service */
        $service = Tygh::$app['template.internal.service'];
        /** @var \Tygh\Template\Internal\Repository $repository */
        $repository = Tygh::$app['template.internal.repository'];

        $template_id = isset($_REQUEST['template_id']) ? (int) $_REQUEST['template_id'] : 0;
        $template = $repository->findById($template_id);

        if (!$template) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        if ($service->restoreTemplate($template)) {
            fn_set_notification('N', __('notice'), __('text_changes_saved'));
        }

        if (!empty($_REQUEST['return_url'])) {
            return array(CONTROLLER_STATUS_REDIRECT, $_REQUEST['return_url']);
        }
    }

    if ($mode == 'send') {
        /** @var \Tygh\NotificationsCenter\NotificationsCenter $notifications_center */
        $notifications_center = Tygh::$app['notifications_center'];
        /** @var \Tygh\Template\Renderer $renderer */
        $renderer = Tygh::$app['template.renderer'];
        /** @var \Tygh\Template\Internal\Repository $repository */
        $repository = Tygh::$app['template.internal.repository'];

        $internal_template = $repository->findById($_REQUEST['template_id']);
        $internal_template->loadFromArray($_REQUEST['internal_template']);

        $user_data = fn_get_user_info(Tygh::$app['session']['auth']['user_id']);

        $variables = $renderer->retrieveVariables($internal_template->getSubject() . "\n" . $internal_template->getTemplate());
        $variables = array_combine($variables, $variables);

        $notifications_center->add([
            'template'      => $internal_template,
            'template_code' => $internal_template->getCode(),
            'data'          => $variables,
            'area'          => 'A',
            'user_id'       => $auth['user_id'],
            'language_code' => Registry::get('settings.Appearance.backend_default_language'),
        ]);

        exit(0);
    }

    if ($mode == 'export') {
        /** @var \Tygh\Template\Internal\Exim $exim */
        $exim = \Tygh::$app['template.internal.exim'];

        try {
            $xml = $exim->exportAllToXml();

            $filename = 'internal_templates_' . date("m_d_Y") . '.xml';
            $file_path = Registry::get('config.dir.files') . $filename;

            fn_mkdir(dirname($file_path));
            fn_put_contents($file_path, $xml);
            fn_get_file($file_path);

        } catch (Exception $e) {
            fn_set_notification('E', __('error'), $e->getMessage());
        }
    }

    if ($mode == 'import') {
        /** @var \Tygh\Template\Internal\Exim $exim */
        $exim = \Tygh::$app['template.internal.exim'];

        $data = fn_filter_uploaded_data('filename', array('xml'));
        $file = reset($data);

        if (!empty($file['path'])) {
            try {
                $result = $exim->importFromXmlFile($file['path']);
                $counter = $result->getData();

                /** @var \Smarty $smarty */
                $smarty = Tygh::$app['view'];

                $smarty->assign('import_result', array(
                    'count_success_templates' => $counter['success_templates'],
                    'count_success_snippets' => $counter['success_snippets'],
                    'count_fail_templates' => $counter['fail_templates'],
                    'count_fail_snippets' => $counter['fail_snippets'],
                    'errors' => $result->getErrors(),
                ));

                fn_set_notification(
                    'I',
                    __('import_results'),
                    $smarty->fetch('views/internal_templates/components/import_summary.tpl')
                );
            } catch (Exception $e) {
                fn_set_notification('E', __('error'), $e->getMessage());
            }
        }
    }

    return array(CONTROLLER_STATUS_OK, 'internal_templates.manage');
}

if ($mode == 'manage') {
    /** @var \Tygh\Template\Internal\Repository $repository */
    $repository = Tygh::$app['template.internal.repository'];
    /** @var \Tygh\Template\Snippet\Repository $snippet_repository */
    $snippet_repository = Tygh::$app['template.snippet.repository'];
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $internal_templates = $repository->find();

    Registry::set('navigation.tabs', array(
        'internal_templates_A' => array(
            'title' => __('admin_notifications'),
            'js' => true,
        ),
    ));

    // group by area
    $groups = array();
    foreach ($internal_templates as $internal_template) {
        $groups[$internal_template->getArea()][] = $internal_template;
    }

    foreach ($groups as $group_id => $templates) {
        usort($groups[$group_id], function (Template $template_a, Template $template_b) {
            return strcmp($template_a->getName(), $template_b->getName());
        });
    }

    $view->assign('snippets', $snippet_repository->findByType('internal'));
    $view->assign('groups', $groups);
} elseif ($mode == 'update') {
    /** @var \Tygh\Template\Internal\Repository $repository */
    $repository = Tygh::$app['template.internal.repository'];
    /** @var \Tygh\Template\Snippet\Repository $snippet_repository */
    $snippet_repository = Tygh::$app['template.snippet.repository'];
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    /** @var \Tygh\Template\Renderer $renderer */
    $renderer = Tygh::$app['template.renderer'];

    if (empty($_REQUEST['template_id']) && (empty($_REQUEST['code']) || empty($_REQUEST['area']))) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    if (!empty($_REQUEST['template_id'])){
        $internal_template = $repository->findById($_REQUEST['template_id']);
    } elseif (!empty($_REQUEST['code']) && !empty($_REQUEST['area'])) {
        $internal_template = $repository->findByCodeAndArea($_REQUEST['code'], $_REQUEST['area']);
    }

    if (!$internal_template) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $default_subject = $internal_template->getDefaultSubject() ? $internal_template->getDefaultSubject() : $internal_template->getSubject();
    $default_template = $internal_template->getDefaultTemplate() ? $internal_template->getDefaultTemplate() : $internal_template->getTemplate();

    $snippets = $snippet_repository->findByType('internal');

    $variables = array_unique(array_merge(
        ['settings'],
        $renderer->retrieveVariables($internal_template->getDefaultTemplate() . "\n" . $internal_template->getDefaultSubject())
    ));

    if ($post_data = fn_restore_post_data('internal_template')) {
        $internal_template->loadFromArray($post_data);
    }

    $view->assign('snippets', $snippets);
    $view->assign('internal_template', $internal_template);
    $view->assign('params_schema', $internal_template->getPreparedParamsSchema());
    $view->assign('default_subject', $default_subject);
    $view->assign('default_template', $default_template);
    $view->assign('variables', $variables);

    $tabs = Registry::ifGet('navigation.tabs', []);

    if (!empty($_REQUEST['event_id']) && !empty($_REQUEST['receiver'])) {
        $event_id = $_REQUEST['event_id'];
        $receiver = $_REQUEST['receiver'];
        $events = EventDispatcherProvider::getEventsSchema();
        if (isset($events[$event_id]['receivers'][$receiver][MailTransport::getId()])) {
            /** @var \Tygh\Notifications\Transports\Mail\MailMessageSchema $schema */
            $schema = $events[$event_id]['receivers'][$receiver][MailTransport::getId()];
            if ($schema->template_code) {
                /** @var \Tygh\Template\Mail\Repository $repository */
                $repository = Tygh::$app['template.mail.repository'];
                $area = $receiver === UserTypes::CUSTOMER
                    ? 'C'
                    : 'A';
                $template = $repository->findByCodeAndArea($schema->template_code, $area);
                if ($template) {
                    $tabs['email'] = [
                        'title' => __('notification_template.tab.email'),
                        'js'    => false,
                        'href'  => Url::buildUrn(
                            ['email_templates', 'update'],
                            ['template_id' => $template->getId(), 'event_id' => $event_id, 'receiver' => $receiver]
                        ),
                    ];
                }
            }
        }
    }

    $tabs['general'] = [
        'title' => __('notification_template.tab.internal'),
        'js'    => true,
    ];

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    $view->assign('active_tab', 'general');

    Registry::set('navigation.tabs', $tabs);

} elseif ($mode == 'snippets') {
    /** @var \Tygh\Template\Snippet\Repository $snippet_repository */
    $snippet_repository = Tygh::$app['template.snippet.repository'];
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $view->assign('snippets', $snippet_repository->findByType('internal'));
    $view->assign('active_section', 'code_snippets');
}
