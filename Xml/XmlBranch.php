<?php
namespace Kadet\Xmpp\Xml;

use Kadet\Utils\Property;

class XmlBranch implements \ArrayAccess
{
    const XML = '<?xml version="1.0" encoding="utf-8"?>';

    public static $bind = [];

    use Property;

    /**
     * Tag of branch.
     * @var string
     */
    public $tag;

    /**
     * Branch attributes.
     * @var array
     */
    public $attributes = [];

    /**
     * Branch content or children.
     * @var XmlArray[]|string
     */
    public $content = [];

    public $_ns = [];

    /**
     * @param string $tag Tag of branch.
     */
    public function __construct($tag = '')
    {
        $this->tag = $tag;
    }

    public function getNamespace($ns = '') {
        return $this->_ns[$ns];
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
    public function addChild(XmlBranch $child)
    {
        if (!isset($this->content[$child->tag])) $this->content[$child->tag] = new XmlArray($child->tag);

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
        $this->content = trim($content);
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
                        $xml .= $branch instanceof XmlBranch ? $branch->asXml() : (string)$branch;
            else
                $xml .= htmlspecialchars($this->content);

            $xml .= '</' . $this->tag . '>';
        }

        return $xml;
    }

    /**
     * @param string $name
     * @return XmlBranch|null
     */
    public function _get($name)
    {
        if (is_array($this->content)) {
            if (!isset($this->content[$name])) $this->content[$name] = new XmlArray($name);
            return $this->content[$name];
        }
        else return null;
    }

    /**
     * @param string $name
     * @param        $value
     *
     * @return XmlBranch|null
     */
    public function _set($name, $value)
    {
        if (!isset($this->content[$name])) $this->content[$name] = new XmlArray($name);
        if($value instanceof XmlBranch) $value->tag = $name;
        $this->content[$name][0] = $value;
    }

    /**
     * @param string $name
     * @return XmlBranch|null
     */
    public function _isset($name)
    {
        if (is_array($this->content))
            return !empty($this->content[$name]);
        else return null;
    }

    public function _unset($name)
    {
        if (is_array($this->content) && isset($this->content[$name]))
            unset($this->content[$name]);
    }

    /**
     * @see asXml
     * @return string
     */
    public function __toString()
    {
        return strip_tags($this->asXml());
    }

    /**
     * @param $xml
     *
     * @return XmlBranch
     */
    public static function fromXml($xml) {
        if(!($xml instanceof \SimpleXMLElement))
            $xml = @simplexml_load_string(preg_replace('/(<\/?)([a-z]*?)\:/si', '$1', $xml));

        $name = $xml->getName();

        $name = $xml->getName();
        $name = strpos($name, ':') !== false ? substr(strstr($name, ':'), 1) : $name; // > SimpleXML
        $ns   = $xml->getNamespaces();
        $ns   = isset($ns['']) ? $ns[''] : null;

        if(isset(self::$bind["{$ns}/{$name}"]))
            $class = self::$bind["{$ns}/{$name}"];
        elseif(isset(self::$bind["{$name}"]))
            $class = self::$bind["{$name}"];
        elseif(isset(self::$bind["{$ns}"]))
            $class = self::$bind["{$ns}"];
        else
            $class = get_called_class();

        $branch = new $class;
        $branch->tag = strpos($name, ':') !== false ? substr(strstr($name, ':'), 1) : $name;

        foreach($xml->attributes() as $attribute)
            $branch->attributes[$attribute->getName()] = (string)$attribute;

        if(count($xml->xpath('parent::*')) != 0)
            $namespaces = array_diff(
                $xml->getNamespaces(),
                current($xml->xpath('parent::*'))->getNamespaces()
            );
        else
            $namespaces = $xml->getNamespaces();

        $branch->_ns = $xml->getNamespaces();
        foreach($namespaces as $key => $ns) {
            $branch->attributes['xmlns'.($key == '' ? '' : ':'.$key)] = $ns;
        }

        if(count($xml->children()) == 0) {
            $branch->content = (string)$xml;
        } else {
            foreach($xml->children() as $child) {
                $branch->addChild(self::fromXml($child));
            }
        }

        return $branch;
    }

    public function xpath($path, $namespaces = []) {
        $sxml = simplexml_load_string($this->asXml());

        foreach($namespaces as $prefix => $namespace)
            $sxml->registerXPathNamespace($prefix, $namespace);

        $results = $sxml->xpath($path);

        $return = [];
        foreach($results as $result) {
            $dom = dom_import_simplexml($result);
            $nodepath = explode('/', substr($dom->getNodePath(), 1));
            array_shift($nodepath);
            $node = $this;
            foreach($nodepath as $chunk) {
                preg_match('/([\w\*]+)(?:\[([0-9]+)\])?/', $chunk, $matches);
                $name = $matches[1];
                $no   = isset($matches[2]) ? $matches[2] - 1 : 0;

                if($name == '*') {
                    $arr = [];
                    foreach($node->content as $childs)
                        $arr = array_merge($arr, $childs->getArrayCopy());

                    $node = $arr[$no];
                } else
                    $node = $node->content[$name][$no];
            }

            $return[] = $node;
        }

        return $return;
    }

    /** {@inheritdoc} */
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    /** {@inheritdoc} */
    public function offsetGet($offset)
    {
        return $this->attributes[$offset];
    }

    /** {@inheritdoc} */
    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset] = $value;
    }

    /** {@inheritdoc} */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    public static function getCompleteXml($xml) {
        for($i = strpos($xml, '<', 0), $n = 0; $i !== false; $i = strpos($xml, '<', $i + 1)) {
            if((strpos($xml, '/>', $i + 1) > strpos($xml, '<', $i + 1) && strpos($xml, '<', $i + 1) !== false) || strpos($xml, '/>', $i + 1) === false) {
                if(!isset($xml[$i + 1])) return false;
                $xml[$i + 1] == '/' ? $n++ : $n--;
            }
            if($n == 0)
                return substr($xml, strpos($xml, '<', 0), strpos($xml, '<', $i + 1) !== false ? strpos($xml, '<', $i + 1) : strlen($xml));;
        }
        return false;
    }
}

?>
