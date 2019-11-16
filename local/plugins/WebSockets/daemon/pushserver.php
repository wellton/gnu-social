<?php
// From composer
require __DIR__ . '/../vendor/autoload.php';

// GNU social INSTALLDIR if we're in /plugins/WebSockets/daemon
$dir = realpath(dirname(__FILE__) . '/../../..');

// GNU social INSTALLDIR if we're in /local/plugins/WebSockets/daemon
// NOTE: False positive if the root folder of the GS install is
//       called 'local', I guess
if (preg_match('/\/local$/', $dir) === 1) {
    $dir = realpath($dir . '/..');
}

define('INSTALLDIR', $dir);

require_once INSTALLDIR . '/scripts/commandline.inc';
require_once INSTALLDIR . '/lib/common.php';
require_once INSTALLDIR . '/lib/daemon.php';

class PushServer extends Daemon
{
    public function __construct($daemonize)
    {
        parent::__construct($daemonize);
    }

    public function name()
    {
        return ('pushserver.' . $this->_id) ;
    }

    public function run()
    {
        $loop   = React\EventLoop\Factory::create();
        $pusher = new wsRealtime\Pusher;

        $controlserver = common_config('websockets', 'controlserver');
        $controlport   = common_config('websockets', 'controlport');
        $webserver     = common_config('websockets', 'webserver');
        $webport       = common_config('websockets', 'webport');

        // Listen for the web server to make a ZeroMQ push after an ajax request
        $context = new React\ZMQ\Context($loop);
        $pull = $context->getSocket(ZMQ::SOCKET_PULL);
        $pull->bind('tcp://' . $controlserver . ':' . $controlport);
        $pull->on('message', array($pusher, 'onNewNotice'));

        // Set up our WebSocket server for clients wanting real-time updates
        $webSock = new React\Socket\Server($loop);
        $webSock->listen($webport, $webserver);
        $webServer = new Ratchet\Server\IoServer(
            new Ratchet\Http\HttpServer(
                    new Ratchet\WebSocket\WsServer(
                        new Ratchet\Wamp\WampServer(
                            $pusher
                        )
                    )
                ),
            $webSock
        );

        $loop->run();
    }
}

$server = new PushServer(true);
$server->runOnce();
