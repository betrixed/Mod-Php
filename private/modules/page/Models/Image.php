<?php
/**
 * @author: Michael Rynn
 */
namespace Page\Models;

use Phalcon\Mvc\Model;
use Mod\Text;

class Image extends \Phalcon\Mvc\Model
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
    public $name;
    
    /**
     * @var width
     */
    public $width;
    
    /**
     * @var height
     */
    public $height;
    
    /** 
     * @var integer
     */
    public $galleryid;
    

    public $date_upload;
    
    public $file_size;
    
    public $mime_type;
    
    public $description;
    
    public $tiedimage;
    
    public $size_str;
    
    public function beforeCreate()
    {
        $this->size_str = Text::formatBytes($this->file_size,1);
    }
    
    
}