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
    new \Kadet\Xmpp\Jid('your@jid.com/bosh'),
    'password',
    new \Kadet\Xmpp\Connector\BoshConnector('https://jid.com:5281/http-bind')
);

// Try to connect to server
$connection->connect() or die("Cannot connect to server.");

// When connection to server is established
$connection->onReady->add(function(\Kadet\Xmpp\XmppClient $connector) {
    $connector->presence("available", "I'll resend your message :)"); // set presence to available with status "I'll resend your message :)"
});

// Handle message
$connection->onMessage->add(function(
    \Kadet\Xmpp\XmppClient $connection,
    \Kadet\Xmpp\Stanza\Message $message
) {
    echo "Message from ".$message->from.": ".$message->body.PHP_EOL;

    $message->reply($message->body);
});

// Launch processing loop
while($connection->connected) {
    $connection->process();
    usleep(1000); // CPU needs to rest
}

$connection->disconnect(); // close connection