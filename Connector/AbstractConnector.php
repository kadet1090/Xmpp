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


use Kadet\Utils\Event;
use Kadet\Utils\Property;
use Kadet\Xmpp\Xml\XmlBranch;
use Kadet\Xmpp\XmppClient;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractConnector
 *
 * @package Kadet\Xmpp\Connector
 *
 * @property-read XmlBranch $features
 * @property-read object    $info
 */
abstract class AbstractConnector
{
    use Property;

    /**
     * @var Event
     * @event(AbstractConnector $connector, XmlBranch $packet)
     */
    public $onSend;
    /**
     * @var Event
     * @event(AbstractConnector $connector, XmlBranch $packet)
     */
    public $onReceive;
    /**
     * @var Event
     * @event(AbstractConnector $connector)
     */
    public $onDisconnect;
    /**
     * @var Event
     * @event(AbstractConnector $connector)
     */
    public $onConnect;
    /**
     * @var Event
     * @event(AbstractConnector $connector)
     */
    public $onClose;
    /**
     * @var Event
     * @event(AbstractConnector $connector)
     */
    public $onOpen;
    /**
     * @var Event
     * @event(AbstractConnector $connector, XmlBranch $error)
     */
    public $onStreamError;
    /**
     * @var Event
     * @event(AbstractConnector $connector, ConnectionException $e)
     */
    public $onConnectionError;
    /**
     * @var Event
     * @event(AbstractConnector $connector, XmlBranch $features)
     */
    public $onFeatures;
    /**
     * @var XmppClient
     */
    public $client;

    public function __construct()
    {
        $this->onReceive     = new Event();
        $this->onSend        = new Event();
        $this->onConnect     = new Event();
        $this->onDisconnect  = new Event();
        $this->onOpen        = new Event();
        $this->onClose       = new Event();
        $this->onStreamError = new Event();
        $this->onFeatures    = new Event();
    }

    public abstract function connect();

    public abstract function disconnect();

    public abstract function send($packet);

    public abstract function read();

    public abstract function streamRestart($jid);

    public abstract function _get_connected();

    public abstract function _get_info();

    public abstract function _get_features();

    public abstract function startTls();
}