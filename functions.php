<?php
/**
 * Copyright (C) 2014, Some right reserved.
 * @author Kacper "Kadet" Donat <kadet1090@gmail.com>
 * @license http://creativecommons.org/licenses/by-sa/4.0/legalcode CC BY-SA
 *
 * Contact with author:
 * Xmpp: kadet@jid.pl
 * E-mail: kadet1090@gmail.com
 *
 * From Kadet with love.
 */

function getCompleteXml($xml) {
    for($i = strpos($xml, '<', 0), $n = 0; $i !== false; $i = strpos($xml, '<', $i + 1)) {
        if((strpos($xml, '/>', $i + 1) > strpos($xml, '<', $i + 1) && strpos($xml, '<', $i + 1) !== false) || strpos($xml, '/>', $i + 1) === false)
            $xml[$i + 1] == '/' ? $n++ : $n--;
        if($n == 0)
            return substr($xml, strpos($xml, '<', 0), strpos($xml, '<', $i + 1) !== false ? strpos($xml, '<', $i + 1) : strlen($xml));;
    }
    return false;
}