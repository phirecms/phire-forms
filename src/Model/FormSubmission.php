<?php
/**
 * Phire Forms Module
 *
 * @link       https://github.com/phirecms/phire-forms
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Phire\Forms\Model;

use Phire\Forms\Table;
use Phire\Model\AbstractModel;

/**
 * Form Submission Model class
 *
 * @category   Phire\Forms
 * @package    Phire\Forms
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 * @version    1.0.0
 */
class FormSubmission extends AbstractModel
{

    /**
     * Get all form submissions
     *
     * @param  int                 $id
     * @param  int                 $limit
     * @param  int                 $page
     * @param  string              $sort
     * @param  \Pop\Module\Manager $modules
     * @return array
     */
    public function getAll($id, $limit = null, $page = null, $sort = null, \Pop\Module\Manager $modules = null)
    {
        $order = (null !== $sort) ? $this->getSortOrder($sort, $page) : 'timestamp ASC';

        if (null !== $limit) {
            $page = ((null !== $page) && ((int)$page > 1)) ?
                ($page * $limit) - $limit : null;

            $rows = Table\FormSubmissions::findBy(['form_id' => $id], [
                'offset' => $page,
                'limit'  => $limit,
                'order'  => $order
            ])->rows();
        } else {
            $rows = Table\FormSubmissions::findBy(['form_id' => $id], ['order' => $order])->rows();
        }

        $fieldNames = [];
        $arrayRows  = [];
        foreach ($rows as $i => $row) {
            $arrayRows[$i] = $row->getColumns();
            if ((null !== $modules) && ($modules->isRegistered('phire-fields'))) {
                $class = 'Phire\Forms\Model\Form';
                $sql   = \Phire\Fields\Table\Fields::sql();
                $sql->select()->where('models LIKE :models');
                $sql->select()->orderBy('order');

                $value  = ($sql->getDbType() == \Pop\Db\Sql::SQLITE) ? '%' . $class . '%' : '%' . addslashes($class) . '%';
                $fields = \Phire\Fields\Table\Fields::execute((string)$sql, ['models' => $value]);

                foreach ($fields->rows() as $field) {
                    if ($field->storage == 'eav') {
                        $fv = \Phire\Fields\Table\FieldValues::findBy([
                            'field_id' => $field->id,
                            'model_id' => $row->id,
                            'model'    => 'Phire\Forms\Model\FormSubmission'
                        ]);
                        foreach ($fv->rows() as $fv) {
                            if (!array_key_exists($field->name, $fieldNames)) {
                                $fieldNames[$field->name] = $field->type;
                            }
                            $arrayRows[$i][$field->name]   = json_decode($fv->value, true);
                        }
                    } else {
                        $fv = new \Pop\Db\Record();
                        $fv->setPrefix(DB_PREFIX)
                            ->setPrimaryKeys(['id'])
                            ->setTable('field_' . $field->name);

                        $fv->findRecordsBy([
                            'model_id' => $row->id,
                            'model'    => 'Phire\Forms\Model\FormSubmission',
                            'revision' => 0
                        ]);

                        if (!array_key_exists($field->name, $fieldNames)) {
                            $fieldNames[$field->name] = $field->type;
                        }

                        if ($fv->count() > 1) {
                            $arrayRows[$i][$field->name] = [];
                            foreach ($fv->rows() as $f) {
                                $arrayRows[$i][$field->name][] = $f->value;
                            }
                        } else {
                            $arrayRows[$i][$field->name] = $fv->value;
                        }
                    }
                }
            }
            $arrayRows[$i] = new \ArrayObject($arrayRows[$i], \ArrayObject::ARRAY_AS_PROPS);
        }

        return ['rows' => $arrayRows, 'fields' => $fieldNames];
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
     * @param  \Pop\Module\Manager $modules
     * @return array
     */
    public function getValues(\Pop\Module\Manager $modules = null)
    {
        $values     = [];
        $fieldNames = [];

        if ((null !== $modules) && ($modules->isRegistered('phire-fields'))) {
            $class = 'Phire\Forms\Model\Form';
            $sql   = \Phire\Fields\Table\Fields::sql();
            $sql->select()->where('models LIKE :models');
            $sql->select()->orderBy('order');

            $value  = ($sql->getDbType() == \Pop\Db\Sql::SQLITE) ? '%' . $class . '%' : '%' . addslashes($class) . '%';
            $fields = \Phire\Fields\Table\Fields::execute((string)$sql, ['models' => $value]);

            foreach ($fields->rows() as $field) {
                if ($field->storage == 'eav') {
                    $fv = \Phire\Fields\Table\FieldValues::findBy([
                        'field_id' => $field->id,
                        'model_id' => $this->id,
                        'model'    => 'Phire\Forms\Model\FormSubmission'
                    ]);
                    foreach ($fv->rows() as $fv) {
                        $fieldNames[$field->name] = $field->type;
                        $values[$field->name]   = json_decode($fv->value, true);
                    }
                } else {
                    $fv = new \Pop\Db\Record();
                    $fv->setPrefix(DB_PREFIX)
                        ->setPrimaryKeys(['id'])
                        ->setTable('field_' . $field->name);

                    $fv->findRecordsBy([
                        'model_id' => $this->id,
                        'model'    => 'Phire\Forms\Model\FormSubmission',
                        'revision' => 0
                    ]);

                    $fieldNames[$field->name] = $field->type;

                    if ($fv->count() > 1) {
                        $values[$field->name] = [];
                        foreach ($fv->rows() as $f) {
                            $fieldNames[$field->name] = $field->type;
                            $values[$field->name][]   = $f->value;
                        }
                    } else {
                        $values[$field->name] = $fv->value;
                    }
                }
            }
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
                        if (file_exists($_SERVER['DOCUMENT_ROOT'] . BASE_PATH. CONTENT_PATH . '/files/' . $file)) {
                            unlink($_SERVER['DOCUMENT_ROOT'] . BASE_PATH. CONTENT_PATH . '/files/' . $file);
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
