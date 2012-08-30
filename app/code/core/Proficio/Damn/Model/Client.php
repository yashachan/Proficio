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
 * @version  SVN: $Id: Client.php 38 2010-03-13 19:18:23Z abrault $
 */

// {{{ Proficio_Damn_Model_Client
/**
 * dAmn (deviantART Message Network) Independant Client Interface
 * 
 * @category Proficio
 * @package  Damn_Model_Client
 * @author   Alan Brault <alan.brault@incruentatus.net>
 */
final class Proficio_Damn_Model_Client
{
    // {{{ propeties
    /**
     * Store when socket received data was last accessed. 
     *
     * @var integer last accessed timestamp
     */
    private static $_access = 0;

    /**
     * User-agent string to identify this dAmn client is a bot and not a browser.
     *
     * @var string user-agent string
     */
    private static $_agent = 'proficio/1.0.0';

    /**
     * Store a buffer of a retrieved packet of 8192 bytes for manipulation and
     * processing.
     *
     * @var resource packet buffer
     */
    private static $_buffer = null;

    /**
     * Client string to identify this dAmn client is a bot and not a browser.
     *
     * @var string agent string
     */
    private static $_client = 'proficio';

    /**
     * Connection state of the dAmn client to the dAmn server.
     * 
     * @var boolean connected status
     */
    private static $_connected = false;
    
    /**
     * This will store any error codes returned by fsockopen(). This is only
     * used during Proficio_Damn_Model_Client::authenticate().
     *
     * @var integer socket error number
     */
    private static $_errno = 0;

    /**
     * This will store any error string returned by fsockopen(). This is only
     * used during Proficio_Damn_Model_Client::authenticate().
     *
     * @var string socket error string
     */
    private static $_errstr = null;

    /**
     * Connection state of whether or not the dAmn client's connection reset.
     *
     * @var boolean connection error status
     */
    private static $_reset = false;
    
    /**
     * Number of retries attempted to reconnect to dAmn server.
     * 
     * @var int connection retry count
     */
    private static $_retry = 0;
    
    /**
     * Maximum number of retries allowed for reconnecting.
     * 
     * @var int connection retry maximum
     */
    private static $_rmax = 5;
        
    /**
     * This stores critical information necessary to connect to dAmn. It
     * cannot be changed through instantiation and is necessary for this
     * class to operate correctly.
     *
     * @var array dAmn server
     */
    private static $_server =
        array('chat' => array('host'  => 'chat.deviantart.com',
                                         'ver'  => '0.3',
                                         'port' => 3900),
                              'login' => array('protocol' => 'https://',
                                               'host'     => 'www.deviantart.com',
                                               'uri'      => '/users/login'));

    /**
     * This stores the resource connection created by the stream socket for
     * dAmn. It is used in all aspects of communication with dAmn.
     *
     * @var resource stream socket
     */
    private static $_socket = null;
    
    /**
     * Stores whether or not we've successfully authenticated with deviantART.
     *
     * @var boolean authenticated status
     */
    public static $authenticated = false;

    /**
     * deviantART authentication token obtained from Proficio_Damn_Model_Client::authenticate().
     *
     * @var array dAmn cookie
     */
    public static $cookie = null;

    /**
     * Stores application configuration registry.
     *
     * @var array configuration registry
     */
    public static $config = null;
    
    /**
     * Store what our username is for reference.
     * 
     * @var string dAmn client username
     */
    public static $username = null;
    // }}}
    
    // {{{ _read()
    /**
     * Read incoming stream data from dAmn and process accordingly.
     *
     * @return boolean true if data read; false if there was no data read
     */
    private static function _read()
    {
        $r = array(self::$_socket);
        $w = null;
        $e = null;

        if ((($x = @socket_select($r, $w, $e, 0)) !== false) && $x > 0) {
            if (in_array(self::$_socket, $r)) {
                $data = @socket_read(self::$_socket, 8192);
                
                switch (@socket_last_error(self::$_socket)) {
                    case 0:
                        break;
                    case 10050:
                        self::$_connected = false;
                        Proficio::log('Network is down', 2);
                        break;
                    case 10051:
                        self::$_connected = false;
                        Proficio::log('Network is unreachable', 2);
                        break;
                    case 10052:
                        self::$_reset = true;
                        Proficio::log('Network dropped connection on reset', 2);
                        break;
                    case 10053:
                        self::$_reset = true;
                        Proficio::log('Software caused connection abort', 2);
                        break;
                    case 10054:
                        self::$_reset = true;
                        Proficio::log('Connection reset by peer', 2);
                        break;
                    default:
                        self::$_connected = false;
                        Proficio::log('Generic Network Failure: (' .
                                      @socket_last_error(self::$_socket) . ') ' .
                                      trim(@socket_strerror(@socket_last_error(self::$_socket))));
                        break;
                }
                
                if (empty($data) || $data === null) return false;
              
                self::$_buffer .= $data;
                $parts = explode(chr(0), self::$_buffer);
                self::$_buffer = ($parts[count($parts) - 1] != '' ? $parts[count($parts) - 1] : '');
                unset($parts[count($parts) - 1]);

                if (is_array($parts)) {
                    foreach ($parts as $part) {
                        Proficio_Damn_Controller_Process::stream($part);
                    }
                }

                self::$_access = microtime(true);
            }
            else {
                if ((microtime(true) - self::$_access) < 100) {
                    self::$_reset = true;
                    self::$_access = microtime(true);
                    Proficio::log('Connection Timed Out', 2);
                }
            }
        }

        return true;
    }
    // }}}
    
