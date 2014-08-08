<?php
/**
 * @namespace
 */
namespace Forms\Form;

use Pop\Validator;
use Forms\Table;

class FormType extends \Phire\Form\AbstractForm
{

    /**
     * Constructor method to instantiate the form object
     *
     * @param  string $action
     * @param  string $method
     * @return self
     */
    public function __construct($action = null, $method = 'post')
    {
        parent::__construct($action, $method, null, '        ');

        $fields1 = array(
            'submit' => array(
                'type'  => 'submit',
                'value' => $this->i18n->__('SAVE'),
                'attributes' => array(
                    'class' => 'save-btn'
                )
            ),
            'update' => array(
                'type'       => 'button',
                'value'      => $this->i18n->__('UPDATE'),
                'attributes' => array(
                    'onclick' => "return phire.updateForm('#form-type-form', false);",
                    'class'   => 'update-btn'
                )
            ),
            'id' => array(
                'type'  => 'hidden',
                'value' => 0
            ),
            'update_value' => array(
                'type'  => 'hidden',
                'value' => 0
            ),
            'method' => array(
                'type'  => 'select',
                'label' => $this->i18n->__('Method'),
                'value' => array(
                    '1' => 'POST',
                    '0' => 'GET'
                ),
                'marked' => '1'
            ),
            'captcha' => array(
                'type'  => 'radio',
                'label' => $this->i18n->__('Use CAPTCHA'),
                'value' => array(
                    '0' => 'No',
                    '1' => 'Yes'
                ),
                'marked' => '0'
            ),
            'csrf' => array(
                'type'  => 'radio',
                'label' => $this->i18n->__('Use CSRF'),
                'value' => array(
                    '0' => 'No',
                    '1' => 'Yes'
                ),
                'marked' => '0'
            ),
            'force_ssl' => array(
                'type'  => 'radio',
                'label' => $this->i18n->__('Force SSL'),
                'value' => array(
                    '0' => 'No',
                    '1' => 'Yes'
                ),
                'marked' => '0'
            )
        );

        $fields2 = array(
            'name' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('Name'),
                'required'   => true,
                'attributes' => array('size' => 50)
            ),
            'subject' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('Subject'),
                'attributes' => array('size' => 50)
            ),
            'to' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('To'),
                'attributes' => array('size' => 50)
            ),
            'from' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('From'),
                'attributes' => array('size' => 50)
            ),
            'reply_to' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('Reply To'),
                'attributes' => array('size' => 50)
            )
        );
        $fields3 = array(
            'action' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('Action'),
                'attributes' => array('size' => 50)
            ),
            'redirect' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('Redirect'),
                'attributes' => array('size' => 50)
            ),
            'attributes' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('Form Attributes'),
                'attributes' => array('size' => 50)
            ),
            'submit_value' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('Submit Value'),
                'attributes' => array('size' => 50)
            ),
            'submit_attributes' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('Submit Attributes'),
                'attributes' => array('size' => 50)
            )
        );

        if (strpos($_SERVER['REQUEST_URI'], BASE_PATH . APP_URI . '/forms/edit/') !== false) {
            $fields2['name']['attributes']['onkeyup'] = "phire.updateTitle('#form-title', this);";
        }

        $this->initFieldsValues = array($fields1, $fields2, $fields3);
        $this->setAttributes('id', 'form-type-form');
    }

    /**
     * Set the field values
     *
     * @param  array $values
     * @param  array $filters
     * @return \Pop\Form\Form
     */
    public function setFieldValues(array $values = null, $filters = null)
    {
        parent::setFieldValues($values, $filters);

        // Add validators for checking dupe names and devices
        if (($_POST) && isset($_POST['id'])) {
            $formType = Table\FormTypes::findBy(array('name' => $this->name));
            if (isset($formType->id) && ($this->id != $formType->id)) {
                $this->getElement('name')
                     ->addValidator(new Validator\NotEqual($this->name, $this->i18n->__('That form name already exists. The name must be unique.')));
            }
        }

        return $this;
    }

}

