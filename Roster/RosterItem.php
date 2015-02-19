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
use Kadet\Xmpp\Xml\XmlBranch;

/**
 * Class RosterItem
 *
 * @package Kadet\Xmpp\Roster
 * @property-read string   $subscription
 * @property-read bool     $approved
 * @property-read string   $ask
 * @property-read Presence $presence     Stores last presence.
 * @property-read string[] $groups
 * @property      string   $name
 * @property      Jid      $jid
 */
class RosterItem extends XmlBranch
{
    use Property;

    private $_presence = null;
    private $_groups = [];

    //<editor-fold desc="Accessors">

    public function __construct(Jid $jid = null, $name = null)
    {
        $this->jid  = $jid;
        $this->name = $name;
    }

    public function _get_name()
    {
        return isset($this['name']) ? $this['name'] : $this['jid'];
    }

    public function _set_name($name)
    {
        $this['name'] = $name;
    }

    public function _get_groups()
    {

    }

    public function _get_jid()
    {
        return new Jid($this['jid']);
    }

    public function _set_jid(Jid $jid = null)
    {
        $this['jid'] =  $jid ? $jid->bare() : null;
    }

    public function _get_subscription()
    {
        return isset($this['subscription']) ? $this['subscription'] : 'both';
    }

    public function _get_ask()
    {
        return isset($this['ask']) ? $this['ask'] : 'none';
    }

    public function _get_presence()
    {
        return $this->_presence;
    }

    //</editor-fold>

    public function _get_approved()
    {
        return !(isset($this['approved']) && $this['approved'] == 'false');
    }

    public function addGroup($group)
    {
        if (!array_search($group, $this->_groups))
            $this->_groups[] = $group;
    }

    public function removeGroup($group)
    {
        if ($key = array_search($group, $this->_groups))
            unset($this->_groups[$key]);
    }

    public function applyPresence(Presence $presence)
    {
        if ($presence->type != 'unavailable')
            $this->_presence[$presence->from->resource] = $presence;
        else
            unset($this->_presence[$presence->from->resource]);
    }
} 