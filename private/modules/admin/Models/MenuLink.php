<?php
namespace Admin\Models;

use Phalcon\Mvc\Model;

class MenuLink extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     * @Primary
     * @Column(type="integer", length=11, nullable=false)
     */
    protected $menu_top_id;

    /**
     *
     * @var integer
     * @Primary
     * @Column(type="integer", length=11, nullable=false)
     */
    protected $menu_item_id;

    /**
     *
     * @var integer
     * @Column(type="integer", length=11, nullable=false)
     */
    protected $serial;

    /**
     * Method to set the value of field menu_top_id
     *
     * @param integer $menu_top_id
     * @return $this
     */
    public function setMenuTopId($menu_top_id)
    {
        $this->menu_top_id = $menu_top_id;

        return $this;
    }

    /**
     * Method to set the value of field menu_item_id
     *
     * @param integer $menu_item_id
     * @return $this
     */
    public function setMenuItemId($menu_item_id)
    {
        $this->menu_item_id = $menu_item_id;

        return $this;
    }

    /**
     * Method to set the value of field serial
     *
     * @param integer $serial
     * @return $this
     */
    public function setSerial($serial)
    {
        $this->serial = $serial;

        return $this;
    }

    /**
     * Returns the value of field menu_top_id
     *
     * @return integer
     */
    public function getMenuTopId()
    {
        return $this->menu_top_id;
    }

    /**
     * Returns the value of field menu_item_id
     *
     * @return integer
     */
    public function getMenuItemId()
    {
        return $this->menu_item_id;
    }

    /**
     * Returns the value of field serial
     *
     * @return integer
     */
    public function getSerial()
    {
        return $this->serial;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        //$this->setSchema("pgreen2_wp");
        $this->belongsTo('menu_top_id', '\MenuTop', 'id', ['alias' => 'MenuTop']);
        $this->belongsTo('menu_item_id', '\MenuItem', 'id', ['alias' => 'MenuItem']);
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'menu_link';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return MenuLink[]|MenuLink|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return MenuLink|\Phalcon\Mvc\Model\ResultInterface
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
            'menu_top_id' => 'menu_top_id',
            'menu_item_id' => 'menu_item_id',
            'serial' => 'serial'
        ];
    }

}
