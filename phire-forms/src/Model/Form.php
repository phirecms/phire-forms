<?php

namespace Phire\Forms\Model;

use Phire\Forms\Table;
use Phire\Model\AbstractModel;

class Form extends AbstractModel
{

    /**
     * Get all forms
     *
     * @param  string  $sort
     * @param  boolean $fields
     * @return array
     */
    public function getAll($sort = null, $fields = false)
    {
        $order = (null !== $sort) ? $this->getSortOrder($sort) : 'id ASC';
        $rows = Table\Forms::findAll(null, ['order' => $order])->rows();
        foreach ($rows as $i => $row) {
            $rows[$i]->num_of_fields = ($fields) ? 1 : 0;
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
            'captcha'           => (!empty($fields['captcha']) ? (int)$fields['captcha'] : null),
            'csrf'              => (!empty($fields['csrf']) ? (int)$fields['csrf'] : null),
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
            $form->captcha           = (!empty($fields['captcha']) ? (int)$fields['captcha'] : null);
            $form->csrf              = (!empty($fields['csrf']) ? (int)$fields['csrf'] : null);
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