    // {{{ _send()
    /**
     * Send raw packet information directly to dAmn.
     *
     * @param  mixed $stream packet data
     * @uses   Proficio::log() logging stream
     * @uses   Proficio::read() packet stream reader
     */
    private static function _send($stream)
    {
        if (self::$_connected === true) {
            if (!empty($stream) || $stream !== null) {
                if (strlen($stream) >= 7168) {
                    $stream = substr($stream, 0, 7162) . ' [...]' . chr(0);
                    Proficio::log('Received packet was truncated', 4);
                }

                self::_read();
                @socket_write(self::$_socket, $stream, strlen($stream));
                self::_read();
            }
            
            return true;
        }
        else {
            return false;
        }
    }
    // }}}

    // {{{ admin
    public static function admin($command, $arguments, $options = null, $channel_p) 
    {
        switch ($command) {

            case 'move':
            case 'remove':
            case 'rename':
            case 'show':
                $_command = $command;
                break;
            default: 
                $_command = null;
                break;
        }
        
        switch ($arguments) {
            case 'privclass':
            case 'users':
                $_arguments = $arguments;
                break;
            default:
                $_arguments = null;
                break;
        }
        
        if (!is_null($_command) && !is_null($_arguments) && !empty($channel_p)) {
            if (Proficio_Damn_Model_Channel::isRegistered($channel_p, 'channel')) {
                self::_send("send chat:$channel_p\n\nadmin\n\n$_command $_arguments $options" . chr(0));
            }
        }
    }
    // }}}
    
    // {{{ authenticate()
    /**
     * Initiate an authentication request with deviantART to obtain a session
     * token.
     *
     * @param  string $username username to authenticate with
     * @param  string $password password to authenticate with
     * @return mixed dAmn authentication cookie; false if authentication failed
     */
    public static function authenticate($username, $password)
    {
        $uri     = @implode('', self::$_server['login']);
        $client  = new Zend_Http_Client();
        
        $client->setCookieJar();
        $client->setUri($uri);
        $client->setParameterPost(array('ref'        => $uri,
                                        'username'   => $username,
                                        'password'   => $password,
                                        'reusetoken' => 1));
        
        $client->request(Zend_Http_Client::POST);
        $cookies = $client->getCookieJar()->getAllCookies();
        
        if (@array_key_exists(0, $cookies)) {
            if ($cookies[0] instanceof Zend_Http_Cookie) {
                $cookie = $cookies[0];
                $cookie = $cookie->getValue();
                $cookie = unserialize($cookie);
                
                if (is_array($cookie)) {
                    if (@array_key_exists('authtoken', $cookie)) {
                        return $cookie;
                    }
                }
            }
        }

        return false;
    }
    // }}}

    // {{{ autoJoin()
    /**
     * Automatically join all channels defined in the configuration.
     * 
     * @uses Proficio_Damn_Model_Client::join() join a channel
     */
    public static function autoJoin()
    {
        foreach (self::$config->base->damn->channels as $channels) {
            if (count($channels) > 1) {
                foreach ($channels as $channel) {
                    self::join($channel);
                }
            }
            else {
                self::join($channels);
            }
        }
    }
    // }}}

