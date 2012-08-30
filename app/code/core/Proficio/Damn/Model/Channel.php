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
 * @version  SVN: $Id: Channel.php 38 2010-03-13 19:18:23Z abrault $
 */

// {{{ Proficio_Damn_Model_Channel
/**
 * @category Proficio
 * @package  Damn_Model_Channel
 * @author   Alan Brault <alan.brault@incruentatus.net>
 */
final class Proficio_Damn_Model_Channel
{   
    // {{{ getMemberGroup()
    public static function getMemberGroup($client_p, $channel_p)
    {
        foreach (Proficio_Object::getObject("dAmn/channels/$channel_p/groups") as $group_p => $children) {
            if (array_key_exists('clients', $children)) {
                if (array_key_exists($client_p, $children['clients'])) {
                    return $group_p;
                }
            }
        }
        
        return null;
    }
    // }}}
    
    // {{{ isRegistered()
    public static function isRegistered($channel_p, $type, $extra = null)
    {
        switch ($type){
            case 'channel':
                $channels = Proficio_Object::getObject('dAmn/channels');
                
                if (!is_null($channels)) {
                    if (array_key_exists($channel_p, $channels)) return true;
                }
                break;
            case 'client':
                if ($extra === null || !is_array($extra)) return false;
                
                $group_p = $extra['group'];
                $clients = Proficio_Object::getObject("dAmn/channels/$channel_p/groups/$group_p");
                
                if (!is_null($clients)) {
                    if (array_key_exists($extra['username'], $clients)) return true;
                }
                break;
            case 'group':
                if ($extra === null) return false;
                
                $groups = Proficio_Object::getObject("dAmn/channels/$channel_p/groups");
                
                if (!is_null($groups)) {
                    if (array_key_exists($extra, $groups)) return true;
                }
                break;
            default: break;
        }
        
        return false;
    }
    // }}}
    
    // {{{ moveClients()
    public static function moveClients($channel_p, $old_group_p, $group_p)
    {
        if (self::isRegistered($channel_p, 'channel')) {
            if (self::isRegistered($channel_p, 'group', $old_group_p) && self::isRegistered($channel_p, 'group', $group_p)) {
                Proficio_Object::moveObjectChildren("dAmn/channels/$channel_p/groups/$old_group_p",
                                                    "dAmn/channels/$channel_p/groups/$group_p", 'clients', true);
            }
        }
    }   
    // }}}
    
    // {{{ register()
    public static function register($channel_p)
    {
        if (self::isRegistered($channel_p, 'channel')) return false;

        Proficio_Object::addObject("dAmn/channels/$channel_p", array());
    }
    // }}}
    
    // {{{ registerAttribute()
    public static function registerAttribute($channel_p, $attribute, $value)
    {
        if (self::isRegistered($channel_p, 'channel')) {
            switch ($attribute) {
                case 'title':
                    Proficio_Object::addObject("dAmn/channels/$channel_p/title", $value);
                    break;
                case 'topic':
                    Proficio_Object::addObject("dAmn/channels/$channel_p/topic", $value);
                    break;
                default: break;
            }
        }
    }
    // }}}
        
    // {{{ registerMembers()
    public static function registerClient($channel_p, $client_p, $type = 'property')
    {
        if (self::isRegistered($channel_p, 'channel')) {
            if ($type === 'property') {
                foreach ($client_p as $client) {
                    $_username  = $client['param'];
                    $group_p  = $client['args']['pc'];

                    if (!(self::isRegistered($channel_p, 'group', $group_p))) continue;
                
                    if ($_username === Proficio_Damn_Model_Client::$username) continue;
                
                    $parameters = array('username' => $_username, 'group' => $group_p);
                    if (self::isRegistered($channel_p, 'client', $parameters)) continue;
                
                    Proficio_Object::addObject("dAmn/channels/$channel_p/groups/$group_p/clients/$_username", array());
                }
            }
            
            if ($type === 'join') {
                $_username  = $client_p['param'];
                $group_p  = str_replace('pc=', '', $client_p['cmd']);
                                
                if (!(self::isRegistered($channel_p, 'group', $group_p))) continue;
                
                if ($_username === Proficio_Damn_Model_Client::$username) continue;
                
                $parameters = array('username' => $_username, 'group' => $group_p);
                if (self::isRegistered($channel_p, 'client', $parameters)) continue;
                
                Proficio_Object::addObject("dAmn/channels/$channel_p/groups/$group_p/clients/$_username", array());
            }
        }
    }
    // }}}

    // {{{ registerGroup()
    public static function registerGroup($channel_p, $group_p)
    {
        if (self::isRegistered($channel_p, 'channel')) {
            if (self::isRegistered($channel_p, 'group', $group_p)) return false;
            
            Proficio_Object::addObject("dAmn/channels/$channel_p/groups/$group_p/clients", array());
        }
    }
    // }}}
    
    // {{{ registerGroupPermissions()
    public static function registerGroupPermissions($channel_p, $group_p, $permissions, $update = false)
    {
        if (self::isRegistered($channel_p, 'channel')) {
            if (self::isRegistered($channel_p, 'group', $group_p)) {
                if ($update === true) {
                   Proficio_Object::addObject("dAmn/channels/$channel_p/groups/$group_p/permissions", $permissions); 
                }
                else {
                    if (is_null(Proficio_Object::getObject("damn/channels/$channel_p/groups/$group_p/permissions"))) {
                        Proficio_Object::addObject("dAmn/channels/$channel_p/groups/$group_p/permissions", $permissions);
                    }
                }
            }
        }
    }
    // }}}
    
    // {{{ renameGroup()
    public static function renameGroup($channel_p, $old_group_p, $group_p)
    {
        if (self::isRegistered($channel_p, 'channel')) {
            if (self::isRegistered($channel_p, 'group', $old_group_p) && self::isRegistered($channel_p, 'group', $group_p)) {
                Proficio_Object::renameObjectKey("dAmn/channels/$channel_p/groups/$old_group_p",
                                                 "dAmn/channels/$channel_p/groups/$group_p");
            }
        }
    }
    // }}}
    
    // {{{ unregister()
    public static function unregister($channel_p)
    {
        if (self::isRegistered($channel_p, 'channel')) {
            Proficio_Object::destroyObject("dAmn/channels/$channel_p");
        }
    }
    // }}}
    
    // {{{ unregisterClient()
    public static function unregisterClient($channel_p, $client_p)
    {
        if (self::isRegistered($channel_p, 'channel')) {
            if (self::getMemberGroup($client_p, $channel_p) !== null) {
                $group_p = self::getMemberGroup($client_p, $channel_p);

                Proficio_Object::destroyObject("dAmn/channels/$channel_p/groups/$group_p/clients/$client_p");
            }            
        }
    }
    // }}}
    
    // {{{ unregisterGroup()
    public static function unregisterGroup($channel_p, $group_p)
    {
        if (self::isRegistered($channel_p, 'channel')) {
            Proficio_Object::destroyObject("dAmn/channels/$channel_p/groups/$group_p");
        }
    }
    // }}}
    
    public static function debug()
    {
        return print_r(Proficio_Object::getObject());
    }
    
}
// }}}