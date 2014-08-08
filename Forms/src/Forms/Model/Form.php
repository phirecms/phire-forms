<?php
/**
 * @namespace
 */
namespace Forms\Model;

use Pop\Data\Type\Html;
use Pop\File\Dir;
use Forms\Table;

class Form extends \Phire\Model\AbstractModel
{

    /**
     * Static method to parse placeholders within string content
     *
     * @param  string $body
     * @param  string $uri
     * @return string
     */
    public static function parse($body, $uri = null)
    {
        // Parse any form placeholders
        $formIds = array();
        $forms = array();
        preg_match_all('/\[\{form.*\}\]/', $body, $forms);
        if (isset($forms[0]) && isset($forms[0][0])) {
            foreach ($forms[0] as $form) {
                $id = substr($form, (strpos($form, 'form_') + 5));
                $formIds[] = str_replace('}]', '', $id);
            }
        }

        if (count($formIds) > 0) {
            foreach ($formIds as $id) {
                $form = new \Forms\Form\Form($id, $uri);
                if ($form->isSubmitted()) {
                    $values = ($form->getMethod() == 'post') ? $_POST : $_GET;
                    $form->setFieldValues($values, array('strip_tags' => null));
                    if ($form->isValid()) {
                        $form->process();
                        $body = str_replace('[{form_' . $id . '}]', $form->getMessage(), $body);
                    } else {
                        $body = str_replace('[{form_' . $id . '}]', (string)$form, $body);
                    }
                } else {
                    $body = str_replace('[{form_' . $id . '}]', (string)$form, $body);
                }
            }
        }

        return $body;
    }

    /**
     * Get all forms method
     *
     * @param  string $sort
     * @param  string $page
     * @return void
     */
    public function getAll($sort = null, $page = null)
    {
        $order = $this->getSortOrder($sort, $page);
        $forms = Table\FormTypes::findAll($order['field'] . ' ' . $order['order']);

        if ($this->data['acl']->isAuth('Forms\Controller\Forms\IndexController', 'remove')) {
            $removeCheckbox = '<input type="checkbox" name="remove_forms[]" id="remove_forms[{i}]" value="[{id}]" />';
            $removeCheckAll = '<input type="checkbox" id="checkall" name="checkall" value="remove_forms" />';
            $submit = array(
                'class' => 'remove-btn',
                'value' => $this->i18n->__('Remove')
            );
        } else {
            $removeCheckbox = '&nbsp;';
            $removeCheckAll = '&nbsp;';
            $submit = array(
                'class' => 'remove-btn',
                'value' => $this->i18n->__('Remove'),
                'style' => 'display: none;'
            );
        }

        $options = array(
            'form' => array(
                'id'      => 'forms-remove-form',
                'action'  => BASE_PATH . APP_URI . '/forms/remove',
                'method'  => 'post',
                'process' => $removeCheckbox,
                'submit'  => $submit
            ),
            'table' => array(
                'headers' => array(
                    'id'      => '<a href="' . BASE_PATH . APP_URI . '/forms?sort=id">#</a>',
                    'edit'    => '<span style="display: block; margin: 0 auto; width: 100%; text-align: center;">' . $this->i18n->__('Edit') . '</span>',
                    'name'    => '<a href="' . BASE_PATH . APP_URI . '/forms?sort=name">' . $this->i18n->__('Name') . '</a>',
                    'process' => $removeCheckAll
                ),
                'class'       => 'data-table',
                'cellpadding' => 0,
                'cellspacing' => 0,
                'border'      => 0
            ),
            'exclude' => array(
                'method', 'to', 'subject', 'action', 'redirect', 'captcha', 'csrf', 'attributes', 'submit_value', 'submit_attributes', 'force_ssl'
            ),
            'separator' => '',
            'indent'    => '        '
        );

        $formAry = array();
        foreach ($forms->rows as $form) {
            if ($this->data['acl']->isAuth('Forms\Controller\Forms\IndexController', 'edit')) {
                $edit = '<a class="edit-link" title="' . $this->i18n->__('Edit') . '" href="' . BASE_PATH . APP_URI . '/forms/edit/' . $form->id . '">Edit</a>';
            } else {
                $edit = null;
            }

            $submissions = Table\FormSubmissions::findAll(null, array('form_id' => $form->id));
            $subs = count($submissions->rows);
            $form->fields = 0;
            $fields = \Phire\Table\Fields::findAll(null);
            foreach ($fields->rows as $field) {
                $models = unserialize($field->models);
                foreach ($models as $model) {
                    if (($model['model'] == 'Forms\Model\Form') && (($model['type_id'] == 0) || ($model['type_id'] == $form->id))) {
                        $form->fields++;
                    }
                }
            }
            $form->submissions = $subs . ((($subs > 0) && ($this->data['acl']->isAuth('Forms\Controller\Forms\IndexController', 'view'))) ? ' [ <a href="' . BASE_PATH . APP_URI . '/forms/view/' . $form->id . '">' . $this->i18n->__('View') . '</a> ]' : null);
            if (null !== $edit) {
                $form->edit = $edit;
            }

            $formAry[] = $form;
        }

        if (isset($formAry[0])) {
            $this->data['table'] = Html::encode($formAry, $options, $this->config->pagination_limit, $this->config->pagination_range);
        }
    }

