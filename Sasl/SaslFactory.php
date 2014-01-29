<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author  Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace Kadet\Xmpp\Sasl;


use Kadet\Xmpp\Jid;

class SaslFactory
{
    protected static $_mechanisms = array(
        'PLAIN'      => 'Kadet\\Xmpp\\Sasl\\Plain',
        'DIGEST-MD5' => 'Kadet\\Xmpp\\Sasl\\DigestMd5',
    );

    /**
     * @param                 $mechanism
     * @param \Kadet\Xmpp\Jid $jid
     * @param                 $password
     *
     * @return MechanismInterface
     */
    public static function get($mechanism, Jid $jid, $password)
    {
        if (isset(self::$_mechanisms[strtoupper($mechanism)])) return new self::$_mechanisms[strtoupper($mechanism)]($jid, $password);
        else return null;
    }
}