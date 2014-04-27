<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kacper
 * Date: 08.08.13
 * Time: 21:31
 * To change this template use File | Settings | File Templates.
 */

namespace Kadet\Xmpp\Stanza;

use Kadet\Xmpp\Stanza\Iq\Query;
use Kadet\Xmpp\Utils\XmlArray;
use Kadet\Xmpp\Utils\XmlBranch;
use Kadet\Xmpp\XmppClient;

/**
 * Class Iq
 * @package Kadet\Xmpp\Stanza
 * @property Query $query
 */
class Iq extends Stanza {

    public function _get_query()
    {
        if (!isset($this->content['query'][0])) return null;
        return $this->content['query'][0];
    }

    public function _set_query($value)
    {
        if(!($value instanceof Query))
            throw new \InvalidArgumentException('Query must be of type Kadet\\Xmpp\\Stanza\\Iq\\Query');

        if(!isset($this->content['query']))
            $this->content['query'] = new XmlArray('query');
        $this->query[0] = $value;
    }

    public static function fromXml($xml, XmppClient $client = null)
    {
        if (!($xml instanceof \SimpleXMLElement))
            $xml = @simplexml_load_string($xml);

        if($xml->getName() == 'iq')
            return parent::fromXml($xml, $client);
        elseif($xml->getName() == 'query')
            return Query::fromXml($xml);

        return XmlBranch::fromXml($xml);
    }
}