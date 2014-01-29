<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kacper
 * Date: 08.08.13
 * Time: 21:31
 * To change this template use File | Settings | File Templates.
 */

namespace Kadet\Xmpp\Stanza;

use Kadet\Xmpp\Jid;

/**
 * Class Presence
 * @package Kadet\Xmpp\Stanza
 *
 * @property string $show
 * @property string $status
 * @property int    $priority
 * @property Jid    $jid
 * @property string $role
 * @property string affiliation
 */
class Presence extends Stanza
{
    private $_show;
    private $_status;
    private $_priority;
    private $_jid;
    private $_role;
    private $_affiliation;

    /**
     * @ignore
     */
    public function _get_show()
    {
        if (!isset($this->_show)) $this->_show = isset($this->xml->show) ? (string)$this->xml->show : 'available';
        return $this->_show;
    }

    /**
     * @ignore
     */
    public function _get_status()
    {
        if (!isset($this->_status)) $this->_status = isset($this->xml->status) ? (string)$this->xml->status : null;
        return $this->_status;
    }

    /**
     * @ignore
     */
    public function _get_priority()
    {
        if (!isset($this->_priority)) $this->_priority = isset($this->xml->priority) ? (string)$this->xml->priority : null;
        return $this->_priority;
    }

    /**
     * @ignore
     */
    public function _get_role()
    {
        if (!isset($this->_role)) {
            $this->_role = 'participant';
            if (isset($this->xml->x->item['role'])) $this->_role = $this->xml->x->item['role'];
            elseif (isset($this->xml->x[0]->item['role'])) $this->_role = $this->xml->x[0]->item['role']; elseif (isset($this->xml->x[1]->item['role'])) $this->_role = $this->xml->x[1]->item['role'];
        }
        return $this->_role;
    }

    /**
     * @ignore
     */
    public function _get_affiliation()
    {
        if (!isset($this->_affiliation)) {
            $this->_affiliation = 'none';
            if (isset($this->xml->x->item['affiliation'])) $this->_affiliation = $this->xml->x->item['affiliation'];
            elseif (isset($this->xml->x[0]->item['affiliation'])) $this->_affiliation = $this->xml->x[0]->item['affiliation']; elseif (isset($this->xml->x[1]->item['affiliation'])) $this->_affiliation = $this->xml->x[1]->item['affiliation'];
        }
        return $this->_affiliation;
    }

    /**
     * Helper, gets jid from packet.
     * @return Jid
     */
    public function _get_jid()
    {
        if (!isset($this->_jid)) {
            if (isset($this->xml->x->item['jid'])) $jid = $this->xml->x->item['jid'];
            elseif (isset($this->xml->x[0]->item['jid'])) $jid = $this->xml->x[0]->item['jid']; elseif (isset($this->xml->x[1]->item['jid'])) $jid = $this->xml->x[1]->item['jid']; else $jid = $this->xml['from'];

            $this->_jid = isset($jid) ? new Jid($jid) : null;
        }
        return $this->_jid;
    }
}