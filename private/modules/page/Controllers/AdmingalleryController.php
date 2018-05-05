<?php
namespace Page\Controllers;
/**
 * @author Michael Rynn
*/

use Phalcon\Mvc\View;
use Page\Forms\GalleryForm;
use Mod\PageInfo;
use Page\Models\Gallery;
use Page\Models\Image;
use Page\Models\ImgGallery;

class ImageOp {

    protected $controller;
    
    protected $galleryid;
    public $imageid;
    public $rowid;
    
    public function init($cont, $galid) {
        $this->controller = $cont;
        $this->galleryid = $galid;
    }

    public function doThing() {
        
    }

}

class EditOp extends ImageOp {
    protected $request;
    
    public function __construct($req)
    {
        $this->request = $req;
    }
    public function doThing() {
        // description field changed?
        $new_description = $this->request->getPost('desc' . $this->rowid,'string');
        
        if ($this->controller->updateDescription($this->imageid,$new_description))
        {
            $this->controller->addEditList($this->imageid);
        }
    }

}

class VisibleOp extends ImageOp {

    public $value;

    public function doThing() {
        $db = $this->controller->db;
        return $db->execute("update img_gallery set visible = :val where imageid = :imgid and galleryid = :galid", array('val' => $this->value, 'imgid' => $this->imageid, 'galid' => $this->galleryid)
        );
    }

}

class DeleteOp extends ImageOp {

    public function doThing() {
        $controller = $this->controller;
        $img_id = $this->imageid;
        $gallery_id = $this->galleryid;
        $galcount = $controller->galRefCount($img_id);

        if ($galcount > 1) {
// remove reference
            $controller->removeRef($img_id, $gallery_id);
            $msg = "Removed reference to image: id = " . $img_id;
        } else {
// remove reference, image and file
            $controller->removeRef($img_id, $gallery_id);
            $myimg = $controller->getImage($img_id);
            $mygal = $controller->getGallery($myimg->galleryid);
            $dpath = $controller->getWebDir() . $mygal->path . "/";
            $path = $dpath . $myimg->name;
            $thumbpath = $dpath . "thumbs" . DIRECTORY_SEPARATOR . $myimg->name;
            $wasDeleted = false;
            if (is_file($path)) {
                $wasDeleted = unlink($path);
// also delete thumbs
                if ($wasDeleted)
                    unlink($thumbpath);
            } else if (!file_exists($path)) {
                $wasDeleted = true;
            }
            if ($wasDeleted) {
                $msg = "Deleted image " . $myimg->name;
                $myimg->delete();
            } else {
                $msg = "Problem with delete of " . $myimg->name;
            }
        }
    }

}

class AdmingalleryController extends \Phalcon\Mvc\Controller {
    /* navigate the images in a particular folder , or a folder indexed with a name */

    /* to start off with, just the images in /image/upload/ */
    const Prefix = '/page_admin/';
    const ViewDir = 'gallery_admin/';

    private $galleryPath = "image/gallery/";
    private $webdir;
    private $editList = [];

    public function initialize() {
        $this->myController = self::Prefix .  'gallery/';
        
    }
    public function setView($picked) {
        $view = $this->view;
        $this->ctx->pickView($view, self::ViewDir . $picked);
        
        $params = [
          'myModule' => self::Prefix, 
          'myController' => $this->myController
        ];
        
        $view->setVars($params);
    }
    public function addEditList($imgid) {
        $this->editList[] = $imgid;
    }

    public function getWebDir() {
        if (!isset($webdir)) {
            $di = \Phalcon\DI::getDefault();
            $config = $di->get('config');
            $webdir = $config->pcan->webDir;
        }
        return $webdir;
    }

    /**
     * Return internal dir path for gallery name
     * @param type $galName
     */
    private function getDirPath($gal) {
        return $this->getWebDir() . $gal->path;
    }

