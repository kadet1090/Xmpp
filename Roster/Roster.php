<?php
/**
 * Created by PhpStorm.
 * User: Kacper
 * Date: 11.01.14
 * Time: 12:04
 */

namespace Kadet\Xmpp\Roster;


use Kadet\Utils\Event;
use Kadet\Utils\Logger;
use Kadet\Utils\Property;
use Kadet\Xmpp\Stanza\Presence;
use Kadet\Xmpp\Utils\XmlBranch;
use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Stanza\Iq;
use Kadet\Xmpp\XmppClient;

/**
 * Class Roster
 * @package Kadet\Xmpp\Roster
 * @property-read RosterItem[] $contacts Gives access to all contacts without groups.
 */
class Roster implements \ArrayAccess, \IteratorAggregate
{
    use Property;

    protected $_client;
    protected $_contacts = [];

    public $onItemChange;
    public $onItem;
    public $onComplete;

    public function __construct(XmppClient $client)
    {
        $this->_client = $client;
        $this->_client->onIq->add([$this, '_onIq']);
        $this->_client->onPresence->add([$this, '_onPresence']);


        $this->onItemChange = new Event();
        $this->onItem       = new Event();
        $this->onComplete   = new Event();

        $this->onItemChange->add([$this, '_onItemChange']);
    }

    /**
     * @param \Kadet\Xmpp\XmppClient $client
     * @param Iq                     $iq
     *
     * @internal
     */
    public function _onIq(XmppClient $client, Iq $iq)
    {
        if ($iq->query == null || $iq->query->namespace != 'jabber:iq:roster') return;

        $changed = [];
        switch ($iq->type) {
            case 'error':
                Logger::warning('Error while retrieving roster from server!');
                return;
            case 'set':
            case 'result':
                foreach ($iq->query->item as $item)
                    $changed[] = $this->fromXml($item);

                break;
        }

        $this->onComplete->run($this, $changed);
    }

    /**
     * @param \Kadet\Xmpp\XmppClient $client
     * @param Presence               $presence
     *
     * @internal
     */
    public function _onPresence(XmppClient $client, Presence $presence) {
        if($item = $this->byJid($presence->from))
            $item->applyPresence($presence);
    }

    // @todo groups support
    public function add(Jid $jid, $name = null, $groups = [])
    {
        $item = new RosterItem($this, $jid, $name);
        $this->_client->write($this->itemXml($item));
    }

    public function _onItemChange(RosterItem $item)
    {

        $this->_client->write($this->itemXml($item));
    }

    private function itemXml(RosterItem $item)
    {
        $xml = new XmlBranch("iq");
        $xml->addAttribute("id", uniqid('roster_'));
        $xml->addAttribute('from', $this->_client->jid);
        $xml->addAttribute("type", "set");
        $xml->addChild(new XmlBranch("query"))->addAttribute("xmlns", "jabber:iq:roster");
        $xml->query[0]->addChild(new XmlBranch('item'))->addAttribute('jid', $item->jid->bare());

        if ($item->name != null) $xml->query[0]->item[0]->addAttribute('name', $item->name);
        if (!empty($groups)) {
            foreach ($groups as $group) {
                $xml->query[0]->item[0]->addChild(new XmlBranch('group'))->setContent('group');
            }
        }

        return $xml;
    }

    private function fromXml($item)
    {
        $contact = RosterItem::fromXml($this, $item);
        $this->onItem->run($this, $contact);
        foreach ($this->_contacts as $name => $group) {
            foreach ($group as $key => $rc) {
                if ($rc->jid->bare() == $contact->jid->bare())
                    unset($this->_contacts[$name][$key]);
            }
        }

        foreach ($contact->groups as $group) {
            if (!isset($this->_contacts[$group])) $this->_contacts[$group] = [];
            $this->_contacts[$group][$contact->name] = & $contact;
        }

        return $contact;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->_contacts[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->_contacts[$offset];
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_contacts);
    }

    /**
     * @param Jid $jid
     *
     * @return RosterItem|null
     */
    public function byJid(Jid $jid)
    {
        foreach ($this->contacts as $contact) {
            if ($contact->jid->bare() == $jid->bare())
                return $contact;
        }
        return null;
    }

    public function _get_contacts() {
        $contacts = [];
        foreach($this as $group)
            $contacts = array_merge($contacts, $group);

        return array_unique($contacts, SORT_REGULAR);
    }
}