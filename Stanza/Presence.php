<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kacper
 * Date: 08.08.13
 * Time: 21:31
 * To change this template use File | Settings | File Templates.
 */

namespace Kadet\Xmpp\Stanza;

use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Xml\XmlArray;
use Kadet\Xmpp\Xml\XmlBranch;
use Kadet\Xmpp\XmppClient;

/**
 * Class Presence
 *
 * @package Kadet\Xmpp\Stanza
 *
 * @property string $show
 * @property string $status
 * @property int    $priority
 * @property Jid    $jid
 * @property string $role
 * @property string affiliation
 */
class Presence extends Stanza
{
    /**
     * @var Jid
     */
    private $_jid;

    /**
     * @ignore
     */
    public function _get_show()
    {
        return isset($this->content['show'][0]) ? (string)$this->content['show'][0] : 'available';
    }

    /**
     * @ignore
     */
    public function _set_show($value)
    {
        if(!isset($this->content['show']))
            $this->content['show'] = new XmlArray('show');

        $this->content['show'][0] = (string)$value;
    }

    /**
     * @ignore
     */
    public function _get_status()
    {
        return isset($this->content['status'][0]) ? (string)$this->content['status'][0] : null;
    }

    /**
     * @ignore
     */
    public function _set_status($value)
    {
        if(!isset($this->content['status']))
            $this->content['status'] = new XmlArray('status');

        $this->content['status'][0] = (string)$value;
    }

    /**
     * @ignore
     */
    public function _get_priority()
    {
        return isset($this->content['priority'][0]) ? (int)$this->content['priority'][0] : null;
    }

    /**
     * @ignore
     */
    public function _set_priority($value)
    {
        if(!isset($this->content['priority']))
            $this->content['priority'] = new XmlArray('priority');

        $this->content['priority'][0] = (int)$value;
    }

    /**
     * @ignore
     */
    public function _get_role()
    {
        $item = $this->xpath('//user:item[@role]', ['user' => 'http://jabber.org/protocol/muc#user']);
        if(!empty($item))
            return $item[0]['role'];

        return 'participant';
    }

    /**
     * @ignore
     */
    public function _set_role($value)
    {
        $item = $this->xpath('//user:item', ['user' => 'http://jabber.org/protocol/muc#user']);
        if(!empty($item)) {
            $item = $item[0];
        } else {
            $item = $this->addChild(new XmlBranch('x'));
            $item['xmlns'] = 'http://jabber.org/protocol/muc#user';
            $item = $item->addChild(new XmlBranch('item'));
        }

        $item['role'] = (string)$value;
    }

    /**
     * @ignore
     */
    public function _get_affiliation()
    {
        $item = $this->xpath('//user:item[@affiliation]', ['user' => 'http://jabber.org/protocol/muc#user']);
        if(!empty($item))
            return $item[0]['affiliation'];

        return 'none';
    }

    public function _set_affiliation($value)
    {
        $item = $this->xpath('//user:item', ['user' => 'http://jabber.org/protocol/muc#user']);
        if(!empty($item)) {
            $item = $item[0];
        } else {
            $item = $this->addChild(new XmlBranch('x'));
            $item['xmlns'] = 'http://jabber.org/protocol/muc#user';
            $item = $item->addChild(new XmlBranch('item'));
        }

        $item['affiliation'] = (string)$value;
    }

    /**
     * Helper, gets jid from packet.
     *
     * @return Jid
     */
    public function _get_jid()
    {
        $jid = $this['from'];
        $item = $this->xpath('//user:item[@jid]', ['user' => 'http://jabber.org/protocol/muc#user']);

        if(!empty($item))
            $jid = $item[0]['jid'];

        if(!isset($this->_jid) || $this->_jid->__toString() != $jid)
            $this->_jid = new Jid($jid);

        return $this->_jid;
    }

    /*public static function fromXml($xml, XmppClient $client = null)
    {
        if (!($xml instanceof \SimpleXMLElement))
            $xml = @simplexml_load_string($xml);

        if($xml->getName() != 'presence')
            return XmlBranch::fromXml($xml);

        return parent::fromXml($xml, $client);
    }
*/
    public function __construct($show = null, $status = null, $priority = null) {
        $this->tag = 'presence';
        if(isset($show)) $this->show = $show;
        if(isset($status)) $this->status = $status;
        if(isset($priority)) $this->priority = $priority;
    }
}