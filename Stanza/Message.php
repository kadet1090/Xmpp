<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kacper
 * Date: 08.08.13
 * Time: 21:31
 * To change this template use File | Settings | File Templates.
 */

namespace Kadet\Xmpp\Stanza;

/**
 * Class Message
 * @package Kadet\Xmpp\Stanza
 *
 * @property string $body      Message body.
 * @property int    $timestamp Message timestamp.
 */
class Message extends Stanza {
    private $_body;
    private $_timestamp;

    /**
     * @internal
     */
    public function _get_body() {
        if(!isset($this->_body)) $this->_body = (string)$this->xml->body;
        return $this->_body;
    }

    /**
     * @internal
     */
    public function _get_timestamp() {
        if(!isset($this->_timestamp)) {
            $this->_timestamp = isset($this->xml->delay['stamp']) ?
                strtotime($this->xml->delay['stamp']) :
                time();
        }

        return $this->_timestamp;
    }

    public function reply($content) {
        if($this->type == 'groupchat')
            $jid = $this->sender->room->jid;
        else
            $jid = $this->from;

        $this->_xmpp->message($jid, $content, $this->type);
    }
}