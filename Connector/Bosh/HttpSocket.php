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

namespace Kadet\Xmpp\Connector\Bosh;


use Kadet\SocketLib\SocketClient;

class HttpSocket extends SocketClient
{
    protected $_busy = false;

    public function __construct($address, $timeout = 10)
    {
        $this->_url = @parse_url($address);
        if (!$this->_url)
            throw new \InvalidArgumentException('Given $address is not valid url.');

        parent::__construct(
            $this->_url['host'],
            isset($this->_url['port']) ? $this->_url['port'] : 80,
            isset($this->_url['scheme']) && $this->_url['scheme'] == 'https' ? 'ssl' : 'tcp',
            $timeout
        );
    }

    public function send($content, $method = 'GET', $headers = [])
    {
        $this->_busy = true;

        $query   = "{$method} {$this->_url['path']} HTTP/1.1\r\n";

        $headers = array_merge([
            'Host'           => $this->_url['host'],
            'Content-Length' => strlen($content)
        ], $headers);

        $query .= implode("\r\n", array_map(function ($key, $value) {
            return $key . ': ' . $value;
        }, array_keys($headers), $headers));

        $query .= "\r\n\r\n";
        $query .= $content;

        $this->onSend->run($this, $content);
        return $this->_send($query);
    }

    public function receive()
    {
        $result = '';
        while($string = $this->_receive())
            $result .= $string;

        if ($result) {
            $this->_busy = false;

            $result = new HttpResponse($result);
            $this->onReceive->run($this, $result);
            return $result;
        }

        return null;
    }

    public function query($method = 'GET', $headers = []) {
        return $this->send(null, $method, $headers);
    }

    public function _get_busy() {
        return $this->_busy;
    }
} 