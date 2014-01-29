<?php
/**
 * Created by PhpStorm.
 * User: Kacper
 * Date: 11.01.14
 * Time: 15:14
 */

namespace Kadet\Xmpp\Roster;


use Kadet\Utils\Property;
use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Stanza\Presence;

/**
 * Class RosterItem
 * @package Kadet\Xmpp\Roster
 * @property-read string   $subscription
 * @property-read bool     $approved
 * @property-read string   $ask
 * @property-read string   $show
 * @property-read string   $status
 * @property-read string[] $groups
 * @property      string   $name
 * @property      Jid      $jid
 */
class RosterItem
{
    use \Kadet\Utils\Property;

    private $_name;
    private $_jid;
    private $_subscription;
    private $_approved;
    private $_ask;

    private $_show;
    private $_status;

    private $_groups = [];

    private $_roster;

    //<editor-fold desc="Accessors">
    public function _get_name()
    {
        return $this->_name;
    }

    public function _set_name($name)
    {
        $this->_name = $name;
        $this->_roster->onItemChange->run($this);
    }

    public function _get_groups()
    {
        return $this->_groups;
    }

    public function _get_jid()
    {
        return $this->_jid;
    }

    public function _set_jid()
    {
        // TODO: add ability to change jid [remove, and add contact]
    }

    public function _get_subscription()
    {
        return $this->_subscription;
    }

    public function _get_approved()
    {
        return $this->_approved;
    }

    public function _get_ask()
    {
        return $this->_ask;
    }

    public function _get_show()
    {
        return $this->_show;
    }

    public function _get_status()
    {
        return $this->_status;
    }

    //</editor-fold>

    public function __construct(Roster $roster, Jid $jid, $name = null)
    {
        $this->_roster = $roster;
        $this->_jid = $jid;
        $this->_name = $name == null ? (string)$jid : $name;
    }

    public function addGroup($group)
    {
        if (!array_search($group, $this->_groups))
            $this->_groups[] = $group;
        $this->_roster->onItemChange->run($this);
    }

    public function removeGroup($group)
    {
        if ($key = array_search($group, $this->_groups))
            unset($this->_groups[$key]);
        $this->_roster->onItemChange->run($this);
    }

    public function processPresence(Presence $presence)
    {
        $this->_show = $presence->show;
        $this->_status = $presence->status;
    }

    public static function fromXml(Roster $roster, $xml)
    {
        $item = new RosterItem($roster, new Jid((string)$xml['jid']), isset($xml['name']) ? (string)$xml['name'] : null);
        $item->_approved = isset($xml['approved']) && $xml['approved'] == 'false' ? false : true;
        $item->_subscription = isset($xml['subscription']) ? (string)$xml['subscription'] : 'both';
        $item->_ask = isset($xml['ask']) ? (string)$xml['ask'] : 'none';

        if (!isset($xml->group)) {
            $item->_groups[] = 'default';
        } elseif (!is_array($xml->group)) {
            $item->_groups[] = (string)$xml->group;
        } else {
            foreach ($xml->group as $group)
                $item->_groups[] = (string)$group;
        }

        return $item;
    }
} 