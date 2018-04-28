<?php

namespace Page\Models;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class Blog extends Model
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
    public $title;
    
    /**
     *
     * @var string
     */
    public $article;
    /**
     *
     * @var string
     */
    public $title_clean;    
    
   /**
     *
     * @var integer
     */
    public $author_id;
    
    /**
     *
     * @var datetime
     */
    public $date_published;
   
    /**
     *
     * @var datetime
     */
    public $date_updated;
  
    /**
     *
     * @var integer
     */
    public $enabled;
     
    /**
     *
     * @var integer
     */
    public $comments;
     
    /**
     *
     * @var integer
     */
    public $featured;
    

    /**
     * @var string
     */
    
    public $style;
    
    /**
     *
     * @var integer
     */
    public $issue;
    /**
    
    public function initialize()
    {
        $this->belongsTo("author_id", 'Users', "id");
    }* 
     */
    /**
     * get orderby data for view and sql query
     */
    
    public function getCategories($arguments = null)
    {
        return $this->getRelated('blog_category', $arguments);
    }
    
    public function getMetaContent($arguments = null)
    {
        $db = $this->di->get('db');
$sql=<<<EOD
select m.meta_name, bm.content
from blog_meta bm join meta m on bm.meta_id = m.id
where bm.blog_id = $this->id
EOD;
        $qmeta = $db->query($sql);
        $qmeta->setFetchMode(\Phalcon\Db::FETCH_OBJ); 
        $results = $qmeta->fetchAll();
        return $results;
        
    }   
    public function initialize()
    {
       $this->hasMany('id', 
               '\Pcan\Models\BlogMeta',
                'blog_id',
                [
                    'alias' => 'blog_meta',
                    'action' => \Phalcon\Mvc\Model\Relation::NO_ACTION
                ]
                ); 
        $this->hasManyToMany('id', 
                'Pcan\Models\BlogMeta', 
                'blog_id', 
                'meta_id',
                'Pcan\Models\Meta',
                'id',
                [  // options
                    'alias' => 'meta',
                ]
        );        
        $this->hasManyToMany('id', 
                'Pcan\Models\BlogToCategory', 
                'blog_id', 
                'category_id',
                'Pcan\Models\BlogCategory',
                'id',
                [  // options
                    'alias' => 'blog_category',
                ]
        );

    }
    static public function viewOrderBy($view, $orderby)
    {
        if (is_null($orderby))
        {
            $orderby = 'date-alt';
        }
        $alt_list = array(
            'date' => 'date',
            'title' => 'title',
            'author' => 'author',
        );
        $col_arrow = array(
            'date' => '',
            'title' => '',
            'author' => '',
        );  
        switch($orderby)
        {
            case 'title':
                $alt_list['title'] = 'title-alt';
                $col_arrow['title'] = '&#8595;';
                $order_field = 'b.title asc';
                break;
            case 'date':
                $alt_list['date'] = 'date-alt';
                $col_arrow['date'] = '&#8595;';
                $order_field = 'b.date_published asc';
                break;
            case 'author':
                $alt_list['author'] = 'author-alt';
                $col_arrow['author'] = '&#8595;';
                $order_field = 'author_name asc, b.date_published desc';
                break;
             case 'title-alt':
                $col_arrow['title'] = '&#8593;';
                $order_field = 'b.title desc';
                break;   
            case 'author-alt':
                 $col_arrow['author'] = '&#8593;';
                 $order_field = 'author_name desc,  b.date_published desc';
                 break;       
            case 'date-alt':
            default:
                $col_arrow['date'] = '&#8593;';
                $order_field = 'b.date_published desc';
                break;             
                
        }
        $view->orderalt = $alt_list;
        $view->orderby = $orderby;
        $view->col_arrow = $col_arrow;
        return $order_field;
    }
/*
    public function save( $data = NULL,  $whiteList = NULL)
    {
        $sql = "update blog " 
                ."set article = :article,"
                . "title = :title,"
                . "title_clean = :title_clean,"
                . "date_updated = :date_updated,"
                . "featured = :featured,"
                . "comments  = :comments,"
                . "enabled = :enabled "
                . "where id = :id";
        $db = $this->getDI()->get('db');
        $stmt = $db->prepare($sql);
        
        $stmt->bindValue(':article', $this->article, \PDO::PARAM_STR);
        $stmt->bindValue(':title',  $this->title, \PDO::PARAM_STR);
        $stmt->bindValue(':title_clean', $this->title_clean, \PDO::PARAM_STR);
        $stmt->bindValue(':date_updated',  $this->date_updated, \PDO::PARAM_STR);
        $stmt->bindValue(':featured', (int) $this->featured, \PDO::PARAM_INT);
        $stmt->bindValue(':comments', (int) $this->comments, \PDO::PARAM_INT);
        $stmt->bindValue(':enabled', (int) $this->enabled, \PDO::PARAM_INT);
        $stmt->bindValue(':id', (int) $this->id, \PDO::PARAM_INT);
        return $stmt->execute();
        
    }
    public function getByID($bid)
    {
        $sql = "select * from blog where id = :id";
        $db = $this->getDI()->get('db');
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', (int) $bid, \PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_INTO, $this);
        if (!$stmt->fetch())
            return false;
        return $this;
        
    }
    static function FindFirstById($bid)
    {
        
        $blog = new Blog();
        return $blog->getById($bid);

    }
 */
}
    
     
     
