<?php

namespace Page\Models;

use Phalcon\Mvc\Model;

class Event extends \Phalcon\Mvc\Model
{
    /**
     *
     * @var integer
     */
    public $id;
     
    /**
     *
     * @var datetime
     */
    public $fromTime;
     
    /**
     *
     * @var datetime
     */
    public $toTime;
     
    /**
     *
     * @var integer
     */
    public $blogId;
     
    /**
     *
     * @var boolean
     */
    public $enabled;
     

}
     
