<?php
/**
 * Phergie
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://phergie.org/license
 *
 * @category  Phergie
 * @package   Phergie_Plugin_Quotes
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Quotes
 */

/**
 * Fetches quotes from various sources that all use the bash.org
 * and qdb.us URL format.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Quotes
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Quotes
 * @uses     Phergie_Plugin_Command pear.phergie.org
 */
class Phergie_Plugin_Quotes extends Phergie_Plugin_Abstract
{
    /**
     * Checks for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        $this->getPluginHandler()->getPlugin('Command');
    }

    /**
     * Handles a request for a quote by ID.
     *
     * <code>bash 12345</code>
     * <code>qdb 12345</code>
     *
     * Due to lack of real input validation, we get some interesting
     * features as a side effect:
     *
     * <code>qdb random</code>
     * <code>qdb latest</code>
     * <code>qdb search?q=keyword</code>
     *
     * @param string $id Qdb quote ID to retrieve
     *
     * @return void
     */
    public function onPrivmsg()
    {
        $msg = $this->plugins->message->getMessage();

        // Is the first word a command we recognize?
        if (!preg_match('/^\s*(bash|qdb)\s+(.*)\s*$/i', $msg, $matches)) {
            return;
        }

        list (, $service, $query) = $matches;

        switch ($service) {
        case 'bash':
            $reply = 'bash.org/?' . $query . ': ';
            $url = 'http://bash.org/?' . $query;
            break;
        case 'qdb':
            $reply = 'qdb.us/' . $query . ': ';
            $url = 'http://qdb.us/' . $query;
            break;
        }

        $data = @file_get_contents($url);

        if ($data === false) {
            $this->doPrivmsg($this->getEvent()->getSource(), $reply . 'not found');
            return;
        }

        switch ($service) {
        case 'bash':
            if (preg_match('/<p class="quote">.*?<b>#(\d+)<\/b>.*?<p class="qt">(.*?)<\/p>/si', $data, $matches)) {
                $id = $matches[1];
                $quote = html_entity_decode($matches[2]);
                $quote = implode(' / ', array_map('trim', explode('<br />', $quote)));
                $reply = 'bash.org/?' . $id . ': ' . $quote;
            }
            break;
        case 'qdb':
            if (preg_match('/<span class=qt id=qt(\S+)>(.*?)<\/span>/si', $data, $matches)) {
                $id = $matches[1];
                $quote = html_entity_decode($matches[2]);
                $quote = implode(' / ', array_map('trim', explode('<br />', $quote)));
                $reply = 'qdb.us/' . $id . ': ' . $quote;
            }
            break;
        }

        if (strlen($reply) > 400) {
            $reply = substr($reply, 0, 400) . ' ...';
        }

        if (strlen($reply)) {
            $this->doPrivmsg($this->getEvent()->getSource(), $reply);
        }
    }

    public function handleMsgonCommandQdb($id)
    {
        $data = @file_get_contents('http://qdb.us/' . $id);
        if ($data === false) {
            $this->doPrivmsg($this->getEvent()->getSource(), 'qdb.us/' . $id . ': not found');
        } elseif (preg_match('/<span class=qt id=qt(\S+)>(.*?)<\/span>/si', $data, $matches)) {
            $id = $matches[1];
            $quote = html_entity_decode($matches[2]);
            $quote = implode(' / ', array_map('trim', explode('<br />', $quote)));
            if (strlen($quote) > 400) {
                $quote = substr($quote, 0, 400) . ' ...';
            }
            $this->doPrivmsg($this->getEvent()->getSource(), 'qdb.us/' . $id . ': ' . $quote);
        }
    }
}
