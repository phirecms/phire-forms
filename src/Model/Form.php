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
     * Get all available fields
     *
     * @param  \Pop\Module\Manager $modules
     * @return array
     */
    public function getFields(\Pop\Module\Manager $modules = null)
    {
        $allFields = [];

        if ((null !== $modules) && ($modules->isRegistered('phire-fields'))) {
            $fields = \Phire\Fields\Table\Fields::findAll();
            foreach ($fields->rows() as $field) {
                $field->models = (!empty($field->models)) ? unserialize($field->models) : [];
                $allFields[$field->id] = $field;
            }
        }

        return $allFields;
    }

    /**
     * Get all available fields
     *
     * @param  array               $post
     * @param  \Pop\Module\Manager $modules
     * @return void
     */
    public function saveFields(array $post, \Pop\Module\Manager $modules = null)
    {
        if ((null !== $modules) && ($modules->isRegistered('phire-fields'))) {

            // Remove existing field-to-form relationships
            $fields = \Phire\Fields\Table\Fields::findAll();
            foreach ($fields->rows() as $field) {
                $models = (!empty($field->models)) ? unserialize($field->models) : [];
                if (count($models) > 0) {
                    foreach ($models as $key => $model) {
                        if (($model['model'] == 'Phire\Forms\Model\Form') && ($model['type_value'] == $post['form_id'])) {
                            unset($models[$key]);
                        }
                    }
                }
                $f = \Phire\Fields\Table\Fields::findById((int)$field->id);
                if (isset($f->id)) {
                    $f->models = serialize($models);
                    $f->save();
                }
            }

            // Save new field-to-form relationships
            if (isset($post['process_forms_manage']) && (count($post['process_forms_manage']) > 0)) {
                foreach ($post['process_forms_manage'] as $id) {
                    $field = \Phire\Fields\Table\Fields::findById((int)$id);
                    if (isset($field->id)) {
                        $models = (!empty($field->models)) ? unserialize($field->models) : [];
                        $models[] = [
                            'model'      => 'Phire\Forms\Model\Form',
                            'type_field' => 'id',
                            'type_value' => (int)$post['form_id']
                        ];
                        $field->models = serialize($models);
                        $field->save();
                    }
                }
            }
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
            'filter'            => (!empty($fields['filter']) ? (int)$fields['filter'] : 0),
            'use_captcha'       => (!empty($fields['use_captcha']) ? (int)$fields['use_captcha'] : 0),
            'use_csrf'          => (!empty($fields['use_csrf']) ? (int)$fields['use_csrf'] : 0),
            'force_ssl'         => (!empty($fields['force_ssl']) ? (int)$fields['force_ssl'] : 0)
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
            $form->filter            = (!empty($fields['filter']) ? (int)$fields['filter'] : 0);
            $form->use_captcha       = (!empty($fields['use_captcha']) ? (int)$fields['use_captcha'] : 0);
            $form->use_csrf          = (!empty($fields['use_csrf']) ? (int)$fields['use_csrf'] : 0);
            $form->force_ssl         = (!empty($fields['force_ssl']) ? (int)$fields['force_ssl'] : 0);
            $form->save();

            $this->data = array_merge($this->data, $form->getColumns());
        }
    }

    /**
     * Copy form
     *
     * @param  int $id
     * @param  \Pop\Module\Manager $modules
     * @return void
     */
    public function copy($id, \Pop\Module\Manager $modules = null)
    {
        $oldForm = Table\Forms::findById((int)$id);

        if (isset($oldForm->id)) {
            $i        = 1;
            $name     = $oldForm->name . ' (Copy ' . $i . ')';
            $dupeForm = Table\forms::findBy(['name' => $name]);

            while (isset($dupeForm->id)) {
                $i++;
                $name = $oldForm->name . ' (Copy ' . $i . ')';
                $dupeForm = Table\forms::findBy(['name' => $name]);
            }

            $form = new Table\Forms([
                'name'              => $name,
                'method'            => (!empty($oldForm->method) ? $oldForm->method : null),
                'to'                => (!empty($oldForm->to) ? $oldForm->to : null),
                'from'              => (!empty($oldForm->from) ? $oldForm->from : null),
                'reply_to'          => (!empty($oldForm->reply_to) ? $oldForm->reply_to : null),
                'action'            => (!empty($oldForm->action) ? $oldForm->action : null),
                'redirect'          => (!empty($oldForm->redirect) ? $oldForm->redirect : null),
                'attributes'        => (!empty($oldForm->attributes) ? $oldForm->attributes : null),
                'submit_value'      => (!empty($oldForm->submit_value) ? $oldForm->submit_value : null),
                'submit_attributes' => (!empty($oldForm->submit_attributes) ? $oldForm->submit_attributes : null),
                'use_captcha'       => (!empty($oldForm->use_captcha) ? (int)$oldForm->use_captcha : null),
                'use_csrf'          => (!empty($oldForm->use_csrf) ? (int)$oldForm->use_csrf : null),
                'force_ssl'         => (!empty($oldForm->force_ssl) ? (int)$oldForm->force_ssl : null)
            ]);

            $form->save();

            $flds = null;
            if ((null !== $modules) && ($modules->isRegistered('phire-fields'))) {
                $flds = \Phire\Fields\Table\Fields::findAll();
            }
            if (null !== $flds) {
                foreach ($flds->rows() as $f) {
                    if (!empty($f->models)) {
                        $models = (unserialize($f->models));
                        print_r($models);
                        foreach ($models as $model) {
                            if (($model['model'] == 'Phire\Forms\Model\Form') && ($oldForm->id == $model['type_value'])) {
                                $models[] = [
                                    'model'      => 'Phire\Forms\Model\Form',
                                    'type_field' => 'id',
                                    'type_value' => $form->id
                                ];
                                $newField = \Phire\Fields\Table\Fields::findById($f->id);
                                if (isset($newField->id)) {
                                    $newField->models = serialize($models);
                                    $newField->save();
                                }
                            }
                        }
                    }
                }
            }
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
