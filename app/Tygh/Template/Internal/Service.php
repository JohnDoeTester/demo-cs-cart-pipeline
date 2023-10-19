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

namespace Tygh\Template\Internal;

use Tygh\Common\OperationResult;
use Tygh\Exceptions\DatabaseException;
use Tygh\Template\Renderer;

/**
 * The service class that implements the logic of on-site notification template management.
 *
 * @package Tygh\Template\Internal
 */
class Service
{
    /** @var Repository */
    protected $repository;

    /** @var Renderer */
    protected $renderer;

    /** @var array */
    protected $types = array();

    /**
     * On-site notification template service constructor.
     *
     * @param Repository    $repository Instance of on-site notification template repository.
     * @param Renderer      $renderer   Instance of template renderer.
     */
    public function __construct(Repository $repository, Renderer $renderer)
    {
        $this->repository = $repository;
        $this->renderer = $renderer;
    }

    /**
     * Create on-site notification template.
     *
     * @param array $data On-site notification template data.
     *
     * @return OperationResult
     */
    public function createTemplate(array $data)
    {
        $result = new OperationResult();

        $data = $this->filterData($data);
        $errors = $this->validateData($data);
        $result->setErrors($errors);

        if (empty($errors)) {
            $template = Template::fromArray($data);
            $template->setCreated(time());
            $template->setUpdated(time());

            try {
                $this->repository->save($template);
                $result->setSuccess(true);
                $result->setData($template);
            } catch (DatabaseException $e) {
                $result->addError($e->getCode(), $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Update on-site notification template.
     *
     * @param Template  $template   Instance of on-site notification template.
     * @param array     $data       On-site notification template data.
     *
     * @return OperationResult
     */
    public function updateTemplate(Template $template, array $data)
    {
        $result = new OperationResult();

        $data = $this->filterData($data);
        $errors = $this->validateData($data, $template);
        $result->setErrors($errors);

        if (empty($errors)) {
            $template->loadFromArray($data);
            $template->setUpdated(time());

            try {
                $this->repository->save($template);
                $result->setSuccess(true);
                $result->setData($template);
            } catch (DatabaseException $e) {
                $result->addError($e->getCode(), $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Clone on-site notification template.
     *
     * @param Template  $template   Instance of cloned on-site notification template.
     * @param array     $data       On-site notification template data.
     *
     * @return OperationResult
     */
    public function cloneTemplate(Template $template, array $data)
    {
        $result = new OperationResult();

        $template = clone $template;
        $template->setId(0);

        $data = $this->filterData($data);
        $errors = $this->validateData($data, $template);
        $result->setErrors($errors);

        if (empty($errors)) {
            $template->setSubject(null);
            $template->setTemplate(null);
            $template->loadFromArray($data);
            $template->setCreated(time());
            $template->setUpdated(time());

            try {
                $this->repository->save($template);
                $result->setSuccess(true);
                $result->setData($template);
            } catch (DatabaseException $e) {
                $result->addError($e->getCode(), $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Remove on-site notification template.
     *
     * @param Template $template Instance of on-site notification template.
     */
    public function removeTemplate(Template $template)
    {
        $this->repository->remove($template);
    }

    /**
     * Remove on-site notification template by area and code.
     *
     * @param string $code On-site notification code identifier.
     * @param string $area On-site notification template area.
     */
    public function removeTemplateByCodeAndArea($code, $area)
    {
        $template = $this->repository->findByCodeAndArea($code, $area);

        if ($template) {
            $this->removeTemplate($template);
        }
    }

    /**
     * Remove on-site notification template by add-on.
     *
     * @param string $addon Add-on code.
     */
    public function removeTemplateByAddon($addon)
    {
        $templates = $this->repository->findByAddon($addon);

        foreach ($templates as $template) {
            $this->removeTemplate($template);
        }
    }

    /**
     * Remove on-site notification template by code.
     *
     * @param string $code On-site notification code identifier.
     */
    public function removeTemplateByCode($code)
    {
        $templates = $this->repository->findByCode($code);

        foreach ($templates as $template) {
            $this->removeTemplate($template);
        }
    }

    /**
     * Filter data.
     *
     * @param array $data           Raw on-site notification template data.
     * @param array $safe_fields    Safe on-site notification template fields.
     * @param array $unsafe_fields  Unsafe on-site notification template fields.
     *
     * @return array
     */
    public function filterData(array $data, array $safe_fields = array(), array $unsafe_fields = array())
    {
        if (empty($safe_fields)) {
            $safe_fields = array(
                'code', 'subject', 'default_subject', 'template', 'addon',
                'default_template', 'area', 'status', 'params', 'params_schema'
            );
        }

        $data = array_intersect_key($data, array_flip($safe_fields));

        foreach ($unsafe_fields as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    /**
     * Validate on-site notification template data.
     *
     * @param array         $data     On-site notification template data.
     * @param Template|null $template Current instance of on-site notification template.
     *
     * @return array
     */
    public function validateData(array $data, Template $template = null)
    {
        $errors = array();

        if ($template == null) {
            $default_fields = array('code', 'area', 'template', 'status', 'subject');
            $data += array_fill_keys($default_fields, '');
        }

        if (array_key_exists('code', $data)) {
            $errors['code'] = $this->validateCode($data['code'], $data['area'], $template ? $template->getId() : null);
        }

        if (array_key_exists('template', $data)) {
            $errors['template'] = $this->validateTemplate($data['template']);
        }

        if (array_key_exists('default_template', $data)) {
            $errors['default_template'] = $this->validateTemplate($data['default_template']);
        }

        if (array_key_exists('subject', $data)) {
            $errors['subject'] = $this->validateTemplate($data['subject']);
        }

        if (array_key_exists('default_subject', $data)) {
            $errors['default_subject'] = $this->validateTemplate($data['default_subject']);
        }

        if (array_key_exists('status', $data)) {
            $errors['status'] = $this->validateStatus($data['status']);
        }

        return array_filter($errors, function ($val) {
            return $val !== true;
        });
    }

    /**
     * Validate on-site notification template status.
     *
     * @param string $status On-site notification template status.
     *
     * @return string|true
     */
    public function validateStatus($status)
    {
        $error = true;

        if (empty($status)) {
            $error = __('error_validator_required', array('[field]' => __('status')));
        } elseif (!in_array($status, array(Template::STATUS_ACTIVE, Template::STATUS_DISABLE), true)) {
            $error = __('error_validator_message', array('[field]' => __('status')));
        }

        return $error;
    }

    /**
     * Validate on-site notification template code.
     *
     * @param string    $code           On-site notification template code.
     * @param string    $area           On-site notification template area.
     * @param int|null  $template_id    Current on-site notification template identifier.
     *
     * @return string|true
     */
    public function validateCode($code, $area, $template_id = null)
    {
        $error = true;

        if (empty($code)) {
            $error = __('error_validator_required', array('[field]' => __('code')));
        } elseif (preg_match('/[^-_a-z0-9.]/', $code)) {
            $error = __('error_validator_message', array('[field]' => __('code')));
        } elseif ($this->repository->exists($area, $code, $template_id ? array($template_id) : array())) {
            $error = __('internal_template_exists');
        }

        return $error;
    }

    /**
     * Validate on-site notification template.
     *
     * @param string    $template   On-site notification template.
     *
     * @return string|true
     */
    public function validateTemplate($template)
    {
        $error = true;

        if (!empty($template)) {
            $result = $this->renderer->validate($template);

            if (!$result->isSuccess()) {
                $error = $result->getFirstError();
            }
        }

        return $error;
    }

    /**
     * Restore notification template to default.
     *
     * @param Template $template Instance of notification template.
     *
     * @return bool
     */
    public function restoreTemplate(Template $template)
    {
        $template->setTemplate($template->getDefaultTemplate());
        $template->setSubject($template->getDefaultSubject());

        return $this->repository->save($template);
    }

    /**
     * @return Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }
}
