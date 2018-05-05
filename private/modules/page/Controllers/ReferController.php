<?php
/**
 * @author Michael Rynn
 */
namespace Page\Controllers;

use Page\Models\Links;
use Mod\PageInfo;

use Phalcon\Db\Column;

class ReferController extends \Phalcon\Mvc\Controller
{
    /**
     * Return page of results
     * @param type $numberPage
     * @param type $pageRows
     */
    protected function linksPageNum($numberPage, $pageRows, $orderby) {
        $start = ($numberPage - 1) * $pageRows;
        //SQL_CALC_FOUND_ROWS
$sql= <<<EOD
select SQL_CALC_FOUND_ROWS b.*   
from links b
where b.enabled <> 0
order by $orderby limit :start, :pagerows
 
EOD;
        $db = $this->db;
        $stmt = $db->prepare($sql);
        $r1 = $db->executePrepared($stmt,
                [   
                    "start" => $start,
                    "pagerows" => $pageRows
                ],
                [
                    "start" => Column::BIND_PARAM_INT,
                    "pagerows" => Column::BIND_PARAM_INT,
                ]
                );
        
        $r1->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $results = $r1->fetchAll();

        $cquery = $db->query("SELECT FOUND_ROWS()");
        $cquery->setFetchMode(\Phalcon\Db::FETCH_NUM);
        $maxrows = $cquery->fetch();

        return new PageInfo($numberPage, $pageRows, $results, $maxrows[0]);
    }
    
    protected function buildAssets()
    {
        $elements = $this->elements;
        $elements->addAsset('bootstrap');
        
    }
    public function indexAction()
    {
        $this->buildAssets();
        $numberPage = $this->request->getQuery("page", "int");
        if (is_null($numberPage)) {
            $numberPage = 1;
        } else {
            $numberPage = intval($numberPage);
        }
        $orderby = $this->request->getQuery('orderby');
        $order_field = Links::indexOrderBy($this->view, $orderby);// this handles null case
        
        $view = $this->view;
        
        $view->page = $this->linksPageNum($numberPage, 12, $order_field);
        $this->ctx->pickView($view, 'refer/index');
        
        //$this->setEditor($this->view);       
    }
    
}
