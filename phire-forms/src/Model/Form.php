<?php

namespace Phire\Forms\Model;

use Phire\Forms\Table;
use Phire\Model\AbstractModel;

class Form extends AbstractModel
{

    /**
     * Get all forms
     *
     * @param  int                 $limit
     * @param  int                 $page
     * @param  string              $sort
     * @param  \Pop\Module\Manager $modules
     * @return array
     */
    public function getAll($limit = null, $page = null, $sort = null, \Pop\Module\Manager $modules = null)
    {
        $order = (null !== $sort) ? $this->getSortOrder($sort, $page) : 'id ASC';

        if (null !== $limit) {
            $page = ((null !== $page) && ((int)$page > 1)) ?
                ($page * $limit) - $limit : null;

            $rows = Table\Forms::findAll([
                'offset' => $page,
                'limit'  => $limit,
                'order'  => $order
            ])->rows();
        } else {
            $rows = Table\Forms::findAll(['order' => $order])->rows();
        }

        foreach ($rows as $i => $row) {
            $fieldCount = [];
            $flds       = null;
            if ((null !== $modules) && ($modules->isRegistered('phire-fields'))) {
                $flds = \Phire\Fields\Table\Fields::findAll();
            }
            if (null !== $flds) {
                foreach ($flds->rows() as $f) {
                    if (!empty($f->models)) {
                        $models = (unserialize($f->models));
                        foreach ($models as $model) {
                            if ($model['model'] == 'Phire\Forms\Model\Form') {
                                if (((null === $model['type_value']) || ($row->id == $model['type_value'])) &&
                                    !in_array($row->id, $fieldCount)) {
                                    $fieldCount[] = $f->id;
                                }
                            }
                        }
                    }
                }
            }
            $rows[$i]->num_of_fields      = count($fieldCount);
            $rows[$i]->num_of_submissions = Table\FormSubmissions::findBy(['form_id' => $row->id])->count();
        }
        return $rows;
    }

    /**
     * Get form by ID
     *
     * @param  int $id
     * @return void
     */
    public function getById($id)
    {
        $form = Table\Forms::findById($id);
        if (isset($form->id)) {
            $data = $form->getColumns();
            $this->data = array_merge($this->data, $data);
        }
    }

    /**
     * Save new form
     *
     * @param  array $fields
     * @return void
     */
    public function save(array $fields)
    {
        $form = new Table\Forms([
            'name'              => $fields['name'],
            'method'            => (!empty($fields['method']) ? $fields['method'] : null),
            'to'                => (!empty($fields['to']) ? $fields['to'] : null),
            'from'              => (!empty($fields['from']) ? $fields['from'] : null),
            'reply_to'          => (!empty($fields['reply_to']) ? $fields['reply_to'] : null),
            'action'            => (!empty($fields['action']) ? $fields['action'] : null),
            'redirect'          => (!empty($fields['redirect']) ? $fields['redirect'] : null),
            'attributes'        => (!empty($fields['attributes']) ? $fields['attributes'] : null),
            'submit_value'      => (!empty($fields['submit_value']) ? $fields['submit_value'] : null),
            'submit_attributes' => (!empty($fields['submit_attributes']) ? $fields['submit_attributes'] : null),
            'use_captcha'       => (!empty($fields['use_captcha']) ? (int)$fields['use_captcha'] : null),
            'use_csrf'          => (!empty($fields['use_csrf']) ? (int)$fields['use_csrf'] : null),
            'force_ssl'         => (!empty($fields['force_ssl']) ? (int)$fields['force_ssl'] : null)
        ]);
        $form->save();

        $this->data = array_merge($this->data, $form->getColumns());
    }

    /**
     * Update an existing form
     *
     * @param  array $fields
     * @return void
     */
    public function update(array $fields)
    {
        $form = Table\Forms::findById((int)$fields['id']);
        if (isset($form->id)) {
            $form->name              = $fields['name'];
            $form->method            = (!empty($fields['method']) ? $fields['method'] : null);
            $form->to                = (!empty($fields['to']) ? $fields['to'] : null);
            $form->from              = (!empty($fields['from']) ? $fields['from'] : null);
            $form->reply_to          = (!empty($fields['reply_to']) ? $fields['reply_to'] : null);
            $form->action            = (!empty($fields['action']) ? $fields['action'] : null);
            $form->redirect          = (!empty($fields['redirect']) ? $fields['redirect'] : null);
            $form->attributes        = (!empty($fields['attributes']) ? $fields['attributes'] : null);
            $form->submit_value      = (!empty($fields['submit_value']) ? $fields['submit_value'] : null);
            $form->submit_attributes = (!empty($fields['submit_attributes']) ? $fields['submit_attributes'] : null);
            $form->use_captcha       = (!empty($fields['use_captcha']) ? (int)$fields['use_captcha'] : null);
            $form->use_csrf          = (!empty($fields['use_csrf']) ? (int)$fields['use_csrf'] : null);
            $form->force_ssl         = (!empty($fields['force_ssl']) ? (int)$fields['force_ssl'] : null);
            $form->save();

            $this->data = array_merge($this->data, $form->getColumns());
        }
    }

    /**
     * Remove a form
     *
     * @param  array $fields
     * @return void
     */
    public function remove(array $fields)
    {
        if (isset($fields['rm_forms'])) {
            foreach ($fields['rm_forms'] as $id) {
                $form = Table\Forms::findById((int)$id);
                if (isset($form->id)) {
                    $form->delete();
                }
            }
        }
    }

    /**
     * Determine if list of forms has pages
     *
     * @param  int $limit
     * @return boolean
     */
    public function hasPages($limit)
    {
        return (Table\Forms::findAll()->count() > $limit);
    }

    /**
     * Get count of forms
     *
     * @return int
     */
    public function getCount()
    {
        return Table\Forms::findAll()->count();
    }

}
