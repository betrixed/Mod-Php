<?php

/*
See the "licence.txt" file at the root "private" folder of this site
*/
namespace Page\Models;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\MetaData;
use Phalcon\Db\Column;



class BlogCategory extends Model {
    public $id;
    public $name;
    public $name_clean;
    public $enabled;
    public $date_created;
    
    public function metaData()
    {
         return array(
            // Every column in the mapped table
            MetaData::MODELS_ATTRIBUTES => [
                'id', 'name', 'name_clean', 'enabled','date_created'
            ],
            MetaData::MODELS_PRIMARY_KEY => [
                'id'
            ],
            MetaData::MODELS_NON_PRIMARY_KEY => [
                'name', 'name_clean', 'enabled','date_created'
            ],
            MetaData::MODELS_NOT_NULL => [
                'id', 'name', 'name_clean', 'enabled','date_created'
            ],
            MetaData::MODELS_DATA_TYPES => [
                'id'   => Column::TYPE_INTEGER,
                'name' => Column::TYPE_VARCHAR,
                'name_clean' => Column::TYPE_VARCHAR,
                'enabled' => Column::TYPE_INTEGER,
                'date_created' => Column::TYPE_DATETIME         
            ],
            MetaData::MODELS_DATA_TYPES_NUMERIC => array(
                'id'   => true,
                'enabled' => true
            ),
            MetaData::MODELS_IDENTITY_COLUMN => 'id',
            MetaData::MODELS_DATA_TYPES_BIND => [
                'id'   => Column::BIND_PARAM_INT,
                'name' => Column::BIND_PARAM_STR,
                'name_clean' => Column::BIND_PARAM_STR,
                'enabled' => Column::BIND_PARAM_INT,
                'date_created' => Column::BIND_PARAM_STR         
            ],
            MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT => [
                 'id' => true,
                 'date_created' => true
            ],
            // Fields that must be ignored from UPDATE SQL statements
            MetaData::MODELS_AUTOMATIC_DEFAULT_UPDATE => [
            ],

            // Default values for columns
            MetaData::MODELS_DEFAULT_VALUES => [
                'id' => true,
            ],

            // Fields that allow empty strings
            MetaData::MODELS_EMPTY_STRING_VALUES => [
            ]
        );
    }
};
    

