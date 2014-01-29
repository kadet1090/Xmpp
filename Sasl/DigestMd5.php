<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package 
 * @license WTFPL
 */

namespace Kadet\Xmpp\Sasl;

class DigestMd5 extends Mechanism
{
    private $_realm;
    private $_nonce;
    private $_qop;
    private $_charset;
    private $_algorithm;

    public function challenge($packet)
    {
        if (!$this->_parseChallenge(base64_decode($packet->xml)))
            return '';

        $cnonce = $this->_generateCnonce();

        $x   = pack('H32', md5("{$this->jid->name}:{$this->_realm}:{$this->password}"));
        $ha1 = md5("$x:{$this->_nonce}:$cnonce");
        $ha2 = md5("AUTHENTICATE:xmpp/{$this->jid->server}");
        $kd  = md5("$ha1:{$this->_nonce}:00000001:$cnonce:{$this->_qop}:$ha2");
        $response = "username=\"{$this->jid->name}\"".(!empty($this->_realm) ? ",realm=\"{$this->_realm}\"" : "").",nonce=\"{$this->_nonce}\",cnonce=\"$cnonce\",nc=00000001,qop={$this->_qop},digest-uri=\"xmpp/{$this->jid->server}\"".(!empty($this->_charset) ? ",charset=\"{$this->_charset}\"" : "").",response=$kd";

        return $response;
    }

    private function _parseChallenge($challenge) {
        if(preg_match('/rspauth=(.*?)/si', $challenge))
            return false;

        if(preg_match('/realm="(.*?)"/si', $challenge, $matches))
            $this->_realm     = $matches[1];
        if(preg_match('/nonce="(.*?)"/si', $challenge, $matches))
            $this->_nonce     = $matches[1];
        if(preg_match('/qop="(.*?)"/si', $challenge, $matches))
            $this->_qop       = $matches[1];
        if(preg_match('/charset=(.*?)/si', $challenge, $matches))
            $this->_charset   = $matches[1];
        if(preg_match('/algorithm=(.*?)/si', $challenge, $matches))
            $this->_algorithm = $matches[1];

        return true;
    }

    private function _generateCnonce() {
        $cnonce = "";
        for($i = 0; $i < 32; $i++) {
            $hex = dechex(rand(0, 15));
            $cnonce .= $hex;
        }

        return $cnonce;
    }

    public function auth()
    {
        return '=';
    }
}