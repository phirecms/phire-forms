<?php
/**
 * @namespace
 */
namespace Forms\Form;

use Pop\File\File;
use Pop\Filter\String;
use Pop\Mail\Mail;
use Pop\Validator;
use Forms\Table;

class Form extends \Phire\Form\AbstractForm
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
     * @param  mixed  $fid
     * @param  string $uri
     * @throws \Pop\Form\Exception
     * @return self
     */
    public function __construct($fid = 0, $uri = null)
    {
        $site = \Phire\Table\Sites::getSite();
        $form = (is_numeric($fid)) ? Table\FormTypes::findById($fid) : Table\FormTypes::findBy(array('name' => $fid));

        if (!isset($form->id)) {
            throw new \Pop\Form\Exception($this->i18n->__('That form does not exist.'));
        }

        $form->name              = html_entity_decode($form->name, ENT_QUOTES, 'UTF-8');
        $form->subject           = html_entity_decode($form->subject, ENT_QUOTES, 'UTF-8');
        $form->action            = html_entity_decode($form->action, ENT_QUOTES, 'UTF-8');
        $form->redirect          = html_entity_decode($form->redirect, ENT_QUOTES, 'UTF-8');
        $form->attributes        = html_entity_decode($form->attributes, ENT_QUOTES, 'UTF-8');
        $form->submit_value      = html_entity_decode($form->submit_value , ENT_QUOTES, 'UTF-8');
        $form->submit_attributes = html_entity_decode($form->submit_attributes, ENT_QUOTES, 'UTF-8');

        $method = ($form->method == 0) ? 'get' : 'post';
        $action = $uri;

        parent::__construct($action, $method, null, '        ');

        $fieldGroups = array();

        $newFields = \Phire\Model\Field::getByModel('Forms\Model\Form', $form->id);
        if ($newFields['hasFile']) {
            $this->hasFile = true;
        }
        foreach ($newFields as $key => $value) {
            if (is_numeric($key)) {
                $fieldGroups[] = $value;
            }
        }

        $flds = array();

        if (count($fieldGroups) > 0) {
            foreach ($fieldGroups as $fg) {
                $flds[] = $fg;
            }
        }

        $submitAttributes = array();
        $formAttributes   = array();

        if (!empty($form->submit_attributes)) {
            $attribs = explode('" ', $form->submit_attributes);
            foreach ($attribs as $attrib) {
                $attAry = explode('=', $attrib);
                $att = trim($attAry[0]);
                $val = str_replace('"', '', trim($attAry[1]));
                $submitAttributes[$att] = $val;
            }
        }

        if (!empty($form->attributes)) {
            $attribs = explode('" ', $form->attributes);
            foreach ($attribs as $attrib) {
                $attAry = explode('=', $attrib);
                $att = trim($attAry[0]);
                $val = str_replace('"', '', trim($attAry[1]));
                $formAttributes[$att] = $val;
            }
        }

        $fields = array();
        if ($form->csrf) {
            $fields['csrf'] = array(
                 'type'  => 'csrf',
                 'value' => String::random(8)
            );
        }

        if ($form->captcha) {
            $fields['captcha'] = array(
                'type'       => 'captcha',
                'label'      => $this->i18n->__('Enter Code'),
                'captcha'    => '<br /><img id="captcha-image" src="' . $site->base_path . '/captcha" /><br /><a class="reload-link" href="#" onclick="document.getElementById(\'captcha-image\').src = \'' . $site->base_path . '/captcha?reload=1\';return false;">' . $this->i18n->__('Reload') . '</a>',
                'attributes' => array('size' => 5)
            );
        }

        $fields['form_id'] = array(
            'type'  => 'hidden',
            'value' => $form->id
        );

        $fields['submit'] = array(
            'type'  => 'submit',
            'label' => '&nbsp;',
            'value' => (!empty($form->submit_value) ? $form->submit_value : $this->i18n->__('SUBMIT')),
            'attributes' => $submitAttributes
        );

        $flds[] = $fields;

        $this->initFieldsValues = $flds;

        foreach ($formAttributes as $attrib => $value) {
            $this->setAttributes($attrib, $value);
        }
    }

    /**
     * Method to process the form
     *
     * @return self
     */
    public function process()
    {
        $site   = \Phire\Table\Sites::getSite();
        $form   = Table\FormTypes::findById($this->form_id);
        $fields = $this->getFields();

        $fieldObjects = \Phire\Model\Field::getByModel('Forms\Model\Form', $this->form_id);

        $submission = new Table\FormSubmissions(array(
            'form_id'    => $this->form_id,
            'site_id'    => (isset($site->id)) ? $site->id : 0,
            'timestamp'  => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ));

        $submission->save();

        unset($fields['csrf']);
        unset($fields['captcha']);
        unset($fields['submit']);

        \Phire\Model\FieldValue::save($fields, $submission->id, (($form->method) ? 'POST' : 'GET'), realpath(__DIR__ . '/../../../data/files'));
        $values = \Phire\Model\FieldValue::getAll($submission->id, true);

        $form->name              = html_entity_decode($form->name, ENT_QUOTES, 'UTF-8');
        $form->subject           = html_entity_decode($form->subject, ENT_QUOTES, 'UTF-8');
        $form->action            = html_entity_decode($form->action, ENT_QUOTES, 'UTF-8');
        $form->redirect          = html_entity_decode($form->redirect, ENT_QUOTES, 'UTF-8');
        $form->attributes        = html_entity_decode($form->attributes, ENT_QUOTES, 'UTF-8');
        $form->submit_value      = html_entity_decode($form->submit_value , ENT_QUOTES, 'UTF-8');
        $form->submit_attributes = html_entity_decode($form->submit_attributes, ENT_QUOTES, 'UTF-8');

        $files = array();
        foreach ($fieldObjects as $k => $fo) {
            if (is_numeric($k) && is_array($fo)) {
                foreach ($fo as $f) {
                    if (($f['type'] == 'file') && isset($values[$f['name']]) && file_exists(__DIR__ . '/../../../data/files/' . $values[$f['name']])) {
                        $files[] = __DIR__ . '/../../../data/files/' . $values[$f['name']];
                    }
                }
            }
        }

        // If the form action is set
        if (!empty($form->action)) {
            $scheme = ($form->force_ssl) ? 'https://' : 'http://';
            $action = (substr($form->action, 0, 4) == 'http') ? $form->action : $scheme . $site->domain . $site->base_path . $form->action;
            $options = array(
                CURLOPT_URL            => $action,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $values,
                CURLOPT_HEADER         => false,
                CURLOPT_RETURNTRANSFER => true
            );

            $curl = new \Pop\Curl\Curl($options);
            $curl->execute();
            unset($curl);
        }

        // Send the submission if the form "to" field is set
        if (!empty($form->to)) {
            $domain = str_replace('www.', '', $site->domain);
            $subject = (!empty($form->subject)) ? $form->subject : $form->name . ' : ' . $domain;

            // Set the recipient
            $rcpt = array('email' => $form->to);

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
                    $mail->attachFile($file);
                }
            }

            $mail->send();
        }

        $this->clear();

        if (!empty($form->redirect)) {
            if ((substr($form->redirect, 0, 4) == 'http') || (substr($form->redirect, 0, 1) == '/')) {
                $this->redirect = true;
                $redirect = (substr($form->redirect, 0, 4) == 'http') ? $form->redirect : $site->base_path . $form->redirect;
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
        return (($_SERVER['REQUEST_URI'] == $this->action) && ((($this->method == 'get') && isset($_GET['submit'])) || (($this->method == 'post') && ($_POST))));
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

