<?php

namespace Phire\Forms\Model;

use Phire\Forms\Table;
use Phire\Model\AbstractModel;

class FormSubmission extends AbstractModel
{

    /**
     * Get all form submissions
     *
     * @param  int     $id
     * @param  string  $sort
     * @param  boolean $fields
     * @return array
     */
    public function getAll($id, $sort = null, $fields = false)
    {
        $order      = (null !== $sort) ? $this->getSortOrder($sort) : 'timestamp ASC';
        $rows       = Table\FormSubmissions::findBy(['form_id' => $id], null, ['order' => $order])->rows();
        $fieldNames = [];
        foreach ($rows as $i => $row) {
            if ($fields) {
                $sql = \Phire\Fields\Table\FieldValues::sql();
                $sql->select([
                    'id'       => DB_PREFIX . 'fields.id',
                    'name'     => DB_PREFIX . 'fields.name',
                    'field_id' => DB_PREFIX . 'field_values.field_id',
                    'model_id' => DB_PREFIX . 'field_values.model_id',
                    'value'    => DB_PREFIX . 'field_values.value'
                ])->where('model_id = :model_id');
                $sql->select()->join(DB_PREFIX . 'fields', [DB_PREFIX . 'fields.id' => DB_PREFIX . 'field_values.field_id']);
                $fvRows = \Phire\Fields\Table\FieldValues::execute((string)$sql, ['model_id' => $row->id])->rows();
                foreach ($fvRows as $fv) {
                    $fieldNames[]        = $fv->name;
                    $rows[$i][$fv->name] = json_decode($fv->value, true);
                }
            }
        }

        return ['rows' => $rows, 'fields' => $fieldNames];
    }

    /**
     * Get form submission by ID
     *
     * @param  int $id
     * @return void
     */
    public function getById($id)
    {
        $submission = Table\FormSubmissions::findById($id);
        if (isset($submission->id)) {
            $data = $submission->getColumns();
            $this->data = array_merge($this->data, $data);
        }
    }

    /**
     * Process a form submission
     *
     * @param  array $fields
     * @return void
     */
    public function process(array $fields)
    {
        if (isset($fields['rm_submissions'])) {
            foreach ($fields['rm_submissions'] as $id) {
                $fv = new \Phire\Fields\Table\FieldValues();
                $fv->delete(['model_id' => $id]);
                $form = Table\FormSubmissions::findById((int)$id);
                if (isset($form->id)) {
                    $form->delete();
                }
            }
        }
    }

    /**
     * Determine if list of form submissions has pages
     *
     * @param  int $limit
     * @return boolean
     */
    public function hasPages($limit)
    {
        return (Table\FormSubmissions::findAll()->count() > $limit);
    }

    /**
     * Get count of form submissions
     *
     * @return int
     */
    public function getCount()
    {
        return Table\FormSubmissions::findAll()->count();
    }

}
