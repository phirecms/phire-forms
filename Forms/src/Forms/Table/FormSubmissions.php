<?php
/**
 * @namespace
 */
namespace Forms\Table;

use Pop\Db\Record;

class FormSubmissions extends Record
{

    /**
     * @var   string
     */
    protected $tableName = 'form_submissions';

    /**
     * @var   string
     */
    protected $primaryId = 'id';

    /**
     * @var   boolean
     */
    protected $auto = true;

    /**
     * @var   string
     */
    protected $prefix = DB_PREFIX;

}