    /**
     * 
     * all images as a file list attached to view
     *  
     */
    private function scanImages($imageExt) {
        $di = \Phalcon\DI::getDefault();
        $config = $di->get('config');
        $imgdir = $this->getWebDir() . $imageExt;
        $dh = opendir($imgdir);
        $entry = readdir($dh);
        $imglist = array();
        while ($entry !== false) {
// create a list of entries if image type
            $entry = readdir($dh);
            $ext = pathinfo($entry, PATHINFO_EXTENSION);
            if ($ext == 'jpg' || $ext == 'png') {
                $imglist[] = $entry;
            }
        }
        closedir($dh);


        $thumbsdir = $imgdir . "/thumbs";
        if (file_exists($thumbsdir) === FALSE) {
            if (mkdir($thumbsdir) == FALSE) {
                return FALSE;
            }
        }
        foreach ($imglist as $path) {
            $this->set_thumb($path, $imgdir, $thumbsdir, 100, 100);
        }
        return $imglist;
    }

    private function scanUnregistered($gal)
    {
        $dpath = $this->getDirPath($gal);
        if (file_exists($dpath) && is_dir($dpath))
        {
            $fileset = $this->scanImages($gal->path);
            $imglist = $this->getImages($gal->id);//should be empty?

            $lookup = [];
            foreach($imglist as $r)
            {
                $lookup[$r->name] = $r;
            }
        // go through file list, and add any 'unregistered'
            foreach ( $fileset as $file) 
            {
                if(!array_key_exists($file, $lookup))
                {
                    $irec = $this->registerImage($gal,$file);
                    if ($irec) {
                        $lookup[$irec->name] = $irec;
                    }
                }
            }
        }         
    }
    private function createFromPost()
    {
        $name = $this->request->getPost('name', 'string');
       
        $oldgal = $this->getGalleryName($name);
        if ($oldgal)
        {
            $this->flash->error(" Gallery of that name already exists");
            return false;
        }
        else {
            $gal = new Gallery();
            $this->assignFromPost($gal,true);
             if (!$gal->save())
             {
                $this->flash->error("Error on save of " . $name);
                return false;    
             }
        }
        $this->scanUnregistered($gal);
        $this->response->redirect($this->myController . "edit/" . $gal->name);
        return true;
    }

    /*
     * Create Image Record for the image file name, found in gallery
     * $gal existing record. 
     * $imageFileName existing image.
     */

    private function registerImage($gal, $imageFileName) {

        $img = new Image();
        $img->galleryid = $gal->id;
        $img->name = $imageFileName;

// construct real path, get $file_size, $mimi_type, $date_upload, width , height

        $imgfile = $this->getWebDir() . $gal->path . DIRECTORY_SEPARATOR . $imageFileName;

        $sizeinfo = getimagesize($imgfile);

        $img->mime_type = $sizeinfo['mime'];
        $img->width = $sizeinfo[0];
        $img->height = $sizeinfo[1];
        $img->file_size = filesize($imgfile);
        $img->date_upload = date('Y-m-d H:i:s', filemtime($imgfile));

        if ($img->save()) {
//
            $link = new ImgGallery();
            $link->imageid = $img->id;
            $link->galleryid = $img->galleryid;
            $link->visible = 1;
            $link->save();
        }
        return $img;
    }

    private function assignFromPost($gal, $isnew) {
        $req = $this->request;
        $gal->name = $req->getPost('name', 'string');
        $gal->description = $req->getPost('description', 'string');
        $gal->path = $this->galleryPath . $gal->name;
    }

    public function newAction() {
        $this->buildAssets();
        $req = $this->request;
        if (!$req->isPost()) {
            /* make a new form */
            $myform = new GalleryForm();
            $this->view->myform = $myform;
        } else {
            if (!$this->createFromPost())
            {
                $myform = new GalleryForm();
                $myform->isValid($_POST);
                
                $this->view->myform = $myform;
            }
        }
    }

    private function getModel($id) {
        $gal = Gallery::findFirstByid($id);
        if (!$gal) {
            $this->flash->error("Gallery was not found");

            return $this->dispatcher->forward(array(
                        "controller" => "admingallery",
                        "action" => "index"
            ));
        }
        $this->setTagFromGallery($gal);
        $this->view->gal = $gal;
    }

