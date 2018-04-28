<?php
/** 
 * @author Michael Rynn
 */
namespace Secure\Models;

use Phalcon\Mvc\Model;

/**
 * Store a password reset code, with the user_id and created_at values
 * of the UserEvent record that created it
 */
class ResetCode extends Model {
    
    public $code;
    
    public $user_id;
    
    public $created_at;
    
}

