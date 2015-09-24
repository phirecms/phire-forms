<?php

namespace Phire\Forms\Form;

use Pop\File\Upload;
use Pop\Mail\Mail;
use Phire\Forms\Table;

class Form extends \Pop\Form\Form
{

    /**
     * Redirect flag
     */
    protected $redirect = false;

    /**
     * Redirect URL
     */
    protected $redirectUrl = null;

    /**
     * Redirect message
     */
    protected $message = null;

    /**
     * Constructor method to instantiate the form object
     *
     * @param  mixed $id
     * @param  array $captchaConfig
     * @throws \Pop\Form\Exception
     * @return self
     */
    public function __construct($id, $captchaConfig = [])
    {
        $form = (is_numeric($id)) ? Table\Forms::findById($id) : Table\Forms::findBy(['name' => $id]);

        if (!isset($form->id)) {
            throw new \Pop\Form\Exception('That form does not exist.');
        }

        if (!class_exists('Phire\Fields\Model\Field') && !class_exists('Phire\FieldsPlus\Model\Field')) {
            throw new \Pop\Form\Exception('Neither the phire-fields or phire-fields-plus modules are installed or active.');
        }

        $action           = (!empty($form->action)) ? $form->action : null;
        $fieldGroups      = [];
        $submitAttributes = [];
        $formAttributes   = [];

        if (!empty($form->submit_attributes)) {
            $attribs = explode('" ', $form->submit_attributes);
            foreach ($attribs as $attrib) {
                $attAry = explode('=', $attrib);
                $att    = trim($attAry[0]);
                $val    = str_replace('"', '', trim($attAry[1]));
                $submitAttributes[$att] = $val;
            }
        }

        if (!empty($form->attributes)) {
            $attribs = explode('" ', $form->attributes);
            foreach ($attribs as $attrib) {
                $attAry = explode('=', $attrib);
                $att    = trim($attAry[0]);
                $val    = str_replace('"', '', trim($attAry[1]));
                $formAttributes[$att] = $val;
            }
        }

        $sql = \Phire\Fields\Table\Fields::sql();
        $sql->select()->where('models LIKE :models');

        $value  = ($sql->getDbType() == \Pop\Db\Sql::SQLITE) ? '%Phire\\Forms\\Model\\Form%' : '%Phire\\\\Forms\\\\Model\\\\Form%';
        $fields = \Phire\Fields\Table\Fields::execute((string)$sql, ['models' => $value]);

        foreach ($fields->rows() as $field) {
            if (null !== $field->group_id) {
                $fieldGroups[$field->group_id] = [];
            }
        }

        $fieldGroups[0]   = [];
        $fieldGroups[-1] = [];

        foreach ($fields->rows() as $field) {
            $field->validators = unserialize($field->validators);
            $field->models     = unserialize($field->models);
            if (null !== $field->group_id) {
                foreach ($field->models as $model) {
                    if ((null === $model['type_value']) || ($form->id == $model['type_value'])) {
                        $fieldGroups[$field->group_id]['field_' . $field->id] = \Phire\Fields\Event\Field::createFieldConfig($field);
                        break;
                    }
                }
            } else if (null === $field->group_id) {
                foreach ($field->models as $model) {
                    if ((null === $model['type_value']) || ($form->id == $model['type_value'])) {
                        $fieldGroups[0]['field_' . $field->id] = \Phire\Fields\Event\Field::createFieldConfig($field);
                        break;
                    }
                }
            }
        }

        if ($form->use_csrf) {
            $fieldGroups[-1]['csrf'] = [
                'type' => 'csrf'
            ];
        }

        if ($form->use_captcha) {
            if (class_exists('Phire\Captcha\Model\Captcha')) {
                $captcha = new \Phire\Captcha\Model\Captcha($captchaConfig);
                $captcha->createToken();

                $fieldGroups[-1]['captcha'] = [
                    'type' => 'captcha',
                    'label' => 'Enter Code',
                    'token' => $captcha->token
                ];
            } else {
                $fieldGroups[-1]['captcha'] = [
                    'type' => 'captcha',
                    'label' => 'Please Solve: ',
                ];
            }
        }

        $fieldGroups[-1]['id'] = [
            'type'  => 'hidden',
            'value' => $form->id
        ];

        $fieldGroups[-1]['submit'] = [
            'type'       => 'submit',
            'label'      => '&nbsp;',
            'value'      => (!empty($form->submit_value) ? $form->submit_value : 'SUBMIT'),
            'attributes' => $submitAttributes
        ];

        parent::__construct($fieldGroups, $action, $form->method);

        foreach ($formAttributes as $attrib => $value) {
            $this->setAttribute($attrib, $value);
        }
    }

