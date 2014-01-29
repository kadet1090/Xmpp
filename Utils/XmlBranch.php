<?php
namespace Kadet\Xmpp\Utils;

class XmlBranch
{
    const XML = '<?xml version="1.0" encoding="utf-8"?>';

    /**
     * Tag of branch.
     * @var string
     */
    public $tag;

    /**
     * Branch attributes.
     * @var array
     */
    public $attributes = array();

    /**
     * Branch content or children.
     * @var array|string
     */
    public $content = array();

    /**
     * @param string $tag Tag of branch.
     */
    public function __construct($tag)
    {
        $this->tag = $tag;
    }

    /**
     * Add new attribute to branch.
     *
     * @param string $name  Attribute name.
     * @param string $value Attribute value.
     *
     * @return $this
     */
    public function addAttribute($name, $value)
    {
        $this->attributes[$name] = htmlspecialchars(trim($value));

        return $this;
    }

    /**
     * Adds new child to branch.
     *
     * @param XmlBranch $child Child.
     *
     * @return XmlBranch Added child.
     */
    public function addChild(xmlBranch $child)
    {
        if (!isset($this->content[$child->tag])) $this->content[$child->tag] = array();

        $this->content[$child->tag][] = $child;

        return $child;
    }

    /**
     * Sets content of branch.
     * Warning, deletes all children.
     *
     * @param string $content New branch content.
     */
    public function setContent($content)
    {
        $this->content = htmlspecialchars(trim($content));
    }

    /**
     * Gets tag as xml string.
     *
     * @return string Xml string.
     */
    public function asXml()
    {
        $xml = '<' . $this->tag . '';
        foreach ($this->attributes as $argument => $value)
            $xml .= ' ' . htmlspecialchars($argument) . '="' . htmlspecialchars($value) . '"';
        if (empty($this->content)) {
            $xml .= '/>';
        } else {
            $xml .= '>';
            if (is_array($this->content))
                foreach ($this->content as $branches)
                    foreach ($branches as $branch)
                        $xml .= $branch;
            else
                $xml .= $this->content;

            $xml .= '</' . $this->tag . '>';
        }

        return $xml;
    }

    /**
     * @param string $name
     * @return XmlBranch|null
     */
    public function __get($name)
    {
        if (is_array($this->content))
            return $this->content[$name];
        else return null;
    }

    /**
     * @see asXml
     * @return string
     */
    public function __toString()
    {
        return $this->asXml();
    }
}

?>
