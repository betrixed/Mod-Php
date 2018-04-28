<?php
namespace Page\Models;


use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Validator\Uniqueness;


/**
 * PCan\Models\Links 
 * Links to other sites and popularity
 * 
 */
class Links extends \Phalcon\Mvc\Model
{
    /**
     *
     * @var integer
     */
    public $id;

    public $url;
    
    public $summary;
    
    public $title;
    
    public $sitename;
    
    public $date_created;
    
    public $enabled;
    
    public $urltype;
    
    public $refid;
    
    /**
     * 
     * @param type $view    - View to set
     * @param string $orderby  - Handles null case for ordered column name
     * @return string    - table field to order by.
     */
    static public function indexOrderBy($view, $orderby)
    {
        if (is_null($orderby))
        {
            $orderby = 'date-alt';
        }
        $alt_list = array(
            'date' => 'date',
            'title' => 'title',
            'type' => 'type',
            'site' => 'site',
            'enabled' => 'enabled'
        );
        $col_arrow = array(
            'date' => '',
            'title' => '',
            'type' => '',
            'site' => '',
            'enabled' => ''
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
                $order_field = 'b.date_created asc';
                break;
            case 'type':
                $alt_list['type'] = 'type-alt';
                $col_arrow['type'] = '&#8595;';
                $order_field = 'b.urltype asc, b.date_created desc';
                break;
             case 'site':
                $alt_list['site'] = 'site-alt';
                $col_arrow['site'] = '&#8595;';
                $order_field = 'b.sitename asc, b.date_created desc';
                break;     
            case 'enabled':
                $alt_list['enabled'] = 'enabled-alt';
                $col_arrow['enabled'] = '&#8595;';
                $order_field = 'b.enabled desc, b.date_created desc';
                break;     
             case 'title-alt':
                $col_arrow['title'] = '&#8593;';
                $order_field = 'b.title desc';
                break;   
            case 'type-alt':
                 $col_arrow['type'] = '&#8593;';
                 $order_field = 'b.urltype desc,  b.date_created desc';
                 break;   
            case 'site-alt':
                $col_arrow['site'] = '&#8593;';
                $order_field = 'b.sitename desc, b.date_created desc';
                break; 
           case 'enabled-alt':
                $col_arrow['enabled'] = '&#8593;';
                $order_field = 'b.enabled asc, b.date_created asc';
                break;     
            case 'date-alt':
            default:
                $col_arrow['date'] = '&#8593;';
                $order_field = 'b.date_created desc';
                break;             
                
        }
        $view->orderalt = $alt_list;
        $view->orderby = $orderby;
        $view->col_arrow = $col_arrow;
        return $order_field;
    }

}
