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
namespace Phire\Forms\Form;

use Pop\File\Upload;
use Pop\Mail\Mail;
use Phire\Forms\Table;

/**
 * Forms Form class
 *
 * @category   Phire\Forms
 * @package    Phire\Forms
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 * @version    1.0.0
 */
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
     * Filter flag
     */
    protected $filter = true;

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

        if (!class_exists('Phire\Fields\Model\Field')) {
            throw new \Pop\Form\Exception('The phire-fields module is not installed or active.');
        }

        $fieldGroups      = [];
        $submitAttributes = [];
        $formAttributes   = [];

        $this->filter = (bool)$form->filter;

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
        $sql->select()->orderBy('order', 'ASC');

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
                        $fieldConfig = \Phire\Fields\Event\Field::createFieldConfig($field);
                        if (strpos($fieldConfig['label'], '<span class="editor-link-span">') !== false) {
                            $fieldConfig['label'] = str_replace('<span class="editor-link-span">', '<span style="display: none;" class="editor-link-span">', $fieldConfig['label']);
                        }
                        $fieldGroups[$field->group_id]['field_' . $field->id] = $fieldConfig;
                        break;
                    }
                }
            } else if (null === $field->group_id) {
                foreach ($field->models as $model) {
                    if ((null === $model['type_value']) || ($form->id == $model['type_value'])) {
                        $fieldConfig = \Phire\Fields\Event\Field::createFieldConfig($field);
                        if (strpos($fieldConfig['label'], '<span class="editor-link-span">') !== false) {
                            $fieldConfig['label'] = str_replace('<span class="editor-link-span">', '<span style="display: none;" class="editor-link-span">', $fieldConfig['label']);
                        }
                        $fieldGroups[0]['field_' . $field->id] = $fieldConfig;
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

        parent::__construct($fieldGroups, null, $form->method);

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
                if (isset($value['tmp_name']) && !empty($value['tmp_name']) && class_exists('Phire\Fields\Model\Field')) {
                    $upload       = new Upload($_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/files');
                    $filename     = $upload->checkFilename($value['name'], $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/files');
                    $fields[$key] = $filename;
                    $files[]      = $filename;
                    $upload->upload($value);
                    unset($_FILES[$key]);
                }
            }
        }

        $fv     = new \Phire\Fields\Model\FieldValue();
        $values = $fv->save($fields, $submission->id, 'Phire\Forms\Model\FormSubmission');
        $form   = Table\Forms::findById($this->id);

        // If the form action is set
        if (!empty($form->action)) {
            $scheme = ($form->force_ssl) ? 'https://' : 'http://';
            $action = (substr($form->action, 0, 4) == 'http') ? $form->action : $scheme . $_SERVER['HTTP_HOST'] . BASE_PATH . $form->action;

            if ($form->method == 'post') {
                $options = [
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $values,
                    CURLOPT_HEADER         => false,
                    CURLOPT_RETURNTRANSFER => true
                ];
            } else {
                $action .= '?' . http_build_query($values);
                $options = [
                    CURLOPT_HEADER         => false,
                    CURLOPT_RETURNTRANSFER => true
                ];
            }

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
                    if (file_exists($_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/files/' . $file)) {
                        $mail->attachFile($_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/files/' . $file);
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
     * Method to get the whether to filter the form
     *
     * @return boolean
     */
    public function isFiltered()
    {
        return $this->filter;
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