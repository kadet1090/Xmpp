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

namespace Kadet\Xmpp\Xml;

final class XmlArray extends \ArrayObject {
    private $_tag;

    function __construct($tag)
    {
        $this->_tag = $tag;
    }

    public function offsetSet($index, $newval)
    {
        if(!($newval instanceof XmlBranch)) {
            $branch = isset($this[$index]) ? $this[$index] : new XmlBranch($this->_tag);
            $branch->setContent((string)$newval);
            $newval = $branch;
        }

        parent::offsetSet($index, $newval);
    }

    public function __call($name, $arguments)
    {
        call_user_func_array([$this[0], $name], $arguments);
    }

    public function __get($name)
    {
        return isset($this[0]) ? $this[0]->$name : null;
    }

    /*public function __set($name, $value)
    {
        if(isset($this[0]))
            $this[0]->$name = $value;
    }*/

    public function __isset($name)
    {
        return isset($this[0]->$name);
    }

    public function __unset($name)
    {
        if(isset($this[0]))
            unset($this[0]->$name);
    }
}