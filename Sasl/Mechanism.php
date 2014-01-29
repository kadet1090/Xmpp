<?php
namespace Kadet\Xmpp\Sasl;

use Kadet\Xmpp\Jid;

abstract class Mechanism implements MechanismInterface {
    /**
     * @var Jid
     */
    protected $jid;
    protected $password;

    public function __construct($jid, $password) {
        $this->jid      = $jid;
        $this->password = $password;
    }
}