    /**
     * Make an edit form for blog $id
     * @param type $id
     * @return type null
     */
    private function editForm($id) {

        $this->getView($id);
    }

    /**
     * 
     * @param type $id
     * id is a path, subdirectory of /image/gallery/
     */
    private function getGalleryFiles($name) {
// see if path exists, is registered, if not, make it
        $gal = Gallery::findFirst("name = '$name'");
        if ($gal === false) {
            $this->flash->error("gallery not registered : " . $name);
//$this->response->redirect("gallery/index");
        } else {
            $imageExt = $gal->path;
            $imgdir = $this->getWebDir() . $imageExt;
            if (!file_exists($imgdir)) {
                if (!mkdir($imgdir, 0775, true)) {
                    $this->flash->error("cannot make path : " . $imageExt);
//$this->response->redirect("gallery/index");
                    return;
                }
                $fileset = [];
            } else {
// scan files, setup thumbs
                $fileset = $this->scanImages($imageExt);
            }

            $this->view->fileset = $fileset;
            return $gal;
        }
    }

    /**
     *  return list of images and particular paths associated with a gallery id
     */
    private function getImages($id) {
        $mydb = $this->db;
// needs 2 queries 

        $qi = $mydb->query(
                "select i.*, g.path, a.visible from image i, gallery g, img_gallery a"
                . " where a.galleryid = :gid"
                . " and i.id = a.imageid"
                . " and g.id = i.galleryid", array('gid' => $id)
        );
        $qi->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $results = $qi->fetchAll();
        return $results;
    }

    private function registerMissing($file, $reg, $gal) {
        foreach ($reg as $r) {
            if ($r . name == $file)
                return;
        }
    }

    /**
     * register files that have been unregistered from gallery
     * @param type $name
     */
    public function registerAction($name) {
        
    }

    public function buildAssets()
    {
        $elements = $this->elements;
        $elements->addAsset('bootstrap');
        $elements->addAsset('jquery-form');
    }
    
    public function scanAction($name) {
       $this->buildAssets();
       $gal = $this->getGalleryFiles($name);
        if ($gal) {
            $this->setView('edit');
            $this->scanUnregistered($gal);
            $this->constructView($gal);
        }
    }
    public function editAction() {
        $this->buildAssets();
        
        $name = $this->dispatcher->getParam("name");
        
        if (!$this->request->isPost()) {
            
            $gal = $this->getGalleryFiles($name);
            if ($gal) {
                $this->constructView($gal);
            }
        } else {
//return $this->updatePost($id);
        }
    }

    private function constructEdit($galid)
    {
        $image_set = [];
    }
    private function constructView($galrec, $op = "edit", $isAjax = false) {

        $image_set = $this->getImages($galrec->id);
        $select = [];
        $select['edit'] = ['Edit', 0];
        $select['show'] = ['Show', 0];
        $select['hide'] = ['Hide', 0];
        $select['remove'] = ['Remove', 0];
        $select[$op][1] = 1;
        
        $view = $this->view;
        if ($isAjax) {
            $view->setRenderLevel(View::LEVEL_ACTION_VIEW);
            $this->setView("file");
        }
        else {
            $this->setView("edit");
        }
        $view->select = $select;
        $view->gallery = $galrec;
        $view->images = $image_set;
        if ($op=="edit" && count($this->editList)>0)
        {
            $tindex = [];
            $elist = [];
            foreach($image_set as $img)
            {
                $tindex[$img->id] = $img;
            }
            foreach($this->editList as $imgid)
            {
                $elist[] = $tindex[$imgid];
            }
            $view->elist = $elist;
        }
    }

