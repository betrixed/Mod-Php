<?php
namespace Secure\Models;

use Phalcon\Mvc\Model;

class Resource extends Model
{
    public $id;
    
    public $name;
    
    public $action;
    
    
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
            $orderby = 'name';
        }
        $alt_list = array(
            'name' => 'name',
            'action' => 'action',
        );
        $col_arrow = array(
            'name' => '',
            'action' => '',
         );  
        switch($orderby)
        {
            case 'name':
                $alt_list['name'] = 'name-alt';
                $col_arrow['name'] = '&#8595;';
                $order_field = 'b.name asc';
                break;
            case 'action':
                $alt_list['action'] = 'action-alt';
                $col_arrow['action'] = '&#8595;';
                $order_field = 'b.action asc';
                break;  
            
             case 'name-alt':
                $col_arrow['name'] = '&#8593;';
                $order_field = 'b.name desc';
                break;   
            case 'action-alt':
                 $col_arrow['action'] = '&#8593;';
                 $order_field = 'b.action desc,  b.name desc';
                 break;   
           
            default:
                $col_arrow['name'] = '&#8595;';
                $order_field = 'b.name asc';
                break;             
                
        }
        $view->orderalt = $alt_list;
        $view->orderby = $orderby;
        $view->col_arrow = $col_arrow;
        return $order_field;
    }
}