<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kacper
 * Date: 02.08.13
 * Time: 18:13
 * To change this template use File | Settings | File Templates.
 */
namespace Kadet\Xmpp\Stanza;

use Kadet\Utils\Property;
use Kadet\Xmpp\Jid;
use Kadet\Xmpp\User;
use Kadet\Xmpp\Utils\XmlBranch;
use Kadet\Xmpp\XmppClient;

/**
 * Class Stanza
 *
 * @package Kadet\Xmpp\Stanza
 *
 * @property string      $id       Unique stanza identificator.
 * @property Jid         $from     Stanza sender address.
 * @property Jid         $to       Stanza receiver address.
 * @property string      $type     Stanza type.
 *
 * @property-read User   $sender   Stanza sender user.
 * @property-read User   $receiver Stanza receiver user.
 */
class Stanza extends XmlBranch
{
    private $_from;
    private $_to;

    use Property;

    /**
     * Xmpp Client instance
     *
     * @var XmppClient
     */
    protected $_xmpp;

    /**
     * Access to low level xml.
     *
     * @var \SimpleXMLElement
     *
     * @deprecated
     */
    public $xml;

    /**
     * @ignore
     * @return \Kadet\Xmpp\Jid
     */
    public function _get_from()
    {
        if (!isset($this->_from) || $this->_from->__toString() != $this['from'])
            $this->_from = new Jid($this['from']);

        return $this->_from;
    }

    public function _set_from($value)
    {
        if (!($value instanceof Jid))
            $value = new Jid($value);
        $this->_from  = $value;
        $this['from'] = $value->__toString();
    }

    public function _set_to($value)
    {
        if (!($value instanceof Jid))
            $value = new Jid($value);
        $this->_to  = $value;
        $this['to'] = $value->__toString();
    }

    /**
     * @ignore
     */
    public function _get_to()
    {
        if (!isset($this->_to) || $this->_to->__toString() != $this['to'])
            $this->_to = new Jid($this['to']);

        return $this->_to;
    }

    /**
     * @ignore
     */
    public function _get_sender()
    {
        if (!isset($this->_xmpp)) return null;

        return $this->_xmpp->getUserByJid($this->from);
    }

    /**
     * @ignore
     */
    public function _get_receiver()
    {
        if (!isset($this->_xmpp)) return null;

        return $this->_xmpp->getUserByJid($this->to);
    }

    /**
     * @ignore
     */
    public function _get_id()
    {
        return isset($this['id']) ? $this['id'] : null;
    }

    public function _set_id($value)
    {
        $this['id'] = $value;
    }

    /**
     * @ignore
     */
    public function _get_type()
    {
        return isset($this['type']) ? $this['type'] : null;
    }

    public function _set_type($value)
    {
        $this['type'] = $value;
    }

    public static function fromXml($xml, XmppClient $client = null)
    {
        if (!($xml instanceof \SimpleXMLElement))
            $xml = @simplexml_load_string(preg_replace('/(<\/?)([a-z]*?)\:/si', '$1', $xml));

        if (get_called_class() != __CLASS__) {
            $stanza        = parent::fromXml($xml);
            $stanza->_xmpp = $client;
            $stanza->xml   = $xml;

            return $stanza;
        }

        $name = $xml->getName();
        $name = strpos($name, ':') !== false ? substr(strstr($name, ':'), 1) : $name; // > SimpleXML

        switch ($name) {
            case 'iq':
                return Iq::fromXml($xml, $client);
            case 'presence':
                return Presence::fromXml($xml, $client);
            case 'message':
                return Message::fromXml($xml, $client);
            default:
                $stanza        = parent::fromXml($xml);
                $stanza->_xmpp = $client;
                $stanza->xml   = $xml;

                return $stanza;
        }
    }
}