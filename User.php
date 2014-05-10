<?php
namespace Kadet\Xmpp;


use Kadet\Utils\Property;
use Kadet\Xmpp\Stanza\Presence;

/**
 * Class User
 *
 * @property-read string $affiliation Users affiliation on room (outcast, none, member, admin, owner)
 * @property-read string $role        Users role on room (visitor, none, participant, moderator)
 * @property-read string $show        Users show status, available, away, dnd, xa (extended away), unavailable
 * @property-read string $status      Users text status message
 *
 * @package Kadet\Xmpp
 */
class User
{
    use Property;

    /**
     * Users nick on room.
     * MUC ONLY
     * @var string
     */
    public $nick;


    /**
     * Users jid
     * @var Jid
     */
    public $jid;

    /**
     * Indicates if this user is our client.
     * @var bool
     */
    public $self;

    /**
     * Last presence from user.
     *
     * @var Presence
     */
    public $presence;

    /**
     * Xmpp Client instance.
     *
     * @var XmppClient
     */
    private $_client;

    public function _get_status()
    {
        return $this->presence->status;
    }

    public function _get_affiliation()
    {
        return $this->presence->affiliation;
    }

    public function _get_role()
    {
        return $this->presence->role;
    }

    public function _get_show()
    {
        return $this->presence->show;
    }

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
        $user->jid         = $presence->jid;
        $user->presence    = $presence;

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