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

// Prepare client
$connection = new Kadet\Xmpp\XmppClient(
    new \Kadet\Xmpp\Jid('kadet@jid.pl/bosh'),
    'fuckyou1'
);

// Try to connect to server
$connection->connect() or die("Cannot connect to server.");

$connection->onReady->add(function(\Kadet\Xmpp\XmppClient $connection) {
    $jid = new \Kadet\Xmpp\Jid('kpe@conference.jabbi.pl');
    $room = $connection->join($jid, 'bot');

    $room->message('Hello.');
    // or
    $connection->message($jid, 'Hello.', 'groupchat');
});

// Handle message
$connection->onMessage->add(function(
    \Kadet\Xmpp\XmppClient $connection,
    \Kadet\Xmpp\Stanza\Message $message
) {
    if($message->from->isChannel()) {
        echo "Message from room ".$message->from->name.' by '.$message->from->resource.': '.$message->body;
    } else {
        echo "Message from ".$message->from.": ".$message->body.PHP_EOL;
    }
});

// Launch processing loop
while($connection->connected) {
    $connection->process();
    usleep(1000); // CPU needs to rest
}

$connection->disconnect(); // close connection