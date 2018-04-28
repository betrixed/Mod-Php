<?php
namespace Secure\Models;

use Phalcon\Mvc\Model;

/**
 * Permissions
 * Stores the permissions by profile
 */
class Permissions extends Model
{
    /**
     * @var integer
     */
    public $groupId;

    /**
     * @var string
     */
    public $resourceId;

    public function initialize()
    {
        $this->belongsTo('groupId', 'Pcan\Models\UserGroup', 'id', array(
            'alias' => 'group'
        ));
        
        $this->belongsTo('$resourceId', 'Pcan\Models\Resource', 'id', array(
            'alias' => 'resource'
        ));
    }
}


