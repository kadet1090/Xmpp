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
use Kadet\Xmpp\XmppClient;

/**
 * Class Stanza
 * @package Kadet\Xmpp\Stanza
 *
 * @property string $id       Unique stanza identificator.
 * @property Jid    $from     Stanza sender address.
 * @property Jid    $to       Stanza receiver address.
 * @property User   $sender   Stanza sender user.
 * @property User   $receiver Stanza receiver user.
 * @property string $type     Stanza type.
 */
class Stanza {
    private $_id;
    private $_from;
    private $_to;
    private $_sender;
    private $_receiver;
    private $_type;

    use \Kadet\Utils\Property;

    /**
     * Xmpp Client instance
     * @var XmppClient
     */
    protected $_xmpp;

    /**
     * Access to low level xml.
     * @var \SimpleXMLElement
     */
    public $xml;

    public function __construct(XmppClient $client, \SimpleXMLElement $xml) {
        $this->_xmpp    = $client;
        $this->xml      = $xml;
    }

    /**
     * @ignore
     * @return \Kadet\Xmpp\Jid
     */
    public function _get_from() {
        if(!isset($this->_from)) $this->_from = new Jid($this->xml['from']);
        return $this->_from;
    }

    /**
     * @ignore
     */
    public function _get_to() {
        if(!isset($this->_to)) $this->_to = new Jid($this->xml['to']);
        return $this->_to;
    }

    /**
     * @ignore
     */
    public function _get_sender() {
        if(!isset($this->_sender)) $this->_sender = $this->_xmpp->getUserByJid($this->from);
        return $this->_sender;
    }

    /**
     * @ignore
     */
    public function _get_receiver() {
        if(!isset($this->_receiver)) $this->_receiver = $this->_xmpp->getUserByJid($this->_receiver);
        return $this->_receiver;
    }

    /**
     * @ignore
     */
    public function _get_id() {
        if(!isset($this->_id)) $this->_id = $this->xml["id"];
        return $this->_id;
    }

    /**
     * @ignore
     */
    public function _get_type() {
        if(!isset($this->_type)) $this->_type = $this->xml["type"];
        return $this->_type;
    }

    public static function factory(XmppClient $client, \SimpleXMLElement $xml) {
        $name = $xml->getName();
        $name = strpos($name, ':') !== false ? substr(strstr($name, ':'), 1) : $name; // > SimpleXML

        switch($name) {
            case 'iq':       return new Iq($client, $xml);
            case 'presence': return new Presence($client, $xml);
            case 'message':  return new Message($client, $xml);
            default:         return new Stanza($client, $xml);
        }
    }
}