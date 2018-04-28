<?php

namespace Secure\Models;

use Phalcon\Mvc\Model;

class UserAuth extends Model {
    
    public $id;
    
    public $userId;
    
    public $groupId;
    
    public $created_at;
    
    public $changed_at;
    
    public function initialize()
    {
        $this->belongsTo('userId', 'Pcan\Models\Users', 'id', array(
            'alias' => 'user'
        ));
    }
};