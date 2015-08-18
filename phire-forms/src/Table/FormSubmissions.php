<?php

namespace Phire\Forms\Table;

use Pop\Db\Record;

class FormSubmissions extends Record
{

    /**
     * Table prefix
     * @var string
     */
    protected static $prefix = DB_PREFIX;

    /**
     * Primary keys
     * @var array
     */
    protected $primaryKeys = ['id'];

}