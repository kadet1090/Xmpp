<?php
/**
 * Copyright (C) 2014, Some right reserved.
 * @author Kacper "Kadet" Donat <kadet1090@gmail.com>
 * @license http://creativecommons.org/licenses/by-sa/4.0/legalcode CC BY-SA
 *
 * Contact with author:
 * Xmpp: kadet@jid.pl
 * E-mail: kadet1090@gmail.com
 *
 * From Kadet with love.
 */

namespace Kadet\Xmpp\Connector;


use Kadet\SocketLib\SocketClient;
use Kadet\Xmpp\Stanza\Stanza;
use Kadet\Xmpp\Xml\XmlBranch;

class TcpConnector extends AbstractConnector {
    /** @var string */
    protected $_buffer;

    /** @var SocketClient */
    protected $_connection;

    /** @var string */
    protected $_server;

    /** @var int */
    protected $_port;

    protected $_features;
    protected $_info;

    public function __construct($server, $port = 5222) {
        parent::__construct();

        $this->_server = $server;
        $this->_port   = $port;

        $this->onReceive->add([$this, '_onPacket']);
    }

    public function connect()
    {
        $this->_lookup();

        $this->_connection = new SocketClient($this->_server, $this->_port);
        $this->_connection->connect(false);
        $this->_connection->send(XmlBranch::XML . "\n");
        $this->onConnect->run($this);
    }

    public function disconnect()
    {
        $this->_connection->send('</stream:stream>');
    }

    public function streamRestart($jid) {
        $stream = new XmlBranch('stream:stream');
        $stream
            ->addAttribute('to', $jid->server)
            ->addAttribute('xmlns', 'jabber:client')
            ->addAttribute('version', '1.0')
            ->addAttribute('xmlns:stream', 'http://etherx.jabber.org/streams');
        $this->_connection->send(str_replace('/>', '>', $stream->asXml()));
    }


    public function send($packet)
    {
        $this->_connection->send($packet);
        $this->onSend->run($this, $packet);
    }

    public function read()
    {
        $this->_buffer .= ($result = $this->_connection->receive());

        if(!empty($result))
            $this->_process();
    }

    public function _get_connected()
    {
        return $this->_connection->connected;
    }

    // TODO: better lookup
    private function _lookup() {
        $results = dns_get_record('_xmpp-client._tcp.'.$this->_server, DNS_SRV);
        foreach($results as $result) {
            if(isset($result['target'])) {
                $this->_server = $result['target'];
                if(isset($result['port'])) $this->_port = $result['port'];
                break;
            }
        }
    }

    public function _onPacket($conn, $xml) {
        if (substr($xml, 1, 7) == 'stream:') {
            $packet = XmlBranch::fromXml($xml);
            switch($packet->tag) {
                case "stream":
                    $this->_info = (object)$packet->attributes;
                    $this->onOpen->run($this, $this->_info);
                    break;
                case "features":
                    $this->_features = Stanza::fromXml($xml);
                    $this->onFeatures->run($this, $this->_features);
                    break;
            }
        }
    }

    private function _process()
    {
        $this->_buffer = preg_replace('/<\?xml.+\?>/', '', $this->_buffer);

        if (substr($this->_buffer, 1, 13) == 'stream:stream')
            $this->_buffer = substr_replace($this->_buffer, '</stream:stream>', strpos($this->_buffer, '>') + 1, 0);

        while ($packet = getCompleteXml($this->_buffer)) {
            $this->_buffer = str_replace($packet, '', $this->_buffer);
            $this->onReceive->run($this, $packet);
        }
    }

    public function _get_info()
    {
        return $this->_info;
    }

    public function _get_features()
    {
        return $this->_features;
    }

    public function startTls()
    {
        $this->_connection->encryption = true;
    }
}