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
 * @version  SVN: $Id: Proficio.php 37 2010-02-27 17:07:35Z abrault $
 */

/**
 * Platform specific directory separator; necessary for file system operations.
 */
define('DS', DIRECTORY_SEPARATOR);

/**
 * Platform specific path separator; necessary for file system operations.
 */
define('PS', PATH_SEPARATOR);

/**
 * Base path define; necessary for ensuring proper loading of all necessary.
 * files in the project.
 */
define('BP', dirname(dirname(__FILE__)));

/**
 * Set error reporting to report all errors including E_STRICT compliance.
 */
@error_reporting(E_ALL | E_STRICT);

/**
 * Manipulate the include_path to include all necessary paths for Proficio
 * and 3rd party libraries.
 */
$paths[] = BP . DS . 'app' . DS . 'code' . DS . 'local';
$paths[] = BP . DS . 'app' . DS . 'code' . DS . 'community';
$paths[] = BP . DS . 'app' . DS . 'code' . DS . 'core';
$paths[] = BP . DS . 'lib';

@set_include_path(implode(PS, $paths) . PS . get_include_path());

/**
 * Initialize Zend Autoloading and Namespace instantiation
 */
require_once 'Zend/Loader/Autoloader.php';
$loader = Zend_Loader_Autoloader::getInstance();
$loader->registerNamespace('Proficio_');

// {{{ Proficio
/**
 * Proficio: An advanced bot/client for dAmn
 * 
 * This is the main toolchain for Proficio. It handles
 * all the bootstrapping and initialization of the
 * application.
 * 
 * @category Proficio
 * @package  Core
 * @author   Alan Brault <alan.brault@incruentatus.net>
 */
final class Proficio
{
    // {{{ properties
    /**
     * Static object for the application initializer
     *
     * @var object Application runtime
     */
    static private $_app = null;

    /**
     * Storage for application registry.
     *
     * @var array|object Application registry
     */
    protected static $_registry = null;
    // }}}
    
    // {{{ _app()
    /**
     * Load configuration data and initialize the Proficio core application model.
     *
     * @return mixed resource or null
     * @uses   Proficio::_setappRoot() Set the application path
     * @uses   Proficio_Core_Model_Config::load() Loads the configuration
     * @uses   Proficio_Core_Model_App::init() Initialize the application
     */
    private static function _app()
    {        
        if (!(self::$_app instanceof Proficio_Core_Model_App)) {
            self::_setAppRoot();

            Proficio_Core_Model_Config::load();
            
            //self::_setLockFile();
            
            if (!(Proficio_Core_Model_Config::getConfig() instanceof Zend_Config_Xml)) {
                throw new Exception('Proficio: Configuration is not an instance of Zend_Config_Xml');
            }
            else {
                self::$_app = new Proficio_Core_Model_App();
                self::$_app->init();
            }
        }

        return self::$_app;
    }
    // }}}

    // {{{ _setAppRoot()
    /**
     * Set the main application path for Proficio and store it in the registry.
     *
     * @param  string $appRoot Application directory
     * @throws Exception Application directory is not readable by the current user
     * @uses   Proficio::getAppRoot()
     * @uses   Proficio::register() Register application directory
     */
    private static function _setAppRoot($appRoot = '')
    {
        if (self::getAppRoot()) return;

        if (empty($appRoot)) {
            $appRoot = dirname(__FILE__);
        }

        $appRoot = realpath($appRoot);

        if (is_dir($appRoot) && is_readable($appRoot)) {
            Proficio::register('appRoot', $appRoot);
        }
        else {
            throw new Exception ('Proficio: ' . $appRoot . ' is not a directory or not readable by current user.');
        }
    }
    // }}}
    
    // {{{ _setLockFile()
    /**
     * Set the main application lock file for Proficio to prevent multiple
     * instances of the application from running.
     * 
     * @throws Exception Prerequisites not loaded or lock file is present.
     * @uses   Proficio::getappRoot() 
     * @uses   Proficio_Core_Model_Config::getConfig()
     * @uses   Proficio_Core_Model_Config::getOptions()
     * @uses   Proficio_Core_Model_Config_Options::getLockDir()
     */
    private static function _setLockFile()
    {        
        if (!(Proficio_Core_Model_Config::getConfig() instanceof Zend_Config_Xml)) {
            throw new Exception('Proficio: The configuration must be loaded before a lock file can be created.');
        }

        $lockDir  = Proficio_Core_Model_Config::getOptions()->getLockDir();
        $lockFile = $lockDir . DS . 'proficio';
        
        if (!is_readable($lockDir) || !is_writable($lockDir) || !is_dir($lockDir)) {
            throw new Exception('Proficio: ' . $lockDir . 
                                ' is not a directory or not readable/writable by current user.');
        }
        
        if (file_exists($lockFile)) {
            throw new Exception('Proficio: The application is already running or a stale lock file is present.');
        }
        
        file_put_contents($lockFile, @getmypid(), LOCK_EX);
    }
    // }}}

    // {{{ getAppRoot()
    /**
     * Get main application path for Proficio
     *
     * @return string application root path
     * @uses   Proficio::registry() Fetch appRoot from registry
     */
    public static function getAppRoot()
    {
        return Proficio::registry('appRoot');
    }
    // }}}
    