    // {{{ connect()
    /**
     * Initiate a connection to dAmn once an authentication token has been
     * acquired.
     *
     * @throws Exception Authentication token is missing
     */
    public static function connect()
    {
        if (@is_resource(self::$_socket)) {
            @socket_shutdown(self::$_socket);
            @socket_close(self::$_socket);
            self::$_socket = null;
        }

        if (!@array_key_exists('authtoken', self::$cookie)) {
            throw new Exception('dAmn: Unable to retrieve or load the required authentication token. Please ' .
                                'verify that one is cached or loaded by Proficio_Damn_Model_Client::authenticate().');
        }

        self::$_socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $_result       = @socket_connect(self::$_socket, self::$_server['chat']['host'], self::$_server['chat']['port']);
            
        if ($_result !== false) {
            $data  = 'dAmnClient ' . self::$_server['chat']['ver'] . chr(10);
            $data .= 'agent=' . self::$_agent . chr(10);
            $data .= 'bot=' . self::$_client . chr(10);
            $data .= 'trigger=' . self::$config->base->damn->trigger . chr(10);
            $data .= 'creator=extrarius/alan.brault@incruentatus.net' . chr(10)
                     . chr(0);

            @socket_write(self::$_socket, $data, strlen($data));
            self::$_reset     = false;
            self::$_retry     = 0;
            self::$_connected = true;
        }
    }
    // }}}

    // {{{ disconnect()
    /**
     * Execute a proper disconnection from dAmn.
     *
     * @uses Proficio_Damn_Model_Client::_send() send raw data packet
     */
    public static function disconnect()
    {
        self::_send("disconnect\n" . chr(0));
        self::$_connected = false;
    }
    // }}}

    // {{{ join()
    /**
     * Join a channel on dAmn.
     *
     * @param  string $channel channel name
     * @return bool true if join successful; false if not
     * @uses   Proficio_Damn_Model_Client::_send() send raw data packet
     */
    public static function join($channel)
    {
        if (!empty($channel)) {
           self::_send("join chat:$channel\n" . chr(0)); 
        }
    }
    // }}}
    
    // {{{ listen()
    /**
     * Listen on the TCP stream created by Proficio_Damn_Model_Client::connect() for activity
     * from dAmn and reconnect if a connection reset was detected.
     * 
     * @uses Proficio_Damn_Model_Client::_read();
     * @uses Proficio_Damn_Model_Client::autoJoin();
     * @uses Proficio_Damn_Model_Client::connect();
     * @uses Proficio_Damn_Model_Client::login();
     * @uses Proficio_Damn_Model_Client::pong();
     */
    public static function listen()
    {
        while (self::$_connected === true) {
           usleep(10000);
           
           if (self::$_connected === true) {
               self::_read();
               
               if (self::$_reset === true && (self::$_retry < self::$_rmax)) {
                   usleep(5000000);
                   Proficio::log('Retrying connection ' . self::$_retry . ' of 5', 2);
                   
                   self::connect();
                   self::login();
                   self::autoJoin();
                   self::pong();
                   
                   self::$_retry++;
               }
           }
        }
    }
    // }}}
    
    // {{{ login()
    /**
     * Commence final authentication with the deviantART Message Network.
     * 
     * @throws Exception
     * @uses   Proficio_Damn_Model_Client::_send() send raw data packet
     */
    public static function login()
    {
        if (!@array_key_exists('authtoken', self::$cookie)) {
            throw new Exception('dAmn: Unable to retrieve or load the required authentication token. Please ' .
                                'verify that one is cached or loaded by Proficio_Damn_Model_App::authenticate().');
        }

        if (self::$_connected === true) {
            self::_send('login ' . self::$config->base->damn->auth->username . "\n" .
                        'pk=' . self::$cookie['authtoken'] . "\n" . chr(0));
            self::$authenticated = true;
            self::$username      = self::$config->base->damn->auth->username;
        }
        else {
            throw new Exception('dAmn: There is no connection to deviantART present. Please make sure you successfully ' .
                                'connected via Proficio_Damn_Model_App::connect() and try again.');
        }
    }
    // }}}

    // {{{ part()
    /**
     * Send a part command to dAmn to leave a channel.
     * 
     * @param string $channel channel to part
     * @uses  Proficio_Damn_Model_Client::_send() send raw data packet
     */
    public static function part($channel)
    {
        if (!empty($channel)) {
            self::_send("part chat:$channel\n" . chr(0));
        }
    }
    // }}}
    
    // {{{ pong()
    /**
     * Send a pong event to dAmn (generally if a ping request is received).
     * 
     * @uses Proficio_Damn_Model_Client::_send() send raw data packet
     */
    public static function pong()
    {
        self::_send("pong\n" . chr(0));
    }
    // }}}

    // {{{ say()
    /**
     * Send a message to the desired channel.
     *
     * @param  string $message the message to transmit
     * @param  string $channel the channel to receive the message
     * @uses   Proficio_Damn_Model_Client::_send() send raw data packet
     */
    public static function say($message, $channel_p)
    {
        if (!empty($message) && !empty($channel_p)) {
            if (Proficio_Damn_Model_Channel::isRegistered($channel_p, 'channel')) {
                self::_send("send chat:$channel_p\n\nmsg main\n\n$message" . chr(0));
            }
        }
    }
    // }}}
}
// }}}