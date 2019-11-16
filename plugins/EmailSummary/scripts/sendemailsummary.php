#!/usr/bin/env php
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

/**
 * @package   GNUsocial
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

define('INSTALLDIR', dirname(__DIR__, 3));
define('PUBLICDIR', INSTALLDIR);

$shortoptions = 'i:n:au';
$longoptions = array('id=', 'nickname=', 'all', 'universe');

$helptext = <<<END_OF_SENDEMAILSUMMARY_HELP
sendemailsummary.php [options]
Send an email summary of the inbox to users

 -i --id       ID of user to send summary to
 -n --nickname nickname of the user to send summary to
 -a --all      send summary to all users
 -u --universe send summary to all users on all sites

END_OF_SENDEMAILSUMMARY_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

if (have_option('u', 'universe')) {
    $sn = new Status_network();
    if ($sn->find()) {
        while ($sn->fetch()) {
            $server = $sn->getServerName();
            GNUsocial::init($server);
            // Different queue manager, maybe!
            $qm = QueueManager::get();
            $qm->enqueue(1, 'sitesum');
        }
    }
} else {
    $qm = QueueManager::get();
    // enqueue summary for user or all users
    try {
        $user = getUser();
        $qm->enqueue($user->id, 'usersum');
    } catch (NoUserArgumentException $nuae) {
        $qm->enqueue(1, 'sitesum');
    }
}
