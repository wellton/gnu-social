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
 * StatusNet, the distributed open-source microblogging tool
 *
 * Module to add additional awesomenss to StatusNet
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Module
 * @package   StatusNet
 * @author    Jeroen De Dauw <jeroendedauw@gmail.com>
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

defined('GNUSOCIAL') || die();

/**
 * Allows to overwrite your theme's background style
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

class OverwriteThemeBackgroundPlugin extends Plugin
{
    const PLUGIN_VERSION = '0.1.0';

    /**
     * Route urls
     *
     * @param URLMapper $m
     * @return bool
     * @throws Exception
     */
    public function onRouterInitialized(URLMapper $m): bool
    {
        $m->connect('plugins/OverwriteThemeBackground/css/my_custom_theme_bg',
                    ['action' => 'OverwriteThemeBackgroundCSS']);
        $m->connect('admin/overwritethemebackground',
                    ['action' => 'overwritethemebackgroundAdminPanel']);
        return true;
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name' => 'Custom Background',
            'version' => self::PLUGIN_VERSION,
            'author' => 'Diogo Cordeiro',
            'homepage' => 'https://www.diogo.site/projects/GNU-social/plugins/OverwriteThemeBackgroundPlugin',
            // TRANS: Plugin description for OverwriteThemeBackground plugin.
            'rawdescription' => _m('A friendly plugin for overwriting your theme\'s background style.')
        ];
        return true;
    }

    public function onEndShowStyles(Action $action): bool
    {
        $action->cssLink(common_local_url('OverwriteThemeBackgroundCSS'));
        return true;
    }

    public function onEndAdminPanelNav(AdminPanelNav $nav): bool
    {
        if (AdminPanelAction::canAdmin('profilefields')) {
            $action_name = $nav->action->trimmed('action');

            $nav->out->menuItem(
                common_local_url('overwritethemebackgroundAdminPanel'),
                _m('Overwrite Theme Background'),
                _m('Customize your theme\'s background easily'),
                $action_name == 'overwritethemebackgroundAdminPanel',
                'nav_overwritethemebackground_admin_panel'
            );
        }

        return true;
    }
}
