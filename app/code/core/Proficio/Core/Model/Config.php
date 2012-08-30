<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Proficio: An advanced bot/client for dAmn
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
 * @package  Core
 * @author   Alan Brault <alan.brault@incruentatus.net>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @version  SVN: $Id: Config.php 37 2010-02-27 17:07:35Z abrault $
 */

// {{{ Proficio_Core_Model_Config
/**
 * Core Configuration Model
 * 
 * A drop-in replacement for Zend_Registry, this class is designed to hold all
 * configuration data for Proficio. It's an adaptation of the same model
 * used by Magento Commerce's framework that was well suited for the needs
 * of Proficio.
 *
 * @category Proficio
 * @package  Core_Model_Config
 * @author   Alan Brault <alan.brault@incruentatus.net>
 */
final class Proficio_Core_Model_Config
{
    // {{{ properties
    /**
     * Storage for application options.
     * 
     * @var array|object Application options
     */
    protected static $_options = null;
    // }}}
       
    // {{{ _isValidTimeZone
    /**
     * Validate timezone provided in the configuration
     *
     * @param  string $tzconfig Configured timezone locale
     * @return boolean true if valid; false if not
     */
    private static function _isValidTimeZone($tzconfig)
    {
        foreach (DateTimeZone::listAbbreviations() as $timezone) {
            foreach ($timezone as $tz) {
                if ($tz['timezone_id'] === $tzconfig) return true;
            }
        }
        
        return false;
    }
    // }}}
    
    // {{{ getConfig()
    /**
     * Get application configuration data.
     *
     * @return array Application registry
     * @uses   Proficio_Core_Model_Config::registry() Application registry
     */
    public static function getConfig()
    {
        return Proficio::registry('config');
    }
    // }}}
    
    // {{{ getOptions()
    /**
     * Get application options.
     * 
     * @return object Proficio_Core_Model_Config_Options
     * @uses   Proficio_Core_Model_Config_Options::_construct() Constructor for configuration options
     */
    public static function getOptions()
    {
        if (!(self::$_options instanceof Proficio_Core_Model_Config_Options)) {
            self::$_options = new Proficio_Core_Model_Config_Options();
        }
            
        return self::$_options;
    }
    // }}}
    
    // {{{ load()
    /**
     * Import the application configuration, sanitize and load into the registry.
     * 
     * @throws Exception Miscellaenous exception caused by configuration error
     * @uses Proficio::log() Logging facility
     * @uses Proficio_Core_Model_Config::register() Register a resource in application registry
     */
    public static function load()
    {
        $config = new Zend_Config_Xml(self::getOptions()->getEtcDir() . DS . 'config.xml');
        
        if ($config->base->damn->auth->username === '{{username}}' || empty($config->base->damn->auth->username)) {
            throw new Exception('Proficio->Config: You must configure a dAmn username.');
        }

        if ($config->base->damn->auth->password === '{{password}}' || empty($config->base->damn->auth->password)) {
            throw new Exception('Proficio->Config: You must configure a dAmn password.');
        }

        if ($config->base->damn->trigger === '{{trigger}}' || empty($config->base->damn->trigger)) {
            throw new Exception('Proficio->Config: You must configure a dAmn bot trigger.');
        }

        if ($config->base->locale->timezone === '{{timezone}}' || empty($config->base->locale->timezone)) {
            throw new Exception('Proficio->Config: You must configure a timezone.');
        }

        if (self::_isValidTimeZone($config->base->locale->timezone) === false) {
            Proficio::log('Invalid timezone identifier "' . $config->base->locale->timezone . 
                          '" was passed. Defaulting to UTC.', 4);
            $config->base->locale->timezone = 'UTC';
        }
               
        foreach ($config->base->damn->channels as $channels) {
            if (count($channels) > 1) {
                foreach ($channels as $channel) {
                    if ($channel === '{{channel}}' || empty($channel)) {
                        throw New Exception('Proficio->Config: You must specify a channel!');
                    }
                }
            }
            else {
                if ($channels === '{{channel}}' || empty($channels)) {
                    throw new Exception('Proficio->Config: You must specify a channel!');
                }
            }
        }

        Proficio::register('config', $config);
    }
    // }}}


}
// }}}