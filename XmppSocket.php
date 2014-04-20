<?php
namespace Kadet\Xmpp;

use Kadet\SocketLib\SocketClient;
use Kadet\Utils\Event;
use Kadet\Utils\Logger;
use Kadet\Utils\Timer;

abstract class XmppSocket extends SocketClient
{
    public $onPacket;

    private $_waiting = array();
    protected $_features;
    protected $_stream;

    /**
     * @param $address
     * @param $port
     * @param int $timeout
     */
    public function __construct($address, $port = 5222, $timeout = 30)
    {
        parent::__construct($address, $port, 'tcp', $timeout);

        $this->onPacket = new Event();
        $this->keepAliveTimer = new Timer(15, array($this, 'keepAliveTick'));
        $this->keepAliveTimer->stop(); // We don't want to run this before connection is finalized.
        $this->onPacket->add(array($this, '_onPacket'));
        $this->onDisconnect->add(array($this, '_onDisconnect'));

        $settings = [
            'indent' => true,
            'input-xml' => true,
            'output-xml' => true,
            'wrap' => 0
        ];

        $this->onReceive->add(function (SocketClient $socket, $packet) use ($settings) {
            $len = strlen($packet);

            if(function_exists('tidy_repair_string'))
                $packet = trim(tidy_repair_string($packet, $settings));

            if(isset($socket->logger))
                $socket->logger->debug("Received {length} bytes: \n{packet}", [
                    'length' => $len,
                    'packet' => $packet
                ]);
        });
        $this->onSend->add(function ($socket, $packet) use ($settings) {
            $len = strlen($packet);

            if(function_exists('tidy_repair_string'))
                $packet = trim(tidy_repair_string($packet, $settings));

            if(isset($socket->logger))
                $socket->logger->debug("sent {length} bytes: \n{packet}", [
                    'length' => $len,
                    'packet' => $packet
                ]);
        });
    }

    public function read()
    {
        $result = '';
        do {
            if (($content = stream_get_contents($this->_socket)) === false) {
                $this->disconnect();
                $this->raiseError();

                return false;
            }

            $result .= $content;
        } while (!preg_match("/('\/|\"\/|iq|ge|ce|am|es|se|ss|ge|re|.'|.\")>$/", substr($result, -3)) && !empty($result));

        if (!empty($result))
            $this->onReceive->run($this, $result);

        $this->_parse(trim($result));

        return $result;
    }

    /**
     * @param string $xml
     */
    private function _parse($xml)
    {
        $packets = preg_split("/<(\/stream:stream|stream|iq|presence|message|proceed|failure|challenge|response|success)/", $xml, -1, PREG_SPLIT_DELIM_CAPTURE);
        for ($i = 1, $c = count($packets); $i < $c; $i += 2) {
            $xml = "<" . $packets[$i] . $packets[($i + 1)];

            if (strpos($xml, '<stream:stream') !== false) $xml .= '</stream:stream>';
            $this->onPacket->run($this, simplexml_load_string(preg_replace('/(<\/?)([a-z]*?)\:/si', '$1', $xml)));
        }
    }

    /**
     * @param string $type
     * @param int $id
     * @param callable $delegate
     */
    public function wait($type, $id, callable $delegate)
    {
        $this->_waiting[] = array(
            'tag' => $type,
            'id' => $id,
            'delegate' => $delegate
        );
    }

    /**
     * @param XmppSocket        $socket
     * @param \SimpleXMLElement $packet
     *
     * @internal
     */
    public function _onPacket(XmppSocket $socket, \SimpleXMLElement $packet)
    {
        $name = $packet->getName();
        if ($name == 'features')
            $this->_features = $packet;
        elseif ($name == 'stream')
            $this->_stream = $packet;

        foreach ($this->_waiting as &$wait) {
            if (
                (empty($wait['tag']) || $name == $wait['tag']) &&
                (empty($wait['id']) || $packet['id'] == $wait['id'])
            ) {
                $wait['delegate']($packet);
            }
        }
    }

    /**
     * @param \Kadet\SocketLib\SocketClient $socket
     *
     * @internal
     */
    public function _onDisconnect(SocketClient $socket)
    {
        $socket->send('</stream:stream>');
    }

    public function write($packet) { $this->send($packet); }

    /**
     * @internal
     */
    public function keepAliveTick()
    {
    }
}