    /**
     * Get all form submissions method
     *
     * @param  string $sort
     * @param  string $page
     * @return void
     */
    public function getSubmissions($sort = null, $page = null)
    {
        $order = $this->getSortOrder($sort, $page);
        $submissions = Table\FormSubmissions::findAll($order['field'] . ' ' . $order['order'], array('form_id' => $this->id));

        if ($this->data['acl']->isAuth('Forms\Controller\Forms\IndexController', 'remove')) {
            $removeCheckbox = '<input type="checkbox" name="remove_submissions[]" id="remove_submissions[{i}]" value="[{id}]" />';
            $removeCheckAll = '<input type="checkbox" id="checkall" name="checkall" value="remove_submissions" />';
            $submit = array(
                'class' => 'remove-btn',
                'value' => $this->i18n->__('Remove')
            );
        } else {
            $removeCheckbox = '&nbsp;';
            $removeCheckAll = '&nbsp;';
            $submit = array(
                'class' => 'remove-btn',
                'value' => $this->i18n->__('Remove'),
                'style' => 'display: none;'
            );
        }


        $options = array(
            'form' => array(
                'id'      => 'forms-remove-form',
                'action'  => BASE_PATH . APP_URI . '/forms/remove/' . $this->id,
                'method'  => 'post',
                'process' => $removeCheckbox,
                'submit'  => $submit
            ),
            'table' => array(
                'headers' => array(
                    'id'         => '<a href="' . BASE_PATH . APP_URI . '/forms/view/' . $this->id . '?sort=id">#</a>',
                    'site_id'    => '<a href="' . BASE_PATH . APP_URI . '/forms/view/' . $this->id . '?sort=site_id">' . $this->i18n->__('Domain') . '</a>',
                    'timestamp'  => '<a href="' . BASE_PATH . APP_URI . '/forms/view/' . $this->id . '?sort=timestamp">' . $this->i18n->__('Date') . '</a>',
                    'ip_address' => $this->i18n->__('IP Address'),
                    'process'    => $removeCheckAll
                ),
                'class'       => 'data-table',
                'cellpadding' => 0,
                'cellspacing' => 0,
                'border'      => 0
            ),
            'exclude' => array(
                'form_id'
            ),
            'indent'    => '        ',
            'separator' => '',
            'date'      => $this->config->datetime_format
        );

        $submissionRows = array();

        foreach ($submissions->rows as $submission) {
            if ((int)$submission->site_id > 0) {
                $site = \Phire\Table\Sites::findById($submission->site_id);
                $submission->site_id = $site->domain;
            } else {
                $submission->site_id = $_SERVER['HTTP_HOST'];
            }

            if ($this->data['acl']->isAuth('Forms\Controller\Forms\IndexController', 'view')) {
                $submission->view = '[ <a href="' . BASE_PATH . APP_URI . '/forms/view/' . $this->id . '/' . $submission->id . '">' . $this->i18n->__('View') . '</a> ]';
            }

            $submissionRows[] = $submission;
        }

        if (isset($submissionRows[0])) {
            $this->data['table'] = Html::encode($submissionRows, $options, $this->config->pagination_limit, $this->config->pagination_range);
        }
    }

    /**
     * Get form submission method
     *
     * @param  int $id
     * @return void
     */
    public function getSubmission($id)
    {
        $submission = Table\FormSubmissions::findById($id);
        $this->data['formId']     = $submission->form_id;
        $this->data['ipAddress']  = $submission->ip_address;
        $this->data['submitDate'] = date($this->config->datetime_format, strtotime($submission->timestamp));
        $this->data['submission'] = \Phire\Model\FieldValue::getAll($id, true);
    }