    // {{{ log()
    /**
     * An adaptation of Mage::log() from Magento Commerce's framework, this function is designed to handle
     * multi-file logging which is perfect for Proficio's needs to log all channel activity. If no file
     * is specified it immediately defaults to system.log.
     *
     * @param   string $message
     * @param   integer $level 
     * @param   string $file
     * @throws  Exception Log directory is not readable or object is not an instance of Proficio_Log
     * @uses    Proficio_Core_Model_Config::getConfig() Fetch configuration registry
     * @uses    Proficio_Core_Model_Config::getOptions() Fetch configuration options
     * @uses    Proficio_Core_Model_Config_Options::getLogDir() Fetch logging directory
     * @uses    Proficio_Log::log() Logging facility
     */
    public static function log($message, $level = null, $file = null)
    {
        static $loggers = array();

        $file  = empty($file) ? 'system.log' : $file;
        $level = empty($level) ? Zend_Log::DEBUG : $level;

        try {
           if (!isset($loggers[$file])) {
               $logDir  = Proficio_Core_Model_Config::getOptions()->getLogDir();
               $logFile = $logDir . DS . $file;

               if (!is_readable($logDir) || !is_writable($logDir) || !is_dir($logDir)) {
                   throw new Exception('Proficio: ' . $logDir . 
                                       ' is not a directory or not readable/writable by current user.');
               }
               
               if (!file_exists($logFile)) {
                   @file_put_contents($logFile, '', LOCK_EX);
                   @chmod($logFile, 0644);
               }

               $loggers[$file] = new Proficio_Log(new Zend_Log_Writer_Stream($logFile));
           }

           if (is_array($message) || is_object($message)) $message = print_r($message, true);

           if ($loggers[$file] instanceof Proficio_Log) {
               $loggers[$file]->log($message, $level);
           }
           else {
               throw new Exception($loggers[$file] . ' is not and instance of Procifio_Log.');
           }
        }
        catch (Exception $e) {
            self::printException($e);
        }
    }
    // }}}

    // {{{ printException()
    /**
     * Print an entire backtrace stack of the exception should one be thrown
     * by the application for debugging and bug report filing.
     *
     * @param Exception $e The exception that has been thrown
     */
    public static function printException(Exception $e)
    {        
        if (Proficio_Core_Model_Config::getConfig()->base->developer === 'true' || 
            !(Proficio_Core_Model_Config::getConfig() instanceof Zend_Config_Xml)) {
                print $e->getMessage() . PHP_EOL . PHP_EOL;
                print $e->getTraceAsString();
            }
            else {
                $reportId   = uniqid(null, true);
                $reportFile = 'report-' . $reportId . '.log';
                
                $reportData = $e->getMessage() . PHP_EOL . PHP_EOL . $e->getTraceAsString();
                self::log($reportData, 7, $reportFile);
            }
           
        exit(1);
    }
    // }}}

    // {{{ removeLockFile()
    public static function removeLockFile()
    {
        if (!(self::$_app instanceof Proficio_Core_Model_App)) {
            throw new Exception('Proficio: The application is currently not running.');
        }
                
        if (!(Proficio_Core_Model_Config::getConfig() instanceof Zend_Config_Xml)) {
            throw new Exception('Proficio: The configuration must be loaded before a lock file can be created.');
        }
        
        $lockDir  = Proficio_Core_Model_Config::getOptions()->getLockDir();
        $lockFile = $lockDir . DS . 'proficio';
        
        if (!file_exists($lockFile)) return;
        
        @unlink($lockFile);
    }
    // }}}
    
    // {{{ register()
    /**
     * Register a resource within the application registry.
     *
     * @param string $index Registry resource
     * @param mixed $value Registry data
     */
    public static function register($index, $value)
    {
        if (isset(self::$_registry[$index])) return;
        self::$_registry[$index] = $value;
    }
    // }}}

    // {{{ registry()
    /**
     * Access a resource from the application registry.
     *
     * @param  string $index Registry resource
     * @return mixed Registry object or null
     */
    public static function registry($index)
    {
        if (isset(self::$_registry[$index])) {
            return self::$_registry[$index];
        }

        return null;
    }
    // }}}
    
   
    // {{{ run()
    /**
     * Initiate the Proficio process and dispatch it
     * 
     * @uses   Proficio::_app() Application bootstrap
     * @uses   Proficio_Core_Model_App::dispatch() Application dispatcher
     */
    public static function run()
    {
        try {
            self::_app();
            self::_app()->dispatch();
        }
        catch (Exception $e) {
            self::printException($e);
        }
    }
    // }}}
    
    // {{{ unregister()
    /**
     * Unregister a resource from the application registry.
     *
     * @param string $index registry resource
     */
    public static function unregister($index)
    {
        if (isset(self::$_registry[$index])) {
            if (is_object(self::$_registry[$index]) && (method_exists(self::$_registry[$index], '__destruct'))) {
                self::$_registry[$index]->__destruct();
            }

            unset(self::$_registry[$index]);
        }
    }
    // }}}
}
// }}}