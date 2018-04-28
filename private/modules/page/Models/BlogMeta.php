<?php
namespace Page\Models;

/**
 * Description of BlogMeta
 *
 * @author michael rynn
 */
class BlogMeta extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     * @Primary
     * @Column(type="integer", length=10, nullable=false)
     */
    protected $blog_id;

    /**
     *
     * @var integer
     * @Primary
     * @Column(type="integer", length=10, nullable=false)
     */
    protected $meta_id;

    /**
     *
     * @var string
     * @Column(type="string", length=200, nullable=true)
     */
    protected $content;

    /**
     * Method to set the value of field blog_id
     *
     * @param integer $blog_id
     * @return $this
     */
    public function setBlogId($blog_id)
    {
        $this->blog_id = $blog_id;

        return $this;
    }

    /**
     * Method to set the value of field meta_id
     *
     * @param integer $meta_id
     * @return $this
     */
    public function setMetaId($meta_id)
    {
        $this->meta_id = $meta_id;

        return $this;
    }

    /**
     * Method to set the value of field content
     *
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Returns the value of field blog_id
     *
     * @return integer
     */
    public function getBlogId()
    {
        return $this->blog_id;
    }

    /**
     * Returns the value of field meta_id
     *
     * @return integer
     */
    public function getMetaId()
    {
        return $this->meta_id;
    }

    /**
     * Returns the value of field content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        //$this->setSchema("sbodev1");
        $this->belongsTo('blog_id', '\Pcan\Models\Blog', 'id', ['alias' => 'blog']);
        $this->belongsTo('meta_id', '\Pcan\Models\Meta', 'id', ['alias' => 'meta']);
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'blog_meta';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return BlogMeta[]|BlogMeta|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return BlogMeta|\Phalcon\Mvc\Model\ResultInterface
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
            'blog_id' => 'blog_id',
            'meta_id' => 'meta_id',
            'content' => 'content'
        ];
    }

}

