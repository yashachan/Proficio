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
 * @version  SVN: $Id: Options.php 37 2010-02-27 17:07:35Z abrault $
 */

// {{{ Proficio_Core_Model_Config_Options()
final class Proficio_Core_Model_Config_Options
{   
    // {{{ Proficio_Core_Model_Config_Options()
    public function __construct()
    {
        $appRoot = Proficio::getAppRoot();
        $root    = dirname($appRoot);
        
        Proficio_Object::addObject('Proficio/app_dir', $appRoot);
        Proficio_Object::addObject('Proficio/base_dir', $root);
        Proficio_Object::addObject('Proficio/code_dir', $appRoot . DS . 'code');
        Proficio_Object::addObject('Proficio/etc_dir', $appRoot . DS . 'etc');
        Proficio_Object::addObject('Proficio/var_dir', $root . DS. 'var');
        Proficio_Object::addObject('Proficio/cache_dir', Proficio_Object::getObject('Proficio/var_dir') . DS . 'cache');
        Proficio_Object::addObject('Proficio/db_dir', Proficio_Object::getObject('Proficio/var_dir') . DS . 'db');
        Proficio_Object::addObject('Proficio/lock_dir', Proficio_Object::getObject('Proficio/var_dir') . DS . 'lock');
        Proficio_Object::addObject('Proficio/log_dir', Proficio_Object::getObject('Proficio/var_dir') . DS . 'log');
    }
    // }}}

    // {{{
    public function getAppDir()
    {
        return Proficio_Object::getObject('Proficio/app_dir');
    }
    // }}}
    
    // {{{
    public function getBaseDir()
    {
        return Proficio_Object::getObject('Proficio/base_dir');
    }
    // }}}
    
    // {{{
    public function getCodeDir()
    {
        return Proficio_Object::getObject('Proficio/code_dir');
    }
    // }}}
    
    // {{{
    public function getEtcDir()
    {
        return Proficio_Object::getObject('Proficio/etc_dir');
    }
    // {{{
    
    // {{{
    public function getVarDir()
    {
       return Proficio_Object::getObject('Proficio/var_dir');
    }
    // }}}
    
    // {{{
    public function getCacheDir()
    {
        return Proficio_Object::getObject('Proficio/cache_dir');
    }
    // }}}
    
    // {{{
    public function getDbDir()
    {
        return Proficio_Object::getObject('Proficio/db_dir');
    }
    // }}}
    
    // {{{
    public function getLockDir()
    {
        return Proficio_Object::getObject('Proficio/lock_dir');
    }
    // }}}
    
    // {{{
    public function getLogDir()
    {
        return Proficio_Object::getObject('Proficio/log_dir');
    }
    // }}}
}
// }}}