<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * dAmn (deviantART Message Network) Independant Interface
 *
 * PHP version 5
 *
 * Copyright 2009-2010 Alan Brault
 * 
 * Licensed under the Apache License, Apache 2.0 (the "License");
 * you may not use this file except in complaince with the License.
 * You may obtain a copy of the License at
 * 
 *      http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for specific language governing permissions and
 * limitations under the License.
 *
 * @category Proficio
 * @package  Damn
 * @author   Alan Brault <alan.brault@incruentatus.net>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @version  SVN: $Id: Process.php 38 2010-03-13 19:18:23Z abrault $
 */

// {{{ Proficio_Damn_Controller_Process
/**
 * dAmn (deviantART Message Network) Client Processor
 *
 * @category Proficio
 * @package  Damn_Controller_Process
 * @author   Alan Brault <alan.brault@incruentatus.net>
 */
final class Proficio_Damn_Controller_Process
{
    // {{{ _message()
    private static function _message($message, $source_p, $channel_p)
    {
        if (!empty($message) && !empty($source_p) && !empty($channel_p)) {
            /* ignored users */

            if ($source_p !== Proficio_Damn_Model_Client::$username) {
                $trigger = Proficio_Core_Model_Config::getConfig()->base->damn->trigger;
                $command = strtolower(trim(substr(strip_tags($message), (strlen(Proficio_Damn_Model_Client::$username) + 1))));
                
                switch ($command) {
                    case 'trigcheck':
                        Proficio_Damn_Model_Client::say("$source_p, my currently configured trigger is: $trigger", 
                                                        $channel_p);
                        break;
                    case 'debug':
                        Proficio_Damn_Model_Client::say(Proficio_Damn_Model_Channel::debug(), $channel_p);
                        break;
                    default:
                        if (substr($command, 0, strlen($trigger)) === $trigger) {
                            $cmd = str_replace($trigger, '', $command);
                            
                            if (!Proficio_Core_Model_Modules::isCommand($cmd)) {
                                Proficio_Damn_Model_Client::say("$source_p, command '$cmd' is unknown.", $channel_p);
                            }
                            else {
                                $callback = Proficio_Core_Model_Modules::getCallback($cmd);
                                
                                if ($callback !== false) {
                                    call_user_func_array(array($callback, $cmd), array($source_p, $channel_p));
                                }
                                else {
                                    Proficio_Damn_Model_Client::say("$source_p, command '$cmd' is unknown.", $channel_p);
                                }
                            }
                        }
                        break;
                }
            }
        }
    }
    // }}}

    // {{{ _packet()
    // credits: miksago; bentomlin
    private static function _packet($data)
    {
        $data   = Proficio_Damn_Helper_Parser::parseTablumps($data);
        $packet = array ('cmd'   => null,
                         'param' => null,
                         'args'  => array(),
                         'body'  => null,
                         'raw'   => $data);
        
        
        $data = explode("\n\n", $data);
        $head = array_shift($data);
        
        $packet['body'] = implode("\n\n", $data);
        
        $data = explode("\n", $head);
        
        for ($i = 0, $icount = count($data); $i < $icount; ++$i) {
            if ($i === 0) {
                $_raw = explode(' ', $data[$i]);                
                $packet['cmd']   = $_raw[0];
                $packet['param'] = (isset($_raw[1])) ? $_raw[1] : null;
            }
            else if (strstr($data[$i], '=')) {
                $property = substr($data[$i], 0, strpos($data[$i], '='));
                $entity   = substr($data[$i], strpos($data[$i], '=') + 1);
                
                $packet['args'][$property] = $entity;
                
                if (strstr($packet['body'], "\n\n")) {
                    $subpacket = explode("\n\n", $packet['body']);

                    foreach ($subpacket as $_subpacket) {
                        if (empty($_subpacket)) continue;
                        
                        $packet['subpacket'][] = self::_packet($_subpacket);
                    }
                }
            }
        }
        
        return $packet;
    }
    // }}}
    
