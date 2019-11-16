<?php
namespace wsRealtime;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

class Pusher implements WampServerInterface
{
    protected $subscribedPaths = array();

    public function onSubscribe(ConnectionInterface $conn, $channel)
    {
        if (!array_key_exists($channel->getId(), $this->subscribedPaths)) {
            $this->subscribedPaths[$channel->getId()] = $channel;
        }
    }

    public function onNewNotice($json)
    {
        $json = stripslashes($json);
        $notice = json_decode($json, true);

        // Make sure we have subscribers
        if (!array_key_exists($notice['channel'], $this->subscribedPaths)) {
            return;
        }

        $channel = $this->subscribedPaths[$notice['channel']];
        $channel->broadcast($notice);
    }

    public function onUnSubscribe(ConnectionInterface $conn, $channel)
    {
    }
    public function onOpen(ConnectionInterface $conn)
    {
    }
    public function onClose(ConnectionInterface $conn)
    {
    }
    public function onCall(ConnectionInterface $conn, $id, $channel, array $params)
    {
        $conn->callError($id, $channel, 'You are not allowed to make calls')->close();
    }
    public function onPublish(ConnectionInterface $conn, $channel, $event, array $exclude, array $eligible)
    {
        $conn->close();
    }
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
    }
}
