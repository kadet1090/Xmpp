<?php
namespace Kadet\Xmpp;


use Kadet\Xmpp\Stanza\Presence;

class User
{
    /**
     * Users nick on room.
     * MUC ONLY
     * @var string
     */
    public $nick;

    /**
     * Users affiliation on room (outcast, none, member, admin, owner)
     * MUC ONLY
     * @var string
     */
    public $affiliation;

    /**
     * Users role on room (visitor, none, participant, moderator)
     * MUC ONLY
     * @var string
     */
    public $role;

    /**
     * Users jid
     * @var Jid
     */
    public $jid;

    /**
     * Users show status, available, away, dnd, xa (extended away), unavailable
     * @var string
     */
    public $show;

    /**
     * Users status (description)
     * @var string
     */
    public $status;

    /**
     * Users chat room.
     * @var Room
     */
    public $room;

    /**
     * Indicates if this user is our client.
     * @var bool
     */
    public $self;

    /**
     * Xmpp Client instance.
     *
     * @var XmppClient
     */
    private $_client;

    /**
     * @param XmppClient $client Xmpp Client instance.
     */
    public function __construct($client)
    {
        $this->_client = $client;
    }

    /**
     * Makes user object from presence packet.
     *
     * @param Presence   $presence Presence element.
     * @param XmppClient $client   XmppClient instance.
     *
     * @throws \InvalidArgumentException
     *
     * @return User User created from presence.
     */
    public static function fromPresence(Presence $presence, XmppClient $client)
    {
        $user              = new User($client);
        $user->nick        = $presence->from->resource;
        $user->affiliation = $presence->affiliation;
        $user->role        = $presence->role;
        $user->jid         = $presence->jid;
        $user->show        = $presence->show;
        $user->status      = $presence->status;

        return $user;
    }

    /**
     * Gets room jid of user.
     *
     * @return Jid User Users jid on room nick@room.tld
     */
    public function roomJid()
    {
        if (!isset($this->room)) return $this->jid;

        return new Jid($this->room->jid->name, $this->room->jid->server, $this->nick);
    }

    /**
     * Sends private message over MUC to user.
     *
     * @param string $content Message content.
     */
    public function privateMessage($content)
    {
        $this->_client->message($this->roomJid(), $content);
    }

    /**
     * Sends message to user.
     * @param string $content Message content.
     */
    public function message($content)
    {
        $this->_client->message($this->jid, $content);
    }
}