    public function imageListAction() {
        $request = $this->request;
        if ($request->isPost() && $request->isAjax()) {
            $galleryid = $request->getPost('galleryid', 'int');
            $image_op = $request->getPost('image_op');
            $chkct = $request->getPost('chkct', 'int');

            if ($chkct > 0) {
                $sql = "";
                $id = 0;
                switch ($image_op) {
                    case "hide":
                        $myop = new VisibleOp();
                        $myop->value = 0;
                        break;
                    case "show":
                        $myop = new VisibleOp();
                        $myop->value = 1;
                        break;
                    case "remove":
                        $myop = new DeleteOp();
                        break;
                    case "edit":
                        $myop = new EditOp($request);
                        break;
                }
                if (isset($myop)) {
                    $myop->init($this, $galleryid);
                    for ($ix = 1; $ix <= $chkct; $ix++) {
                        $chkname = "chk" . $ix;
                        $chkvalue = $request->getPost($chkname, 'int');
                        if ($chkvalue > 0) {
                            $myop->imageid = $chkvalue;
                            $myop->rowid = $ix;
                            $myop->doThing();
                        }
                    }
                }
            }
            
            $this->constructView($this->getGallery($galleryid), $image_op, true);
           
        } else {
            $this->flash->error('No Ajax');
            $this->view->events = array();
        }
    }

    public function uploadAction() {
//$response->setHeader("Content-Type", "text/plain");
        if (!$this->request->isAjax()) {
            $this->flash->error("Not an AJAX request");
            return;
        }
        $this->setView("file");
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
        $reply = array();
// get the gallery to upload to
        $galleryid = $this->request->getPost('galleryid', 'int');
        $gal = Gallery::findFirstByid($galleryid);
        if (!isset($gal) || ($gal === false)) {
            $reply[] = '_failed_: ' . $galleryid;
            return;
        }


        $dest_dir = $this->getWebDir() . $gal->path . DIRECTORY_SEPARATOR;

        if ($this->request->hasFiles() == true) {
            $uploads = $this->request->getUploadedFiles();
            $isUploaded = false;
//do a loop to handle each file individually
// get indication of type
//$dest_dir = $this->request->getPost('up_dest');
//$dest_dir .= '/upload/';
// check if registered or exists
            foreach ($uploads as $upload) {
//define a “unique” name and a path to where our file must go
// $path = 'temp/' . md5(uniqid(rand(), true)) . '-' . strtolower($upload->getname());
                $fname = strtolower($upload->getname());
                $path = $dest_dir . $fname;
#move the file and simultaneously check if everything was ok
                $mimetype = $upload->getRealType();
                $filesize = $upload->getSize();
                $isUploaded = ($upload->moveTo($path)) ? true : false;

                if ($isUploaded) {
                    $reply[] = 'uploaded: ' . $fname;
                    $this->registerImage($gal, $fname);
                } else {
                    $reply[] = 'failed: ' . $fname;
                }
            }
        } else {
            $reply = ['No files were transferred'];
        }
        $this->view->replylist = $reply;
        $this->constructView($gal, "edit", true);
    }

