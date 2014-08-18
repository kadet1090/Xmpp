<?php
namespace Kadet\Xmpp;

class Jid
{
    /**
     * Users name
     * @var string
     */
    public $name = null;

    /**
     * Users server
     * @var string
     */
    public $server = null;

    /**
     * Users resource
     * @var string|null
     */
    public $resource = null;

    /**
     * @param string $name
     * @param string $resource
     * @param string|null $server
     */
    public function __construct($name, $server = null, $resource = null)
    {
        if($server === null) {
            preg_match(
                '#(?:(?P<name>[^@\/\"\'\s\&\:><]+)@)?(?P<server>[a-z_\-\.]*)(?:\/(?P<resource>[^\&\:><]*))?#si',
                $name,
                $matches
            );

            $this->name = isset($matches['name']) ? $matches['name'] : null;
            $this->server = isset($matches['server']) ? $matches['server'] : null;
            $this->resource = isset($matches['resource']) ? $matches['resource'] : null;
        } else {
            $this->name = $name;
            $this->server = $server;
            $this->resource = $resource;
        }
    }

    /**
     * Gets jid as a string.
     * @return string
     */
    public function __toString()
    {
        return (!empty($this->name) ? "{$this->name}@" : '') . $this->server . (!empty($this->resource) ? "/{$this->resource}" : '');
    }

    /**
     * Gets bare jid.
     * @return string
     */
    public function bare()
    {
        return "{$this->name}@{$this->server}";
    }

    public function isChannel()
    {
        return preg_match(
            '/^[^@\/\\\"\'\s\&\:><]+@(conference|chat|irc)\.[a-z\_\-\.]*\.[a-z]{2,3}$/',
            $this->__toString()
        ) && empty($this->resource);
    }

    public function fromChannel()
    {
        return preg_match(
            '/^[^@\/\\\"\'\s\&\:><]+@(conference|chat|irc)\.[a-z\_\-\.]*\.[a-z]{2,3}\/[^@\&\:><]*?$/',
            $this->__toString()
        );
    }

    static public function isJid($jid)
    {
        if($jid instanceof Jid) return true;
        return preg_match('#(?:(?P<name>[^@\/\"\'\s\&\:><]+)@)?(?P<server>[a-z_\-\.]*)(?:\/(?P<resource>[^\&\:><]*))?#si', $jid);
    }
}