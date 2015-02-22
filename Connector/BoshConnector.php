<?php
/**
 * Copyright (C) 2014, Some right reserved.
 *
 * @author  Kacper "Kadet" Donat <kadet1090@gmail.com>
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

class BoshConnector extends AbstractConnector
{
    protected $_address;
    /** @var SocketClient[] */
    protected $_connections;
    protected $_connected;
    protected $_features;
    protected $_info;
    protected $_rid = 1;
    protected $_ack = 1;
    protected $_queue;

    public function __construct($address)
    {
        parent::__construct();
        $this->onReceive->add([$this, '_onPacket']);
        $this->_address = $address;
    }

    public function connect()
    {
        $this->_connected = true;

        $this->_addConnection();

        $this->onConnect->run($this);

        return true;
    }

    protected function _addConnection()
    {
        $connection = new Bosh\HttpSocket($this->_address);
        $connection->connect(false);
        $connection->onReceive->add([$this, 'connection_onReceive']);
        $connection->onDisconnect->add([$this, 'connection_onDisconnect']);

        $this->_connections[] = $connection;

        return $connection;
    }

    public function disconnect()
    {
        $body = new XmlBranch('body');
        $body->addAttribute('sid', $this->_info->sid)
            ->addAttribute('rid', ++$this->_rid)
            ->addAttribute('xmlns', 'http://jabber.org/protocol/httpbind')
            ->addAttribute('type', 'terminate');
        $packet = $body->asXml();

        $this->onSend->run($this, $packet);

        $this->connection->send($packet, 'POST', [
            'Content-Type'   => 'text/xml; charset=utf-8',
            'Content-Length' => strlen($packet),
            'Connection'     => 'close'
        ]);

        foreach ($this->_connections as $connection) {
            $connection->disconnect();
        }
    }

    public function send($packet)
    {
        $this->_queue[] = $packet;

        return true;
    }

    public function read()
    {
        foreach ($this->_connections as $connection)
            $connection->receive();

        $this->_send();

        $available = array_filter($this->_connections, function (SocketClient $connection) {
            return $connection->busy;
        });

        // at least one connection should be waiting for data
        if (empty($available))
            $this->_connections[array_rand($this->_connections)]->send($this->_wrap(''), 'POST');
    }

    public function _send()
    {
        if (empty($this->_queue)) return;

        $available = array_filter($this->_connections, function (SocketClient $connection) {
            return !$connection->busy;
        });

        if (empty($available)) return;

        $connection = $available[array_rand($available)];

        $body = '';
        while ($packet = array_shift($this->_queue)) {
            $this->onSend->run($this, $packet);
            $body .= $packet."\n";
        }

        $body = $this->_wrap(trim($body));
        if(isset($this->client->logger))
            $this->client->logger->debug('Sent BOSH request ({size} bytes): '.PHP_EOL.'{body}', [
                'size' => strlen($body),
                'body' => $body
            ]);

        $connection->send($body, 'POST', [
            'Content-Type'   => 'text/xml; charset=utf-8',
            'Content-Length' => strlen($body)
        ]);
    }

    private function _wrap($packet)
    {
        return "<body sid='{$this->_info->sid}' rid='" . ($this->_rid++) . "' xmlns='http://jabber.org/protocol/httpbind'>\n{$packet}\n</body>";
    }

    public function connection_onReceive($connection, $data)
    {
        if(isset($this->client->logger))
            $this->client->logger->debug('Received BOSH response ({size} bytes) [{code} {status}]: '.PHP_EOL.'{body}', [
                'size' => strlen((string)$data),
                'body' => (string)$data,
                'code' => $data->code,
                'status' => $data->status
            ]);

        if ($data->code != 200) return;
        $body = dom_import_simplexml(simplexml_load_string((string)$data));

        if (!isset($this->_info)) {
            $this->_info = new \stdClass();
            foreach ($body->attributes as $attr) {
                $this->_info->{$attr->nodeName} = $attr->nodeValue;
            }
            $this->_info->id = $this->_info->sid;
            $this->onOpen->run($this, $this->_info);
        }

        foreach ($body->childNodes as $child) {
            $this->onReceive->run($this, $child->ownerDocument->saveXML($child));
        }
    }

    public function connection_onDisconnect($connection)
    {
        if (isset($this->client->logger))
            $this->client->logger->debug('Connection #{id} closed, {left} connections left.', [
                'id' => array_search($connection, $this->_connections),
                'left' => count($this->_connections) - 1
            ]);

        unset($this->_connections[array_search($connection, $this->_connections)]);
    }

    public function streamRestart($jid)
    {
        $body = new XmlBranch('body');
        $body->addAttribute('content', 'text/xml; charset=utf-8')
            ->addAttribute('from', (string)$jid)
            ->addAttribute('to', $jid->server)
            ->addAttribute('rid', $this->_rid++)
            ->addAttribute('xml:lang', 'en')
            ->addAttribute('xmlns', 'http://jabber.org/protocol/httpbind')
            ->addAttribute('xmlns:xmpp', 'urn:xmpp:xbosh');

        if (!isset($this->_info))
            $body->addAttribute('ver', '1.6')
                ->addAttribute('wait', 60)
                ->addAttribute('hold', 1)
                ->addAttribute('ack', $this->_ack)
                ->addAttribute('xmpp:version', '1.0');
        else
            $body->addAttribute('sid', $this->_info->id)
                ->addAttribute('xmpp:restart', 'true');

        $body = $body->asXml();

        $this->onSend->run($this, $body);
        $this->connection->send($body, 'POST', [
            'Content-Type'   => 'text/xml; charset=utf-8',
            'Content-Length' => strlen($body)
        ]);
    }

    public function _get_connected()
    {
        return $this->_connected;
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
        return false; // encryption is supported on higher layer
    }

    public function _onPacket($conn, $xml)
    {
        if (substr($xml, 1, 7) == 'stream:') {
            $packet = XmlBranch::fromXml($xml);
            switch ($packet->tag) {
                case "error":
                    $this->onStreamError->run($this, $packet);
                    break;
                case "features":
                    $this->_features = Stanza::fromXml($xml);
                    $this->onFeatures->run($this, $this->_features);
                    break;
            }
        }
    }

    protected function _get_connection()
    {
        $available = array_filter($this->_connections, function (SocketClient $connection) {
            return !$connection->busy;
        });

        if (empty($available)) {
            $connection = $this->_addConnection();

            if ($this->client->logger)
                $this->client->logger->debug('No free connections, created new one (#{id}), total: {total}', [
                    'id' => array_search($connection, $this->_connections),
                    'total' => count($this->_connections)
                ]);

            return $connection;
        }

        return $available[array_rand($available)];
    }
}