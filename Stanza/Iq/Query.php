<?php
/**
 * Created by PhpStorm.
 * User: Kacper
 * Date: 11.01.14
 * Time: 12:11
 */

namespace Kadet\Xmpp\Stanza\Iq;

use Kadet\Utils\Property;
use Kadet\Xmpp\Utils\XmlBranch;

class Query extends XmlBranch
{
    /**
     * @internal
     */
    public function _get_namespace()
    {
        return $this['xmlns'];
    }
} 