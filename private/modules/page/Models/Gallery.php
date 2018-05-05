<?php
/**
 * @author: Michael Rynn
 */
namespace Page\Models;

use Phalcon\Mvc\Model;

class Gallery extends \Phalcon\Mvc\Model
{
    /**
     *
     * @var integer
     */
    public $id;
     
    /**
     *
     * @var string
     */
    public $path;
     
    /**
     *
     * @var string
     */
    public $description;
    
    
    public $name;

}