    protected function listPageNum($numberPage, $pageRows, $orderby) {
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

    public function indexAction() {
        $this->buildAssets();
        $numberPage = $this->request->getQuery("page", "int");
        $orderby = 'name';
        $order_field = 'name';
        if (!isset($numberPage)) {
            $numberPage = 1;
        } else {
            $numberPage = intval($numberPage);
        }
        $view = $this->view;
        $view->orderby = $orderby;
        $view->page = $this->listPageNum($numberPage, 12, $order_field);
        $this->setView('index');
        
    }

/// photos_dir = ‘uploads/photos’
/// thumbs_dir = photos_dir . /thumbs
/// squire_size = 150
/// quality = 100 // percent
// from http://www.webxpert.ro/andrei/2009/01/08/thumbnail-generation-with-php-tutorial/
//
    static public function set_thumb($file, $photos_dir, $thumbs_dir, $square_size = 100, $quality = 100) {
        if (!file_exists($thumbs_dir . "/" . $file)) {
//get image info
            list($width, $height, $type, $attr) = getimagesize($photos_dir . "/" . $file);

//set dimensions
            if ($width > $height) {
                $width_t = $square_size;
//respect the ratio
                $height_t = round($height / $width * $square_size);
//set the offset
                $off_y = ceil(($width_t - $height_t) / 2);
                $off_x = 0;
            } elseif ($height > $width) {
                $height_t = $square_size;
                $width_t = round($width / $height * $square_size);
                $off_x = ceil(($height_t - $width_t) / 2);
                $off_y = 0;
            } else {
                $width_t = $height_t = $square_size;
                $off_x = $off_y = 0;
            }
            $srcfile = $photos_dir . "/" . $file;
            switch ($type) {
                case IMAGETYPE_GIF:   $thumb = imagecreatefromgif($srcfile);   break;
                case IMAGETYPE_JPEG:  $thumb = imagecreatefromjpeg($srcfile);  break;
                case IMAGETYPE_PNG:   $thumb = imagecreatefrompng($srcfile);   break;
                default: $thumb = null;         
            }
            if (!empty($thumb))
            {
                $thumb_p = imagecreatetruecolor($width_t, $height_t);

                imagecopyresampled($thumb_p, $thumb, 0, 0, 0, 0, $width_t, $height_t, $width, $height);
                imagejpeg($thumb_p, $thumbs_dir . "/" . $file, $quality);
            }
        }
    }

    /**
     * Return number of gallerys that reference the image
     * @param type $img_id
     */
    public function galRefCount($img_id) {
        $mydb = $this->db;
        $qct = $mydb->query("select count(*) from img_gallery g where g.imageid = :id"
                , array('id' => $img_id)
        );
        $qct->setFetchMode(\Phalcon\Db::FETCH_NUM);
        $galcount1 = $qct->fetch();
        return $galcount1[0];
    }

    public function getImage($imgid) {
        $image = Image::findFirst(array(
                    "conditions" => "id=?0",
                    "bind" => array(0 => $imgid)
        ));
        return $image;
    }

    private function getGalleryName($name) {
        return Gallery::findFirst(array(
                    "conditions" => "name=?0",
                    "bind" => array(0 => $name)
        ));
    }

    public function getGallery($galid) {
        return Gallery::findFirst(array(
                    "conditions" => "id=?0",
                    "bind" => array(0 => $galid)
        ));
    }

    private function removeImage($img_id) {
        $mydb = $this->db;
        $mydb->execute("delete from image"
                . " where id = :imgid"
                , array('imgid' => $img_id));
    }

    public function removeRef($img_id, $gallery_id) {
        $mydb = $this->db;
        $mydb->execute("delete from img_gallery "
                . "where imageid = :imgid and galleryid = :gid"
                , array('imgid' => $img_id, 'gid' => $gallery_id));
    }
    public function galleryEditAction()
    {
         $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
         if (!$this->request->isPost())
         {
            $idstr = $this->request->getQuery('id','string');
            $galid = intval(substr($idstr,3));
            $gal = $this->getGallery($galid);
            $this->view->gallery = $gal;   
            
         }
         else {
             $galid = $this->request->getPost('id','int');
             $name = $this->request->getPost('name','string');
             $description = $this->request->getPost('description','string');
             
             $gal = $this->getGallery($galid);
             if ($gal)
             {
                 $needSave = false;
                 if ($name != $gal->name)
                 {
                    $gal->$name = $name;
                    $needSave = true;
                     //? wanting a rename, do folder rename or error?
                 }
                 if ($gal->description != $description)
                 {
                     $needSave = true;
                     $gal->description = $description;
                 }
                 if ($needSave)
                 {
                    $db = $this->db;
                    $db->execute("update gallery set name=:dname, description = :ndesc where id = :id",
                            array('dname' => $name,'ndesc' => $description, 'id' => $galid)
                            );
                 }
                 $this->view->gallery = $gal;

             }
         }
         
         
    }

    public function updateDescription($imageId, $new_description)
    {
        $img = $this->getImage($imageId);
        if ($img)
        {
           $needSave = false;
           if ($img->description != $new_description)
           {
               $needSave = true;
               $img->description = $new_description;
           }
           if ($needSave)
           {
              $db = $this->db;
              $db->execute("update image set description = :ndesc where id = :id",
                      array('ndesc' => $new_description, 'id' => $imageId)
                      );
              return true;
           }           
        }
        return false;
    }        
}
