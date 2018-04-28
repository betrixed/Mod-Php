<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Page\Models;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Validator\Uniqueness;


/**
 * Description of BlogComment
 *
 * @author http
 */
class BlogComment extends Model {
    //put your code here
    public $id;
    public $blog_id;
    public $head_id;
    public $reply_to_id;
    public $title;
    public $comment;
    public $user_id;
    public $mark_read;
    public $enabled;
    public $date_comment;
    
    public function initialize()
    {
        $this->belongsTo("user_id", 'Users', "id");
        $this->belongsTo("blog_id", "Blog", "id");
        
    }
    /** Reusable code for pages of comments.
     * @return PageInfo object
     * @param type $numberPage page of data to return
     * @param type $grabSize  size of each page
     */
    static public function getComments($numberPage, $grabsize, $blogId)
    {   
        $start = ($numberPage-1) * $grabsize;
        $sql = "select SQL_CALC_FOUND_ROWS c.id as ord1, c.*, u.name as author_name"
                . " from blog_comment c" 
                . " left join users u on u.id = c.user_id"
                . " where c.blog_id = " . $blogId . " and c.head_id is null"
                . " union select r.head_id as ord1, r.*, u1.name as author_name"
                . " from blog_comment r"
                . " left join users u1 on u1.id = r.user_id"
                . " where r.blog_id = " . $blogId . " and r.head_id is not null"
                . " order by ord1 desc, head_id, reply_to_id"
                . " limit " . $start . ", " . $grabsize;
        
        $di = \Phalcon\DI::getDefault();
        $mm = $di->get('db');
        $mm->connect();
        
        $stmt = $mm->query($sql);
        $stmt->setFetchMode(\Phalcon\Db::FETCH_OBJ);    
        $results = $stmt->fetchAll();
    
        $cquery = $mm->query("SELECT FOUND_ROWS()");
        $cquery->setFetchMode(\Phalcon\Db::FETCH_NUM);
        $maxrows = $cquery->fetch();
  
        $paginator = new PageInfo($numberPage, $grabsize, $results, $maxrows[0]);
        
        return $paginator;
    }
}
