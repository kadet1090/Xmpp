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


use Kadet\Utils\Property;

class HttpResponse
{
    use Property;

    protected $_content;
    protected $_headers;
    protected $_code;
    protected $_version;
    protected $_status;

    public function __construct($query)
    {
        $headers = strpos($query, "\r\n\r\n") !== false ? trim(strstr($query, "\r\n\r\n", true)) : $query;
        $this->_parseHeaders($headers);

        $this->_content = substr($query, strlen($headers) + 4);
    }

    public function __toString()
    {
        return (string)$this->_content;
    }

    protected function _parseHeaders($headers) {
        $lines = explode("\r\n", $headers);
        $code = array_shift($lines);

        preg_match('/^HTTP\/(?P<version>\d\.\d) (?P<code>\d+) (?P<string>.*)$/', $code, $matches);
        $this->_code = $matches['code'];
        $this->_status = $matches['string'];
        $this->_version = $matches['version'];

        foreach($lines as $line)
            $this->_headers[strstr($line, ':', true)] = substr(strstr($line, ':'), 2);
    }

    public function header($header) {
        return isset($this->_headers[$header]) ? $this->_headers[$header] : false;
    }

    public function _get_code() {
        return $this->_code;
    }

    public function _get_status() {
        return $this->_status;
    }

    public function _get_version() {
        return $this->_version;
    }
} 