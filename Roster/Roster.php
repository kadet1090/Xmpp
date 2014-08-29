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
use Kadet\Xmpp\Xml\XmlBranch;
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
                if(isset($client->logger))
                    $client->logger->warning('Error while retrieving roster from server!');
                return;
            case 'set':
            case 'result':
                if(!isset($iq->query->item)) {
                    if(isset($client->logger))
                        $client->logger->info('Received empty roster.');
                    return;
                }

                foreach ($iq->query->item as $item) {
                    $changed[] = $item;
                    $this->onItem->run($this, $item);
                }

                break;
        }

        if(isset($client->logger))
            $client->logger->info('Received {count} roster items.', ['count' => count($changed)]);
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
        $item = new RosterItem($jid, $name);
        $this->_client->write($item->asXml());
    }

    public function _onItemChange(RosterItem $item)
    {
        $this->_client->write($item->asXml());
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