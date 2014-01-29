<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace Kadet\Xmpp\Sasl;


class Plain extends Mechanism
{
    public function challenge($packet)
    {
        // Plain has no challenge
    }

    public function auth()
    {
        return base64_encode("\0{$this->jid->name}\0{$this->password}");
    }
}