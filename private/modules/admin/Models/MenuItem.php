<?php
namespace Admin\Models;

use Phalcon\Mvc\Model;

class MenuItem extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     * @Primary
     * @Identity
     * @Column(type="integer", length=11, nullable=false)
     */
    protected $id;

    /**
     *
     * @var integer
     * @Column(type="integer", length=11, nullable=true)
     */
    protected $lang_id;

    /**
     *
     * @var string
     * @Column(type="string", length=30, nullable=true)
     */
    protected $controller;

    /**
     *
     * @var string
     * @Column(type="string", length=60, nullable=true)
     */
    protected $action;

    /**
     *
     * @var string
     * @Column(type="string", length=30, nullable=false)
     */
    protected $caption;

    /**
     *
     * @var string
     * @Column(type="string", length=15, nullable=true)
     */
    protected $user_role;

    /**
     *
     * @var string
     * @Column(type="string", length=30, nullable=true)
     */
    protected $class;

    /**
     * Method to set the value of field id
     *
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Method to set the value of field lang_id
     *
     * @param integer $lang_id
     * @return $this
     */
    public function setLangId($lang_id)
    {
        $this->lang_id = $lang_id;

        return $this;
    }

    /**
     * Method to set the value of field controller
     *
     * @param string $controller
     * @return $this
     */
    public function setController($controller)
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * Method to set the value of field action
     *
     * @param string $action
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Method to set the value of field caption
     *
     * @param string $caption
     * @return $this
     */
    public function setCaption($caption)
    {
        $this->caption = $caption;

        return $this;
    }

    /**
     * Method to set the value of field user_role
     *
     * @param string $user_role
     * @return $this
     */
    public function setUserRole($user_role)
    {
        $this->user_role = $user_role;

        return $this;
    }

    /**
     * Method to set the value of field class
     *
     * @param string $class
     * @return $this
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Returns the value of field id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the value of field lang_id
     *
     * @return integer
     */
    public function getLangId()
    {
        return $this->lang_id;
    }

    /**
     * Returns the value of field controller
     *
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Returns the value of field action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Returns the value of field caption
     *
     * @return string
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * Returns the value of field user_role
     *
     * @return string
     */
    public function getUserRole()
    {
        return $this->user_role;
    }

    /**
     * Returns the value of field class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        //$this->setSchema("pgreen2_wp");
        $this->hasMany('id', 'MenuLink', 'menu_item_id', ['alias' => 'MenuLink']);
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'menu_item';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return MenuItem[]|MenuItem|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return MenuItem|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

    /**
     * Independent Column Mapping.
     * Keys are the real names in the table and the values their names in the application
     *
     * @return array
     */
    public function columnMap()
    {
        return [
            'id' => 'id',
            'lang_id' => 'lang_id',
            'controller' => 'controller',
            'action' => 'action',
            'caption' => 'caption',
            'user_role' => 'user_role',
            'class' => 'class'
        ];
    }

}