    /**
     * Get form submissions fpr export method
     *
     * @param  int $id
     * @return void
     */
    public function getExport($id)
    {
        $rows = array();
        $form = Table\FormTypes::findById($id);
        $submissions = Table\FormSubmissions::findAll(null, array('form_id' => $id));

        foreach ($submissions->rows as $row) {
            $values = array_merge(array('id' => $row->id), \Phire\Model\FieldValue::getAll($row->id, true));
            $values['timestamp']  = $row->timestamp;
            $values['ip_address'] = $row->ip_address;
            $rows[] = $values;
        }

        $this->data['formName'] = $form->name;
        $this->data['rows'] = $rows;
    }

    /**
     * Get form by ID method
     *
     * @param  int $id
     * @return void
     */
    public function getById($id)
    {
        $form = Table\FormTypes::findById($id);
        if (isset($form->id)) {
            $this->data = array_merge($this->data, $form->getValues());
        }
    }

    /**
     * Save form
     *
     * @param \Pop\Form\Form $form
     * @return void
     */
    public function save(\Pop\Form\Form $form)
    {
        $fields = $form->getFields();

        $formType = new Table\FormTypes(array(
            'method'            => (int)$fields['method'],
            'name'              => $fields['name'],
            'subject'           => $fields['subject'],
            'to'                => $fields['to'],
            'from'              => $fields['from'],
            'reply_to'          => $fields['reply_to'],
            'action'            => $fields['action'],
            'redirect'          => $fields['redirect'],
            'attributes'        => $fields['attributes'],
            'submit_value'      => $fields['submit_value'],
            'submit_attributes' => $fields['submit_attributes'],
            'captcha'           => (int)$fields['captcha'],
            'csrf'              => (int)$fields['csrf'],
            'force_ssl'         => (int)$fields['force_ssl']
        ));

        $formType->save();
        $this->data['id'] = $formType->id;
    }

    /**
     * Update form
     *
     * @param \Pop\Form\Form $form
     * @return void
     */
    public function update(\Pop\Form\Form $form)
    {
        $fields = $form->getFields();

        $formType = Table\FormTypes::findById($fields['id']);
        $formType->method            = (int)$fields['method'];
        $formType->name              = $fields['name'];
        $formType->subject           = $fields['subject'];
        $formType->to                = $fields['to'];
        $formType->from              = $fields['from'];
        $formType->reply_to          = $fields['reply_to'];
        $formType->action            = $fields['action'];
        $formType->redirect          = $fields['redirect'];
        $formType->attributes        = $fields['attributes'];
        $formType->submit_value      = $fields['submit_value'];
        $formType->submit_attributes = $fields['submit_attributes'];
        $formType->captcha           = (int)$fields['captcha'];
        $formType->csrf              = (int)$fields['csrf'];
        $formType->force_ssl         = (int)$fields['force_ssl'];

        $formType->update();
        $this->data['id'] = $formType->id;
    }

    /**
     * Remove forms
     *
     * @param array $post
     * @return void
     */
    public function remove(array $post)
    {
        if (isset($post['remove_forms'])) {
            foreach ($post['remove_forms'] as $id) {
                $formSubmissions = Table\FormSubmissions::findAll(null, array('form_id' => $id));
                if (isset($formSubmissions->rows[0])) {
                    foreach ($formSubmissions->rows as $sub) {
                        // Delete files
                        \Phire\Model\FieldValue::remove($sub->id, realpath(__DIR__ . '/../../../data/files'));
                        //$fv = new \Phire\Table\FieldValues();
                        //$fv->delete(array('model_id' => $sub->id));
                    }
                }
                $form = Table\FormTypes::findById($id);
                if (isset($form->id)) {
                    $fields = \Phire\Table\Fields::findAll();
                    foreach ($fields->rows as $field) {
                        $models = unserialize($field->models);
                        foreach ($models as $key => $model) {
                            if (($model['model'] == 'Forms\Model\Form') && ($model['type_id'] == $id)) {
                                unset($models[$key]);
                            }
                        }
                        $fld = \Phire\Table\Fields::findById($field->id);
                        $fld->models = serialize($models);
                        $fld->save();
                    }
                    $form->delete();
                }
            }
        } else if (isset($post['remove_submissions'])) {
            foreach ($post['remove_submissions'] as $id) {
                \Phire\Model\FieldValue::remove($id, realpath(__DIR__ . '/../../../data/files'));

                $sub = Table\FormSubmissions::findById($id);
                $sub->delete();
            }
        }
    }

}

