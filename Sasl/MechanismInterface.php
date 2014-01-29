<?php
namespace Kadet\Xmpp\Sasl;

interface MechanismInterface {
    public function challenge($packet);

    public function auth();
}