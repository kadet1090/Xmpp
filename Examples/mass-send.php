<?php
/**
 * Copyright (C) 2015, Some right reserved.
 * @author Kacper "Kadet" Donat <kadet1090@gmail.com>
 * @license http://creativecommons.org/licenses/by-sa/4.0/legalcode CC BY-SA
 *
 * Contact with author:
 * Xmpp: kadet@jid.pl
 * E-mail: kadet1090@gmail.com
 *
 * From Kadet with love.
 */

include '../vendor/autoload.php';

// Jids
$jids = [
    'jid1@server.com',
    'jid2@server.com',
    'jid3@server.org'
];

$message = 'Not spam, trust me.';

// Prepare client
$connector = new Kadet\Xmpp\XmppClient(
    new \Kadet\Xmpp\Jid('your@jid.com/resource'),
    'password'
);

// Try to connect to server
$connector->connect() or die("Cannot connect to server.");

$connector->onReady->add(function(\Kadet\Xmpp\XmppClient $connector) use ($jids, $message) {
    foreach($jids as $jid) {
        $connector->message(new \Kadet\Xmpp\Jid($jid), $message);
        usleep(50000); // Wait some time, after sending message
    }

    $connector->disconnect(); // close connection
});

// Launch processing loop
while($connection->connected) {
    $connection->process();
    usleep(1000); // CPU needs to rest
}