    // {{{ stream
    public static function stream($stream)
    {
        $packet = self::_packet($stream);
                
        switch ($packet['cmd']) {
            case 'dAmnServer':
                Proficio::log('Connected to dAmnServer ' . trim($packet['param']), 6);
                break;
            case 'disconnect':
                Proficio::log('Disconnected (' . trim($packet['args']['e']) . ')');
                break;
            case 'error':
                Proficio::log('Received unknown packet: ' . trim($packet['args']['e']), 7);
                break;
            case 'join':
                $channel_p = Proficio_Damn_Helper_Parser::channel($packet['param']);
                if (empty($channel_p)) return;
                
                if ($packet['args']['e'] === 'ok') {
                    Proficio_Damn_Model_Channel::register($channel_p);
                    Proficio::log('Joined #' . $channel_p, 5);
                }
                else {
                    Proficio::log('Unable to join #' . $channel_p . '; received error: ' . $packet['args']['e'], 2);
                }
                break;
            case 'login':
                switch ($packet['args']['e']) {
                    case 'ok':
                        Proficio::log('Authenticated as ' . $packet['param'], 6);
                        break;
                    case 'authentication failed':
                        Proficio::log('Authentication as ' . $packet['param'] . ' failed.', 2);
                        Proficio_Damn_Model_Client::$connected = false;
                        break;
                    default:
                        Proficio::log('An unexpected error occurred authenticating as ' . $packet['param'] .
                                      'Error: ' . $packet['args']['e'], 3);
                        Proficio_Damn_Model_Client::$connected = false;
                        break;
                }
                break;
            case 'part':
                $channel_p = Proficio_Damn_Helper_Parser::channel($packet['param']);
                if (empty($channel_p)) return;
                
                if ($packet['args']['e'] === 'ok') {
                    Proficio_Damn_Model_Channel::unregister($channel_p);
                    Proficio::log('Parted #' . $channel_p, 5);
                }
                else {
                    Proficio::log('Unable to part #' . $channel_p . '; received error: ' . $packet['args']['e'], 2);
                }
                break;
            case 'ping':
                Proficio_Damn_Model_Client::pong();
                Proficio::log('Ping? Pong!', 6);
                break;
            case 'property':
                $channel_p = Proficio_Damn_Helper_Parser::channel($packet['param']);
                if (empty($channel_p)) return;
                
                switch ($packet['args']['p']) {
                    case 'members':
                        Proficio_Damn_Model_Channel::registerClient($channel_p, $packet['subpacket']);
                        break;
                    case 'privclasses':
                        $_privclasses = explode("\n", $packet['body']);

                        Proficio_Damn_Model_Client::admin('show', 'privclass', null, $channel_p);
                        
                        foreach ($_privclasses as $_privclass) {
                            if (empty($_privclass)) continue;
                                                                                    
                            $privclass = explode(':', $_privclass);
                            Proficio_Damn_Model_Channel::registerGroup($channel_p, $privclass[1]);
                        }
                        break;
                    case 'title':
                    case 'topic':
                        $type = $packet['args']['p'];
                        
                        $attribute['message'] = trim(strip_tags($packet['body']));
                        $attribute['author']  = $packet['args']['by'];
                        $attribute['date']    = gmdate('D d M Y H:i:s \G\M\T', $packet['args']['ts']);
                        
                        Proficio_Damn_Model_Channel::registerAttribute($channel_p, $type, $attribute);
                        
                        Proficio::log(ucwords($type) . ': ' . $attribute['message'], 6, "$channel_p.log");
                        Proficio::log('** ' . ucwords($type) . ' changed by ' . $attribute['author'] . ' on ' 
                                      . $attribute['date'], 5, "$channel_p.log");
                        break;
                        break;
                    default: break;
                }
                break;
            case 'recv':
                $channel_p = Proficio_Damn_Helper_Parser::channel($packet['param']);
                if (empty($channel_p)) return;

                $event = self::_packet($packet['body']);
                
                switch ($event['cmd']) {
                    case 'admin':
                        switch ($event['param']) {
                            case 'create':
                                $group_p  = $event['args']['name'];
                                $source_p = $event['args']['by'];
                                
                                Proficio_Damn_Model_Channel::registerGroup($channel_p, $group_p);
                                Proficio::log("** Privilege class '$group_p' has been created by $source_p",
                                              5, "$channel_p.log");
                                break;
                            case 'move':
                                $old_group_p = $event['args']['prev'];
                                $group_p     = $event['args']['name'];
                                $source_p    = $event['args']['by'];
                                
                                Proficio_Damn_Model_Channel::moveClients($channel_p, $old_group_p, $group_p);
                                Proficio::log("** Members of '$old_group_p' have been made '$group_p' by $source_p",
                                              5, "$channel_p.log");
                                break;
                            case 'remove':
                                $group_p  = $event['args']['name'];
                                $source_p = $event['args']['by'];
                                
                                Proficio_Damn_Model_Channel::unregisterGroup($channel_p, $group_p);
                                Proficio::log("** Privilege class '$group_p' has been removed by $source_p", 
                                              5, "$channel_p.log");
                                break;
                            case 'rename':
                                $old_group_p = $event['args']['prev'];
                                $group_p     = $event['args']['name'];
                                $source_p    = $event['args']['by'];
                                
                                Proficio_Damn_Model_Channel::renameGroup($channel_p, $old_group_p, $group_p);
                                Proficio::log("** Privilege class '$old_group_p' has been renamed to '$group_p'" .
                                              " by $source_p", 5, "$channel_p.log");
                                break;
                            case 'show':
                                $permissions = self::_packet($event['body']);
                                $permissions = explode("\n", $permissions['raw']);
                                
                                foreach ($permissions as $permission) {
                                    $_permissions = trim(substr($permission, strrpos($permission, ' order='), strlen($permission)));
                                    $_group_p     = trim(substr($permission, 0, strrpos($permission, ' order=')));
                                    
                                    Proficio_Damn_Model_Channel::registerGroupPermissions($channel_p, $_group_p, $_permissions);
                                }
                                
                                break;
                            case 'update':
                                $group_p  = $event['args']['name'];
                                $source_p = $event['args']['by'];
                                $changes  = $event['args']['privs'];
                                
                                Proficio::log("** Privilege class '$group_p' has been updated by $source_p with: " .
                                              "$changes", 5, "$channel_p.log");
                                break;
                            default: break;
                        }
                        break;
                    case 'action':
                        $source_p = $event['args']['from'];
                        $message  = $event['body'];
                        
                        if ($source_p !== Proficio_Damn_Model_Client::$username) {
                            Proficio::log("* $source_p $message", 6, "$channel_p.log");
                        }
                        break;
                    case 'join':
                        $source_p          = self::_packet($event['body']);
                        $source_p['param'] = $event['param'];
                                               
                        Proficio_Damn_Model_Channel::registerClient($channel_p, $source_p, 'join');
                        Proficio::log('** ' . $source_p['param'] . ' has joined', 5, "$channel_p.log");
                        break;
                    case 'kicked':
                        $source_p = $event['args']['by'];
                        $target_p = $event['param'];
                        $reason   = $event['body'];
                        
                        Proficio_Damn_Model_Channel::unregisterClient($channel_p, $target_p);
                        Proficio::log("** $target_p was kicked by $source_p $reason", 5, "$channel_p.log");
                        break;
                    case 'part':
                        $source_p = $event['param'];
                        $reason   = (isset($event['args']['r']) ? $event['args']['r'] : '');

                        Proficio_Damn_Model_Channel::unregisterClient($channel_p, $source_p);
                        Proficio::log("** $source_p has left $reason", 5, "$channel_p.log");
                        break;
                    case 'privchg':
                        break;
                    case 'msg':
                        $source_p = $event['args']['from'];
                        $message  = $event['body'];
                        
                        if ($source_p !== Proficio_Damn_Model_Client::$username) {
                            Proficio::log("<$source_p> $message", 6, "$channel_p.log");
                        }
                        
                        self::_message($message, $source_p, $channel_p);
                        break;
                    default: break;
                }
                break;
            default: break;
        }
    }
    // }}}
}
// }}}