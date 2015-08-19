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
     * @param  int     $limit
     * @param  int     $page
     * @param  string  $sort
     * @param  boolean $fields
     * @return array
     */
    public function getAll($id, $limit = null, $page = null, $sort = null, $fields = false)
    {
        $order = (null !== $sort) ? $this->getSortOrder($sort, $page) : 'timestamp ASC';

        if (null !== $limit) {
            $page = ((null !== $page) && ((int)$page > 1)) ?
                ($page * $limit) - $limit : null;

            $rows = Table\FormSubmissions::findBy(['form_id' => $id], null, [
                'offset' => $page,
                'limit'  => $limit,
                'order'  => $order
            ])->rows();
        } else {
            $rows = Table\FormSubmissions::findBy(['form_id' => $id], null, ['order' => $order])->rows();
        }

        $fieldNames = [];
        foreach ($rows as $i => $row) {
            $fieldNames = [];
            if ($fields) {
                $sql = \Phire\Fields\Table\FieldValues::sql();
                $sql->select([
                    'id'       => DB_PREFIX . 'fields.id',
                    'name'     => DB_PREFIX . 'fields.name',
                    'type'     => DB_PREFIX . 'fields.type',
                    'field_id' => DB_PREFIX . 'field_values.field_id',
                    'model_id' => DB_PREFIX . 'field_values.model_id',
                    'value'    => DB_PREFIX . 'field_values.value'
                ])->where('model_id = :model_id');
                $sql->select()->join(DB_PREFIX . 'fields', [DB_PREFIX . 'fields.id' => DB_PREFIX . 'field_values.field_id']);
                $fvRows = \Phire\Fields\Table\FieldValues::execute((string)$sql, ['model_id' => $row->id])->rows();
                foreach ($fvRows as $fv) {
                    $fieldNames[$fv->name] = $fv->type;
                    $rows[$i][$fv->name]   = json_decode($fv->value, true);
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
     * Get form submission values
     *
     * @return array
     */
    public function getValues()
    {
        $values     = [];
        $fieldNames = [];

        $sql = \Phire\Fields\Table\FieldValues::sql();
        $sql->select([
            'id'       => DB_PREFIX . 'fields.id',
            'name'     => DB_PREFIX . 'fields.name',
            'type'     => DB_PREFIX . 'fields.type',
            'field_id' => DB_PREFIX . 'field_values.field_id',
            'model_id' => DB_PREFIX . 'field_values.model_id',
            'value'    => DB_PREFIX . 'field_values.value'
        ])->where('model_id = :model_id');
        $sql->select()->join(DB_PREFIX . 'fields', [DB_PREFIX . 'fields.id' => DB_PREFIX . 'field_values.field_id']);
        $fvRows = \Phire\Fields\Table\FieldValues::execute((string)$sql, ['model_id' => $this->id])->rows();
        foreach ($fvRows as $fv) {
            $fieldNames[$fv->name] = $fv->type;
            $values[$fv->name]     = json_decode($fv->value, true);
        }

        return ['values' => $values, 'fields' => $fieldNames];
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
                $fv = \Phire\Fields\Table\FieldValues::findBy(['model_id' => $id]);
                foreach ($fv->rows() as $value) {
                    $field = \Phire\Fields\Table\Fields::findById($value->field_id);
                    if (isset($field->id) && ($field->type == 'file')) {
                        $file = json_decode($value->value);
                        if (file_exists($_SERVER['DOCUMENT_ROOT'] . BASE_PATH. CONTENT_PATH . '/assets/phire-fields/files/' . $file)) {
                            unlink($_SERVER['DOCUMENT_ROOT'] . BASE_PATH. CONTENT_PATH . '/assets/phire-fields/files/' . $file);
                        }
                    }
                }

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
     * @param  int $id
     * @param  int $limit
     * @return boolean
     */
    public function hasPages($id, $limit)
    {
        return (Table\FormSubmissions::findBy(['form_id' => $id])->count() > $limit);
    }

    /**
     * Get count of form submissions
     *
     * @param  int $id
     * @return int
     */
    public function getCount($id)
    {
        return Table\FormSubmissions::findBy(['form_id' => $id])->count();
    }

}
