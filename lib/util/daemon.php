<?php
// This file is part of GNU social - https://www.gnu.org/software/social
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.

defined('GNUSOCIAL') || die();

class Daemon
{
    public $daemonize = true;
    public $_id = 'generic';

    public function __construct($daemonize = true)
    {
        $this->daemonize = $daemonize;
    }

    public function name()
    {
        return null;
    }

    public function get_id()
    {
        return $this->_id;
    }

    public function set_id($id)
    {
        $this->_id = $id;
    }

    /**
     * Reconnect to the database for each child process,
     * or they'll get very confused trying to use the
     * same socket.
     */
    protected function resetDb()
    {
        global $_DB_DATAOBJECT;

        // Can't be called statically
        $user = new User();
        $conn = $user->getDatabaseConnection();
        $conn->disconnect();

        // Remove the disconnected connection from the list
        foreach ($_DB_DATAOBJECT['CONNECTIONS'] as $k => $v) {
            if ($v === $conn) {
                unset($_DB_DATAOBJECT['CONNECTIONS'][$k]);
            }
        }

        // Reconnect main memcached, or threads will stomp on
        // each other and corrupt their requests.
        $cache = Cache::instance();
        if ($cache) {
            $cache->reconnect();
        }

        // Also reconnect memcached for status_network table.
        if (!empty(Status_network::$cache)) {
            Status_network::$cache->close();
            Status_network::$cache = null;
        }
    }

    public function background()
    {
        // Database connection will likely get lost after forking
        $this->resetDb();

        // Double-forking.
        foreach (['single', 'double'] as $v) {
            switch ($pid = pcntl_fork()) {
                case -1: // error
                    common_log(LOG_ERR, 'Could not fork.');
                    return false;
                case 0:  // child
                    if ($v === 'single') {
                        posix_setsid();
                    }
                    break;
                default: // parent
                    if ($v === 'double') {
                        common_log(LOG_INFO, 'Successfully forked.');
                    }
                    die();
            }
        }

        return true;
    }

    public function alreadyRunning()
    {
        $pidfilename = $this->pidFilename();

        if (!$pidfilename) {
            return false;
        }

        if (!file_exists($pidfilename)) {
            return false;
        }
        $contents = file_get_contents($pidfilename);
        if (posix_kill(trim($contents), 0)) {
            return true;
        } else {
            return false;
        }
    }

    public function writePidFile()
    {
        $pidfilename = $this->pidFilename();

        if (!$pidfilename) {
            return false;
        }

        return file_put_contents($pidfilename, posix_getpid() . "\n");
    }

    public function clearPidFile()
    {
        $pidfilename = $this->pidFilename();
        if (!$pidfilename) {
            return false;
        }
        return unlink($pidfilename);
    }

    public function pidFilename()
    {
        $piddir = common_config('daemon', 'piddir');
        if (!$piddir) {
            return null;
        }
        $name = $this->name();
        if (!$name) {
            return null;
        }
        return $piddir . '/' . $name . '.pid';
    }

    public function changeUser()
    {
        $groupname = common_config('daemon', 'group');

        if ($groupname) {
            $group_info = posix_getgrnam($groupname);
            if (!$group_info) {
                common_log(
                    LOG_WARNING,
                    'Ignoring unknown group for daemon: ' . $groupname
                );
            } else {
                common_log(LOG_INFO, "Setting group to " . $groupname);
                posix_setgid($group_info['gid']);
            }
        }

        $username = common_config('daemon', 'user');

        if ($username) {
            $user_info = posix_getpwnam($username);
            if (!$user_info) {
                common_log(
                    LOG_WARNING,
                    'Ignoring unknown user for daemon: ' . $username
                );
            } else {
                common_log(LOG_INFO, "Setting user to " . $username);
                posix_setuid($user_info['uid']);
            }
        }
    }

    public function runOnce()
    {
        if ($this->alreadyRunning()) {
            common_log(LOG_INFO, $this->name() . ' already running. Exiting.');
            exit(0);
        }

        if ($this->daemonize) {
            common_log(LOG_INFO, 'Backgrounding daemon "'.$this->name().'"');
            $this->background();
        }

        $this->writePidFile();
        $this->changeUser();
        $this->run();
        $this->clearPidFile();
    }

    public function run()
    {
        return true;
    }
}
