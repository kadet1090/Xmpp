<?php
namespace Kadet\Xmpp;

class Jid
{
    /**
     * Users name
     * @var string
     */
    public $name;

    /**
     * Users server
     * @var string
     */
    public $server;

    /**
     * Users resource
     * @var string|null
     */
    public $resource;

    /**
     * @param string $name Login or jid string.
     * @param string $resource
     * @param string|null $server
     */
    public function __construct($name, $server = null, $resource = null)
    {
        if (preg_match('#([^@\/\"\'\s\&\:><]+)\@([a-z_\-\.]*[a-z]{2,3})(\/[^@\/\&\:><]*)?#si', $name, $matches)) {
            $this->name = $matches[1];
            $this->resource = isset($matches[3]) ? substr($matches[3], 1) : null;
            $this->server = $matches[2];
        } else {
            $this->name = $name;
            $this->resource = $resource;
            $this->server = $server;
        }
    }

    /**
     * Gets jid as a string.
     * @return string
     */
    public function __toString()
    {
        return "{$this->name}@{$this->server}" . (!empty($this->resource) ? "/{$this->resource}" : '');
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
            '/^[^@\/\\\"\'\s\&\:><]+@(conference|chat|irc)\.[a-z\_\-\.]*\.[a-z]{2,3}\/[^@\/\&\:><]*?$/',
            $this->__toString()
        );
    }

    static public function isJid($jid)
    {
        return preg_match('#([^@\/\"\'\s\&\:><]+)\@([a-z_\-\.]*[a-z]{2,3})(\/[^@\/\&\:><]*)?#si', $jid);
    }
}