<?php

namespace Kadet\Xmpp;

class Room
{
    /**
     * Rooms client.
     * @var XmppClient
     */
    private $_client;

    /**
     * Rooms jid
     * @var \Kadet\Xmpp\Jid
     */
    public $jid;

    /**
     * Stores data of room users accessed by nick.
     * @var User[]
     */
    public $users = array();

    /**
     * Rooms subject
     * @var string|bool
     */
    public $subject = false;

    /**
     * Stores room configuration with additional client data (ie. join time)
     * @var \SimpleXMLElement
     */
    public $configuration = array();

    /**
     * Clients nick on room
     * @var string
     */
    public $nick;

    protected static $config;

    /**
     * @param XmppClient $client Xmpp Client instance.
     * @param Jid        $jid    Room Jid.
     * @param string     $nick   Clients nick on room.
     */
    public function __construct(XmppClient $client, Jid $jid, $nick)
    {
        $this->_client = $client;
        $this->jid     = $jid;
        $this->nick    = $nick;

        if (empty(self::$config))
            self::$config = simplexml_load_file('./Config/Rooms.xml');

        $this->configuration = self::$config->xpath("/rooms/room[@jid='{$this->jid->bare()}']");

        if (empty($this->configuration)) {
            self::$config->addChild('room');
            self::$config->room[count(self::$config->room) - 1]->addAttribute('jid', $this->jid->bare());
            $this->configuration = self::$config->room[count(self::$config->room) - 1];
        } else {
            $this->configuration = $this->configuration[0];
        }

        $this->configuration->jointime = time();

        self::$config->saveXML('./Config/Rooms.xml');
    }

    /**
     * Sends message to the channel.
     *
     * @param string $content Message content.
     */
    public function message($content)
    {
        $this->_client->message($this->jid, $content, 'groupchat');
    }

    /**
     * Kicks out specified user from the channel.
     *
     * @param string $nick   User nick.
     * @param string $reason Reason of kick.
     */
    public function kick($nick, $reason = '')
    {
        $this->role($nick, 'none', $reason);
    }

    /**
     * Changes specified user role on the channel.
     * @param string $nick   User nick.
     * @param string $role   Must be one of: visitor (no voice), none (aka kick), participant (standard role), moderator (can kick out users)
     * @param string $reason Reason of changing role.
     */
    public function role($nick, $role, $reason = '')
    {
        if (!isset($this->users[$nick])) return; // Exception maybe?
        $this->_client->role($this->jid, $nick, $role, $reason);
    }

    /**
     * Bans user on the channel.
     *
     * @param Jid|string $who    User to ban, nick or Jid
     * @param string     $reason Ban reason.
     */
    public function ban($who, $reason = '')
    {
        $this->affiliate($who, 'outcast', $reason);
    }

    /**
     * Unbans user on the channel.
     *
     * @param Jid|string $who    Users nick or Jid.
     * @param string     $reason Unban reason.
     */
    public function unban($who, $reason = '')
    {
        $this->affiliate($who, 'none', $reason);
    }

    /**
     * Changes user affiliation on the channel.
     *
     * @param Jid|string $who         Users nick or Jid.
     * @param string     $affiliation New user affiliation. Must be one of: owner (channels god), admin, outcast (aka ban), member (vip, or something), none (standard)
     * @param string     $reason      Reason of affiliation change.
     *
     * @throws \InvalidArgumentException
     */
    public function affiliate($who, $affiliation, $reason = '')
    {
        if (!($who instanceof Jid)) {
            if (!isset($this->users[$who])) throw new \InvalidArgumentException('who');
            $who = $this->users[$who]->jid;
        }

        $this->_client->affiliate($this->jid, $who, $affiliation, $reason);
    }

    /**
     * Gets user list with who has specified affiliation.
     *
     * @param string   $affiliation Type of affiliation. Must be one of: owner (channels god), admin, outcast (aka ban), member (vip, or something), none (standard)
     * @param callable $delegate    Delegate to be executed after list came.
     */
    public function affiliationList($affiliation, callable $delegate)
    {
        $this->_client->affiliationList($this->jid, $affiliation, $delegate);
    }

    /**
     * Gets out of the room.
     */
    public function leave()
    {
        $this->_client->leave($this->jid);
    }

    /**
     * Adds user to the room.
     *
     * @param User $user User to add.
     *
     * @return \Kadet\Xmpp\User
     */
    public function addUser(User $user)
    {
        $user->room = $this;
        return $this->users[$user->nick] = $user;
    }

    /**
     * Removes user from the room.
     *
     * @param User $user User to remove.
     */
    public function removeUser(User $user)
    {
        unset($this->users[$user->nick]);
    }

    /**
     * Sets room subject.
     *
     * @param string $subject New subject
     */
    public function setSubject($subject)
    {
        $this->_client->setSubject($this->jid, $subject);
    }

    /**
     * Saves rooms configuration to file.
     */
    public static function save()
    {
        self::$config->asXML('./Config/Rooms.xml');
    }
}