<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kacper
 * Date: 08.08.13
 * Time: 21:31
 * To change this template use File | Settings | File Templates.
 */

namespace Kadet\Xmpp\Stanza;
use Kadet\Xmpp\Xml\XmlArray;
use Kadet\Xmpp\Xml\XmlBranch;
use Kadet\Xmpp\XmppClient;

/**
 * Class Message
 * @package Kadet\Xmpp\Stanza
 *
 * @property string $body      Message body.
 * @property int    $timestamp Message timestamp.
 */
class Message extends Stanza {
    /**
     * @internal
     */
    public function _get_body() {
        return isset($this->content['body'][0]) ? $this->content['body'][0]->content : null;
    }

    public function _set_body($value) {
        if(!isset($this->content['body'])) $this->content['body'] = new XmlArray('body');
        $this->content['body'][0] = $value;
    }

    /**
     * @internal
     */
    public function _get_subject() {
        return isset($this->content['subject'][0]) ? $this->content['subject'][0]->content : null;
    }

    public function _set_subject($value) {
        if(!isset($this->content['subject'])) $this->content['subject'] = new XmlArray('subject');
        $this->content['subject'][0] = $value;
    }

    /**
     * @internal
     */
    public function _get_timestamp() {
        return isset($this->delay[0]['stamp']) ?
            strtotime($this->delay[0]['stamp']) :
            time();
    }

    public function reply($content) {
        if($this->type == 'groupchat')
            $jid = $this->sender->room->jid;
        else
            $jid = $this->from;

        $this->_xmpp->message($jid, $content, $this->type);
    }

    public function __construct($body = null, $to = null, $type = 'chat', $from = null)
    {
        parent::__construct('message');
        if($body != null) $this->body = $body;
        if($from != null) $this->from = $from;
        if($to != null) $this->to = $to;
        $this->type = $type;
    }

    /*public static function fromXml($xml, XmppClient $client = null)
    {
        if (!($xml instanceof \SimpleXMLElement))
            $xml = @simplexml_load_string($xml);

        if($xml->getName() != 'message')
            return XmlBranch::fromXml($xml);

        return parent::fromXml($xml, $client);
    }*/
}