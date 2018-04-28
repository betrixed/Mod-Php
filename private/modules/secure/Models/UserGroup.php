<?php
namespace Secure\Models;

use Phalcon\Mvc\Model;


/**
 * Vokuro\Models\Profiles
 * All the profile levels in the application. Used in conjenction with ACL lists
 */
class UserGroup extends Model
{

    /**
     * @Primary
     * @Identity
     * @Column(type="integer",nullable=false)
     * 
     */
    public $id;

    /**
     * Name
     * @Column(type="string",length=64,nullable=false)
     */
    public $name;

    /**
     *
     * @Column(type="string",length=1,nullable=false)
     */
    public $active;
    
    /**
     * Define relationships to Users and Permissions
     */
    public function initialize()
    {
        $this->hasMany('id', 'Pcan\Models\UserAuth', 'groupId', array(
            'alias' => 'users',
            'foreignKey' => array(
                'message' => 'UserGroup cannot be deleted because it\'s used by UserAuth'
            )
        ));

        $this->hasMany('id', 'Pcan\Models\Permissions', 'groupId', array(
            'alias' => 'permissions',
            'foreignKey' => array(
                'message' => 'UserGroup cannot be deleted because it\'s used by Permissions'
            )
        ));
    }

    
}

