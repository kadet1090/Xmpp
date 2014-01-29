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
use Kadet\Xmpp\Utils\XmlBranch;
use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Stanza\Iq;
use Kadet\Xmpp\XmppClient;

class Roster implements \ArrayAccess, \IteratorAggregate
{
    protected $_client;
    protected $_contacts = [];

    public $onItemChange;

    public function __construct(XmppClient $client)
    {
        $this->_client = $client;
        $this->_client->onIq->add([$this, '_onIq']);

        $this->onItemChange = new Event();
        $this->onItemChange->add([$this, '_onItemChange']);
    }

    /**
     * @param Iq $iq
     * @internal
     */
    public function _onIq(Iq $iq)
    {
        if ($iq->query == null || $iq->query->namespace != 'jabber:iq:roster') return;

        switch ($iq->type) {
            case 'error':
                Logger::warning('Error while retrieving roster from server!');
                return;
            case 'set':
            case 'result':
                foreach ($iq->query->item as $item) {
                    $this->fromXml($item);
                }
                break;
        }
    }

    public function add(Jid $jid, $name = null, $groups = [])
    {
        $item = new RosterItem($this, $jid, $name);
        $this->_client->write($this->itemXml($item));
    }

    public function _onItemChange(RosterItem $item)
    {
        var_dump($item);
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

    public function &byJid(Jid $jid)
    {
        $it = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($this->_contacts));
        foreach ($it as &$contact)
            if ($contact->jid == $jid) return $contact;
        return null;
    }


}