<?php

/**
 * @author Michael Rynn
 */

namespace Page\Controllers;

//use Mod\Text;
use Mod\Path;
use Mod\PageInfo;
use Page\Models\Gallery;

class GalleryController extends \Phalcon\Mvc\Controller
{
    /* navigate the images in a particular folder , or a folder indexed with a name */

    /* to start off with, just the images in /image/upload/ */

    /**
     *  url
     *  /gallery/index/
     *  /gallery/new/
     *  /gallery/edit/
     * 
     */

    const ViewDir = 'gallery/';
    
    private $galleryPath = "image/gallery/";
    private $webdir;
    private $prefix;
    
    public function initialize()
    {
        $this->prefix = '/' . $this->ctx->activeModule->name . '/';
        $this->myController = $this->prefix . self::ViewDir;
    }
    protected function setView($picked) {
        $view = $this->view;
        $this->ctx->pickView($view, self::ViewDir . $picked);
        $view->setVars( 
                [
                    'myController' => $this->myController,
                    'myModule' => $this->prefix
                ]
                );
    }
    protected function buildAssets()
    {
        $elements = $this->elements;
        $elements->addAsset("bootstrap");
    }

    private function getWebDir()
    {
        if (!isset($webdir)) {
            $config = $this->config;
            $webdir = Path::endSep($config->webDir);
        }
        return $webdir;
    }

    /**
     * 
     * @param type $id
     * id is a path, subdirectory of /image/gallery/
     */
    private function getGalleryName($name)
    {
        // see if path exists, is registered, if not, make it
        $gal = Gallery::findFirst("name = '$name'");
        if (!isset($gal)) {
            $this->flash->error("gallery not registered : " . $name);
            $this->response->redirect("gallery/index");
            return false;
        } else {
            $imageExt = $gal->path;
            $imgdir = $this->getWebDir() . $imageExt;
            if (!file_exists($imgdir)) {
                $this->flash->error("cannot find path : " . $imageExt);
                $this->response->redirect("gallery/index");
                return false;
            }
            return $gal;
        }
    }

    /**
     *  return list of images and particular paths associated with a gallery id
     */
    private function getImages($id)
    {
        $mydb = $this->db;
        $qi = $mydb->query(
                "select i.*, g.path from image i, gallery g, img_gallery a"
                . " where a.galleryid = :gid and a.visible <> 0"
                . " and i.id = a.imageid"
                . " and g.id = i.galleryid", array('gid' => $id)
        );
        $qi->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $results = $qi->fetchAll();
        return $results;
    }

    public function viewAction($path)
    {
        if (!$this->request->isPost()) {
            $gal = $this->getGalleryName($path);
            if ($gal) {
                $view = $this->view;
                $view->images = $this->getImages($gal->id);
                $view->gallery = $gal;
                $this->buildAssets();
                $this->setView('view');
            }
        } else {
            //return $this->updatePost($id);
        }
    }

    protected function listPageNum($numberPage, $pageRows, $orderby)
    {
        $start = ($numberPage - 1) * $pageRows;
        //SQL_CALC_FOUND_ROWS
        $sql = "select SQL_CALC_FOUND_ROWS b.* "
                . " from gallery b"
                . " order by " . $orderby
                . " limit " . $start . ", " . $pageRows;

        $db = $this->db;
        $stmt = $db->query($sql);

        $stmt->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $results = $stmt->fetchAll();

        $cquery = $db->query("SELECT FOUND_ROWS()");
        $cquery->setFetchMode(\Phalcon\Db::FETCH_NUM);
        $maxrows = $cquery->fetch();

        return new PageInfo($numberPage, $pageRows, $results, $maxrows[0]);
    }

    public function indexAction()
    {
        $numberPage = $this->request->getQuery("page", "int");
        $orderby = 'path';
        $order_field = 'path';
        if (!isset($numberPage)) {
            $numberPage = 1;
        } else {
            $numberPage = intval($numberPage);
        }
        $view = $this->view;

        $view->orderby = $orderby;
        $view->page = $this->listPageNum($numberPage, 12, $order_field);
        $this->setView('index');
        $this->buildAssets();
    }

}
