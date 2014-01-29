<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kacper
 * Date: 08.08.13
 * Time: 21:31
 * To change this template use File | Settings | File Templates.
 */

namespace Kadet\Xmpp\Stanza;

use Kadet\Xmpp\Stanza\Iq\Query;

/**
 * Class Iq
 * @package Kadet\Xmpp\Stanza
 * @property Query $query
 */
class Iq extends Stanza
{
    private $_query;

    public function _get_query()
    {
        if (!isset($this->xml->query)) return null;
        if (!isset($this->_query)) $this->_query = new Query($this->xml->query);
        return $this->_query;
    }
}