    /**
     * Method to process the form
     *
     * @return self
     */
    public function process()
    {
        $fields = $this->getFields();

        $submission = new Table\FormSubmissions([
            'form_id'    => $this->id,
            'timestamp'  => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]);

        $submission->save();

        unset($fields['csrf']);
        unset($fields['captcha']);
        unset($fields['id']);
        unset($fields['submit']);

        $files = [];

        if ($_FILES) {
            foreach ($_FILES as $key => $value) {
                if (isset($value['tmp_name']) && !empty($value['tmp_name'])) {
                    if (class_exists('Phire\Fields\Model\Field')) {
                        $upload   = new Upload(__DIR__ . '/../../../../assets/phire-fields/files');
                        $filename = $upload->checkFilename($value['name'], __DIR__ . '/../../../../assets/phire-fields/files');
                    } else if (class_exists('Phire\FieldsPlus\Model\Field')) {
                        $upload   = new Upload(__DIR__ . '/../../../../assets/phire-fields-plus/files');
                        $filename = $upload->checkFilename($value['name'], __DIR__ . '/../../../../assets/phire-fields-plus/files');
                    }
                    $fields[$key] = $filename;
                    $files[]      = $filename;
                    $upload->upload($value);
                    unset($_FILES[$key]);
                }
            }
        }

        $fv     = new \Phire\Fields\Model\FieldValue();
        $values = $fv->save($fields, $submission->id);

        $form = Table\Forms::findById($this->id);

        // If the form action is set
        if (!empty($form->action)) {
            $scheme = ($form->force_ssl) ? 'https://' : 'http://';
            $action = (substr($form->action, 0, 4) == 'http') ? $form->action : $scheme . $_SERVER['HTTP_HOST'] . BASE_PATH . $form->action;
            $options = [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $values,
                CURLOPT_HEADER         => false,
                CURLOPT_RETURNTRANSFER => true
            ];

            $curl = new \Pop\Http\Client\Curl($action, $options);
            $curl->send();
            unset($curl);
        }

        // Send the submission if the form "to" field is set
        if (!empty($form->to)) {
            $domain  = str_replace('www.', '', $_SERVER['HTTP_HOST']);
            $subject = $form->name . ' : ' . $domain;

            // Set the recipient
            $rcpt    = ['email' => $form->to];
            $message = '';

            foreach ($values as $key => $value) {
                $message .= ucwords(str_replace('_', ' ', $key)) . ': ' . (is_array($value) ? implode(', ', $value) : $value) . PHP_EOL;
            }

            // Send form submission
            $mail = new Mail($subject, $rcpt);

            if (!empty($form->from)) {
                if (!empty($form->reply_to)) {
                    $mail->from($form->from, null, false)
                         ->replyTo($form->reply_to, null, false);
                } else {
                    $mail->from($form->from);
                }
            } else if (!empty($form->reply_to)) {
                $mail->replyTo($form->from);
            } else {
                $mail->from('noreply@' . $domain);
            }

            $mail->setText($message);

            if (count($files) > 0) {
                foreach ($files as $file) {
                    if (file_exists(__DIR__ . '/../../../../assets/phire-fields/files/' . $file)) {
                        $mail->attachFile(__DIR__ . '/../../../../assets/phire-fields/files/' . $file);
                    } else if (file_exists(__DIR__ . '/../../../../assets/phire-fields-plus/files/' . $file)) {
                        $mail->attachFile(__DIR__ . '/../../../../assets/phire-fields-plus/files/' . $file);
                    }
                }
            }

            $mail->send();
        }

        $this->clear();

        if (!empty($form->redirect)) {
            if ((substr($form->redirect, 0, 4) == 'http') || (substr($form->redirect, 0, 1) == '/')) {
                $this->redirect = true;
                $redirect       = (substr($form->redirect, 0, 4) == 'http') ? $form->redirect : BASE_PATH . $form->redirect;
                header('Location: ' . $redirect);
                exit();
            } else {
                $this->message = $form->redirect;
            }
        }

        return $this;
    }

    /**
     * Method to get the whether the form was submitted
     *
     * @return boolean
     */
    public function isSubmitted()
    {
        return (($_SERVER['REQUEST_URI'] == $this->attributes['action']) && ((($this->attributes['method'] == 'get') &&
            isset($_GET['submit'])) || (($this->attributes['method'] == 'post') && ($_POST))));
    }

    /**
     * Method to get the whether it's a redirect
     *
     * @return boolean
     */
    public function isRedirect()
    {
        return $this->redirect;
    }

    /**
     * Method to get the redirect URL
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * Method to get the redirect message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }


}