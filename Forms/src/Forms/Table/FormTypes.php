<?php
/**
 * @namespace
 */
namespace Forms\Table;

use Pop\Db\Record;

class FormTypes extends Record
{

    /**
     * @var   string
     */
    protected $tableName = 'forms';

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

