<?php

namespace Page;

/**
 * Class to run metatags query associated with a blog Id
 */
class MetaTags
{

    /**
     * 
     * @param \Phalcon\Db\Adapter $db - 
     * @param Sites URL domain name, eg http://parracan.org
     * @param integer $id
     * @param type $meta reference to return Array of Metatags with replacement of template parameter
     * @return array of Metatag information
     */
    protected $db;
    protected $urlPrefix;
    public $meta; // Array of subsituted metatags
    public $results;

    public function __construct($db, $urlPrefix)
    {
        $this->db = $db;
        $this->urlPrefix = $urlPrefix;
    }

    static function hasPrefix($all, $prefix)
    {
        return (substr($all, 0, strlen($prefix)) === $prefix);
    }

    protected function tagsFromResults()
    {
        if ($this->results && count($this->results) > 0) {

            $meta_tags = [];
            foreach ($this->results as $row) {
                $content = $row->content;
                if ($row->prefixSite && !self::hasPrefix($content, "http")) {
                    if (!self::hasPrefix($content, '/')) {
                        $content = '/' . $content;
                    }
                    $content = $this->urlPrefix . $content;
                }
                $meta_tags[] = str_replace("{}", $content, $row->template);
            }
            $this->meta = $meta_tags;
        }
    }

    public function getMeta()
    {
        if (empty($meta)) {
            $this->tagsFromResults();
        }
        return $this->meta;
    }

    public function getTags($id)
    {
        // setup metatag info
        $sql = "select m.id, m.meta_name, m.template, m.data_limit, m.display, m.prefixSite, b.content"
                . " from meta m"
                . " join blog_meta b on b.meta_id = m.id"
                . " and b.blog_id = :blogId"
                . " order by meta_name";

        // form with m_attr_value as labels, content as edit text.

        $stmt = $this->db->query($sql, array('blogId' => $id));
        $stmt->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $this->results = $stmt->fetchAll();

        return $this->results;
    }

}
