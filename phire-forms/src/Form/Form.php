<?php

namespace Phire\Forms\Form;

use Pop\Form\Form as F;
use Pop\Validator;
use Phire\Forms\Table;

class Form extends F
{

    /**
     * Constructor
     *
     * Instantiate the form object
     *
     * @param  array  $fields
     * @param  string $action
     * @param  string $method
     * @return Form
     */
    public function __construct(array $fields, $action = null, $method = 'post')
    {
        parent::__construct($fields, $action, $method);
        $this->setAttribute('id', 'form-form');
        $this->setIndent('    ');
    }

    /**
     * Set the field values
     *
     * @param  array $values
     * @return Form
     */
    public function setFieldValues(array $values = null)
    {
        parent::setFieldValues($values);
        return $this;
    }

}