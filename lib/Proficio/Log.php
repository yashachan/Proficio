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
 * @version  SVN: $Id: Log.php 37 2010-02-27 17:07:35Z abrault $
 */

// {{{ Proficio_Log
/**
 * A logging facility extended off of Zend_Log.
 * 
 * The original Zend_Log (as of 1.9.6) is incapable of allowing one to have a custom timestamp format. So
 * I had to extend the class and overload Zend_Log::log() in order to fix that issue.
 *
 * @category Proficio
 * @package  Proficio_Log
 * @see      Zend_Log
 * @version  SVN: $Id: Log.php 37 2010-02-27 17:07:35Z abrault $
 */
final class Proficio_Log extends Zend_Log 
{
    // {{{ log()
    /**
     * Log a message at a priority
     *
     * @param  string   $message   Message to log
     * @param  integer  $priority  Priority of message
     * @see    Zend_Log::log()
     * @throws Zend_Log_Exception
     */
    public function log($message, $priority, $extras = null)
    {
        if (empty($this->_writers)) {
            require_once 'Zend/Log/Exception.php';
            throw new Zend_Log_Exception('No Writers were added');
        }
        
        if (!isset($this->_priorities[$priority])) {
            require_once 'Zend/Log/Exception.php';
            throw new Zend_Log_Exception('Bad log priority');
        }
        
        $event = array_merge(array('timestamp'    => date('r'),
                                   'message'      => $message,
                                   'priority'     => $priority,
                                   'priorityName' => $this->_priorities[$priority]),
                                   $this->_extras);

        if (!empty($extras)) {
            $info = array();
            if (is_array($extras)) {
                foreach ($extras as $key => $value) {
                    if (is_string($key)) {
                        $event[$key] = $value;
                    }
                    else {
                        $info[] = $value;
                    }
                }
            }
            else {
                $info = $extras;
            }
            
            if (!empty($info)) {
                $event['info'] = $info;
            }
        }
                                   
        foreach ($this->_filters as $filter) {
            if (!$filter->accept($event)) {
                return;
            }
        }
        
        foreach($this->_writers as $writer) {
            $writer->write($event);
        }
    }
    // }}}
}
// }}}