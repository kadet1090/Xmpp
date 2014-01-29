<?php
/**
 * Created by PhpStorm.
 * User: Kacper
 * Date: 11.01.14
 * Time: 12:11
 */

namespace Kadet\Xmpp\Stanza\Iq;

use Kadet\Utils\Property;

class Query
{
    use \Kadet\Utils\Property;

    public $xml;

    public function __construct(\SimpleXMLElement $xml)
    {
        $this->xml = $xml;
    }

    /**
     * @internal
     */
    public function _get_namespace()
    {
        preg_match('/xmlns=(?:"|\')(.*?)(?:"|\')/si', $this->xml->asXML(), $match);
        return $match[1];
    }

    public function _get($name)
    {
        return $this->xml->$name;
    }
} 