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

namespace Tygh\Models\Components;

use Tygh\Models\Components\IModel;

class Relation
{
    const HAS_ONE = 'one';
    const HAS_MANY = 'many';

    protected $owner;
    protected $type;
    protected $class_name;
    protected $field;
    protected $target_field;
    protected $extra_params;

    public function setRule($type, $class_name, $field, $target_field = null, $extra_params = array())
    {
        $this->type         = $type;
        $this->class_name   = $class_name;
        $this->field        = $field;
        $this->target_field = $target_field;
        $this->extra_params = $extra_params;
    }

    public function setOwner(IModel $owner)
    {
        $this->owner = $owner;
    }

    public function find()
    {
        $method = 'find' . ucfirst($this->type);
        if (method_exists($this, $method)) {
            return call_user_func(array($this, $method));
        }

        return null;
    }

    protected function findOne()
    {
        $target_field = $this->target_field ?: 'ids';
        if ($related_id = $this->owner->{$this->field}) {
            $model = $this->getModel($this->class_name);
            return $model->find(array_merge($this->extra_params, array(
                $target_field => $related_id
            )));
        }

        return null;
    }

    protected function findMany()
    {
        $target_field = $this->target_field ?: $this->owner->getPrimaryField();
        if ($related_id = $this->owner->{$target_field}) {
            $model = $this->getModel($this->class_name);
            return $model->findMany(array_merge($this->extra_params, array(
                $this->field => $related_id
            )));
        }

        return null;
    }

    public function link(IModel $related_model)
    {
        $method = 'link' . ucfirst($this->type);
        if (method_exists($this, $method)) {
            return call_user_func(array($this, $method), $related_model);
        }

        return null;
    }

    protected function linkOne(IModel $related_model)
    {
        $target_field = $this->target_field ?: $related_model->getPrimaryField();
        $this->owner->{$this->field} = $related_model->{$target_field};

        return $this->owner->save();
    }

    protected function linkMany(IModel $related_model)
    {
        $target_field = $this->target_field ?: $this->owner->getPrimaryField();
        $related_model->{$this->field} = $this->owner->{$target_field};

        return $related_model->save();
    }

    protected function getModel($class)
    {
        return $class::model($this->owner->getParams());
    }

}
