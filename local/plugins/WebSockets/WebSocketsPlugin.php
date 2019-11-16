<?php
if (!defined('GNUSOCIAL')) {
    exit(1);
}

require_once INSTALLDIR . '/plugins/Realtime/RealtimePlugin.php';

class WebSocketsPlugin extends RealtimePlugin
{
    const VERSION = '0.1.0';

    public $webserver     = null;
    public $webport       = null;
    public $path          = null;
    public $controlserver = null;
    public $controlport   = null;
    protected $_socket    = null;

    public function __construct($webserver=null, $webport=8080, $path='', $sslport=null, $controlport=5555, $controlserver=null)
    {
        global $config;

        $this->webserver     = (empty($webserver)) ? $config['site']['server'] : $webserver;
        $this->webport       = $webport;
        $this->path          = $path;
        $this->sslport       = $webport; // SSL port defaults to $webport
        $this->controlserver = (empty($controlserver)) ? 'locahost' : $controlserver;
        $this->controlport   = $controlport;

        parent::__construct();
    }

    /**
     * Pull settings from config file/database if set.
     */
    public function initialize()
    {
        $settings = array(
            'webserver',
            'webport',
            'path',
            'sslport',
            'controlserver',
            'controlport'
        );

        foreach ($settings as $name) {
            $val = common_config('websockets', $name);
            if ($val !== false) {
                $this->$name = $val;
            }
        }

        return parent::initialize();
    }

    public function _getScripts()
    {
        $scripts = parent::_getScripts();

        $scripts[] = $this->path('/js/lib/autobahn.min.js');
        $scripts[] = $this->path('/js/websockets.js');

        return $scripts;
    }

    public function _updateInitialize($timeline, $user_id)
    {
        $script = parent::_updateInitialize($timeline, $user_id);
        $ours = sprintf(
            "wsRealtime.init(%s, %s, %s, %s, %s);",
            json_encode($this->webserver),
            json_encode($this->webport),
            json_encode($this->path),
            json_encode($this->sslport),
            json_encode($timeline)
        );
        return $script . " " . $ours;
    }

    public function _connect()
    {
        $context = new ZMQContext();
        $socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher');
        $socket->connect("tcp://" . $this->controlserver . ":" . $this->controlport);

        $this->_socket = $socket;

        // TODO: Error handling.
    }

    public function _publish($channel, $message)
    {
        $message['channel'] = $channel;
        $message = json_encode($message);
        $message = addslashes($message);

        $this->_socket->send($message);
    }

    public function _disconnect()
    {
        /*        if (!$this->persistent) {
                    $cnt = fwrite($this->_socket, "QUIT\n");
                    @fclose($this->_socket);
                } */
    }

    public function _pathToChannel($path)
    {
        /*        if (!empty($this->channelbase)) {
                    array_unshift($path, $this->channelbase);
                } */
        return implode('-', $path);
    }

    public function onAutoload($cls)
    {
        $realtime_dir = INSTALLDIR . '/plugins/Realtime';

        switch ($cls) {
            case 'KeepalivechannelAction':
            case 'ClosechannelAction':
                require_once($realtime_dir . '/actions/' . strtolower(mb_substr($cls, 0, -6)) . '.php');
                return false;
            case 'Realtime_channel':
                require_once($realtime_dir . '/classes/' . $cls . '.php');
                return false;
            default:
                return true;
        }
    }

    public function onGetValidDaemons(&$daemons)
    {
        $daemons[] = __DIR__ . '/daemon/pushserver.php';
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = array('name' => 'Websockets',
                            'version' => self::VERSION,
                            'author' => 'Chimo',
                            'homepage' => 'http://github.com/chimo/wsRealtime',
                            'description' =>
                            // TRANS: Plugin description.
                            _m('Plugin to do "real time" updates using Websockets.'));
        return true;
    }
}
