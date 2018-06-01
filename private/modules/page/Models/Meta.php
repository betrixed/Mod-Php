<?php

namespace Page\Models;

class Meta extends \Phalcon\Mvc\Model
{

    
 
    /**
     *
     * @var integer
     * @Primary
     * @Identity
     * @Column(type="integer", length=10, nullable=false)
     */
    protected $id;

    /**
     *
     * @var string
     * @Column(type="string", length=20, nullable=false)
     */
    protected $meta_name;

    /**
     *
     * @var string
     * @Column(type="string", length=80, nullable=true)
     */
    protected $template;

    /**
     *
     * @var integer
     * @Column(type="integer", length=11, nullable=true)
     */
    protected $data_limit;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=true)
     */
    protected $display;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    protected $prefixSite;

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
     * Method to set the value of field meta_name
     *
     * @param string $meta_name
     * @return $this
     */
    public function setMetaName($meta_name)
    {
        $this->meta_name = $meta_name;

        return $this;
    }

    /**
     * Method to set the value of field template
     *
     * @param string $template
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Method to set the value of field data_limit
     *
     * @param integer $data_limit
     * @return $this
     */
    public function setDataLimit($data_limit)
    {
        $this->data_limit = $data_limit;

        return $this;
    }

    /**
     * Method to set the value of field display
     *
     * @param integer $display
     * @return $this
     */
    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Method to set the value of field prefixSite
     *
     * @param integer $prefixSite
     * @return $this
     */
    public function setPrefixSite($prefixSite)
    {
        $this->prefixSite = $prefixSite;

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
     * Returns the value of field meta_name
     *
     * @return string
     */
    public function getMetaName()
    {
        return $this->meta_name;
    }

    /**
     * Returns the value of field template
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Returns the value of field data_limit
     *
     * @return integer
     */
    public function getDataLimit()
    {
        return $this->data_limit;
    }

    /**
     * Returns the value of field display
     *
     * @return integer
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Returns the value of field prefixSite
     *
     * @return integer
     */
    public function getPrefixSite()
    {
        return $this->prefixSite;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        //$this->setSchema("sbodev1");
        $this->hasMany('id', '\Pcan\Models\BlogMeta', 'meta_id', ['alias' => 'BlogMeta']);
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'meta';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return Meta[]|Meta|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return Meta|\Phalcon\Mvc\Model\ResultInterface
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
            'meta_name' => 'meta_name',
            'template' => 'template',
            'data_limit' => 'data_limit',
            'display' => 'display',
            'prefixSite' => 'prefixSite'
        ];
    }

}