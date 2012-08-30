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
 * @version  SVN: $Id: App.php 37 2010-02-27 17:07:35Z abrault $
 */

// {{{ Proficio_Core_Model_App
/**
 * Core Application
 * 
 * This is the main core application model for Proficio that is used during the
 * bootstrap process for the application. It will check various conditions of
 * the environment to ensure operability, initialize the dAmn client model
 * and then dispatch and fork to execute.
 *
 * @category Proficio
 * @package  Core_Model_App
 * @author   Alan Brault <alan.brault@incruentatus.net>
 */
final class Proficio_Core_Model_App
{
    // {{{ properties
    /**
     * Cache directory for the application
     *
     * @var string Caching directory
     */
    private static $_cacheDir = null;
    
    /**
     * Configuration object for the application
     *
     * @var object Configuration registry
     */
    private static $_config = null;
    // }}}
    
    // {{{ Proficio_Core_Model_App()
    /**
     * Run several checks and balances within the application model and store
     * various options and configuration data.
     *
     * @throws Exception Miscellaneous error depending on what failed checks and balances
     * @uses   Proficio_Core_Model_Config::getOptions() Fetch configuration options
     * @uses   Proficio_Core_Model_Config::getConfig() Fetch configuration registry
     */
    function __construct()
    {
        if (version_compare(phpversion(), '5.2.7', '<')) {
            throw new Exception('Proficio: PHP version 5.2.7 or greater is required.');
        }

        if ((Zend_Version::compareVersion('1.10.2')) !== 0) {
            throw new Exception('Proficio: ZendFramework version 1.10.2 must be present and in use and no other version.');
        }

        if (!extension_loaded('Zend Debugger')) {
            if (strtolower(substr(PHP_SAPI, 0, 3) !== 'cli')) {
                throw new Exception('Proficio: Must only be executed in CLI SAPI mode.');
            }
        }

        if ((bool)ini_get('magic_quotes_gpc')) {
            throw new Exception('Proficio: PHP magic_quotes_gpc is deprecated and should not be loaded in CLI SAPI mode.');
        }
        
        if ((bool)ini_get('register_globals')) {
            throw new Exception('Proficio: PHP register_globals is deprecated and should not be loaded in CLI SAPI mode.');
        }
        
        if ((bool)ini_get('register_long_arrays')) {
            throw new Exception('Proficio: PHP register_long_arrays is deprecated and should not be loaded in CLI SAPI mode.');
        }
        
        if ((bool)ini_get('safe_mode')) {
            throw new Exception('Proficio: PHP safe_mode is deprecated and should not be loaded in CLI SAPI mode.');
        }

        if (extension_loaded('apc')) {
            throw new Exception('Proficio: APC extension should not be loaded in CLI SAPI mode.');
        }

        if (extension_loaded('xcache')) {
            throw new Exception('Proficio: XCache extension should not be loaded in CLI SAPI mode.');
        }

        if (extension_loaded('suhosin')) {
            throw new Exception('Proficio: Suhosin extension should not be loaded in CLI SAPI mode.');
        }

        self::$_cacheDir = Proficio_Core_Model_Config::getOptions()->getCacheDir();
        self::$_config   = Proficio_Core_Model_Config::getConfig();
     
        #Proficio_Core_Model_Modules::moduleLoad('Core');
    }
    // }}}
    
    // {{{ dispatch()
    /**
     * Initiate a dispatch by loading up the client model and running
     * until a disconnection or reconnect if necessary.
     *
     * @uses   Proficio_Damn_Model_Client::connect() dAmn connection initializer
     * @uses   Proficio_Damn_Model_Client::login() dAmn login sequence
     * @uses   Proficio_Damn_Model_Client::autoJoin() dAmn auto join sequence
     * @uses   Proficio_Damn_Model_Client::read() dAmn packet reading sequence
     * @uses   Proficio_Damn_Model_Client::disconnect() dAmn disconnect sequence
     */
    public function dispatch()
    {
        Proficio_Object::addObject('dAmn', array());
               
        Proficio_Damn_Model_Client::connect();
        Proficio_Damn_Model_Client::login();
        Proficio_Damn_Model_Client::autoJoin();
        Proficio_Damn_Model_Client::listen();
        Proficio_Damn_Model_Client::disconnect();
        
        Proficio::removeLockFile();
        exit(0);
    }
    // }}}

    // {{{ init()
    /**
     * Authenticate with deviantART and cache the credentials to save the login
     * as well as setting the timezone.
     *
     * @throws Exception Unable to save cached token; unable to authenticate; unable to load token
     * @uses   Proficio_Core_Model_Client::authenticate() dAmn authentication sequence
     * @uses   Proficio_Damn_Model_Client::$cookie dAmn authentication cookie
     * @uses   Proficio_Damn_Model_Client::$config Configuration registry
     */
    public function init()
    {
        @date_default_timezone_set(self::$_config->base->locale->timezone);

        if (!(@file_exists(self::$_cacheDir . DS . 'token.dat'))) {
            $token = Proficio_Damn_Model_Client::authenticate(self::$_config->base->damn->auth->username,
                                                              self::$_config->base->damn->auth->password);

            if ($token !== false) {
                Proficio_Damn_Model_Client::$cookie = $token;
                Proficio_Damn_Model_Client::$config = self::$_config;

                $store_token = @serialize($token);
                if (!(@file_put_contents(self::$_cacheDir . DS . 'token.dat', $store_token, LOCK_EX)))
                    throw new Exception('Proficio: Unable to save cached token to: ' . self::$_cacheDir);
            }
            else {
                throw new Exception('Proficio: Unable to authenticate with deviantART');
            }
        }
        else {
            $token = @file_get_contents(self::$_cacheDir . DS . 'token.dat');

            if (empty($token)) {
                throw new Exception('Proficio: Unable to retrieve token from: ' . self::$_cacheDir);
            }
            
            $retrieved_token = @unserialize($token);
            
            Proficio_Damn_Model_Client::$cookie = $retrieved_token;
            Proficio_Damn_Model_Client::$config = self::$_config;
        }
    }
    // }}}
}
// }}}