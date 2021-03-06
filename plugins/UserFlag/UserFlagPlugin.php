<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Allows users to flag content and accounts as offensive/spam/whatever
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
 * @category  Plugin
 * @package   StatusNet
 *
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 *
 * @see      http://status.net/
 */
if (!defined('STATUSNET')) {
    exit(1);
}

/**
 * Allows users to flag content and accounts as offensive/spam/whatever
 *
 * @category Plugin
 * @package  StatusNet
 *
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 *
 * @see     http://status.net/
 */
class UserFlagPlugin extends Plugin
{
    const PLUGIN_VERSION = '2.0.0';

    const REVIEWFLAGS = 'UserFlagPlugin::reviewflags';
    const CLEARFLAGS  = 'UserFlagPlugin::clearflags';

    public $flagOnBlock = true;

    /**
     * Hook for ensuring our tables are created
     *
     * Ensures that the user_flag_profile table exists
     * and has the right columns.
     *
     * @return bool hook return
     */
    public function onCheckSchema(): bool
    {
        $schema = Schema::get();

        // For storing user-submitted flags on profiles
        $schema->ensureTable('user_flag_profile', User_flag_profile::schemaDef());
        return true;
    }

    /**
     * Add our actions to the URL router
     *
     * @param URLMapper $m URL mapper for this hit
     *
     * @return bool hook return
     */
    public function onRouterInitialized(URLMapper $m): bool
    {
        $m->connect('main/flag/profile', ['action' => 'flagprofile']);
        $m->connect('main/flag/clear', ['action' => 'clearflag']);
        $m->connect('panel/profile/flag', ['action' => 'adminprofileflag']);
        return true;
    }

    /**
     * Add a 'flag' button to profile page
     *
     * @param Action  $action  The action being called
     * @param Profile $profile Profile being shown
     *
     * @return bool hook result
     */
    public function onEndProfilePageActionsElements(Action $action, Profile $profile): bool
    {
        $this->showFlagButton(
            $action,
            $profile,
            ['action'      => 'showstream',
                'nickname' => $profile->nickname, ]
        );

        return true;
    }

    /**
     * Add a 'flag' button to profiles in a list
     *
     * @param ProfileListItem $item item being shown
     *
     * @return bool hook result
     */
    public function onEndProfileListItemActionElements(ProfileListItem $item): bool
    {
        list($action, $args) = $item->action->returnToArgs();
        $args['action']      = $action;
        $this->showFlagButton($item->action, $item->profile, $args);

        return true;
    }

    /**
     * Actually output a flag button. If the target profile has already been
     * flagged by the current user, a null-action faux button is shown.
     *
     * @param Action  $action
     * @param Profile $profile
     * @param array   $returnToArgs
     */
    protected function showFlagButton(Action $action, Profile $profile, array $returnToArgs): array
    {
        $user = common_current_user();

        if (!empty($user) && ($user->id != $profile->id)) {
            $action->elementStart('li', 'entity_flag');

            if (User_flag_profile::exists($profile->id, $user->id)) {
                // @todo FIXME: Add a title explaining what 'flagged' means?
                // TRANS: Message added to a profile if it has been flagged for review.
                $action->element('p', 'flagged', _m('Flagged'));
            } else {
                $form = new FlagProfileForm($action, $profile, $returnToArgs);
                $form->show();
            }

            $action->elementEnd('li');
        }
    }

    /**
     * Check whether a user has one of our defined rights
     *
     * We define extra rights; this function checks to see if a
     * user has one of them.
     *
     * @param Profile   $user    User being checked
     * @param string $right   Right we're checking
     * @param bool   &$result out, result of the check
     *
     * @return bool hook result
     */
    public function onUserRightsCheck(Profile $user, string $right, bool &$result): bool
    {
        switch ($right) {
        case self::REVIEWFLAGS:
        case self::CLEARFLAGS:
            $result = $user->hasRole('moderator');
            return false; // done processing!
        }

        return true; // unchanged!
    }

    /**
     * Optionally flag profile when a block happens
     *
     * We optionally add a flag when a profile has been blocked
     *
     * @param User    $user    User doing the block
     * @param Profile $profile Profile being blocked
     *
     * @return bool hook result
     */
    public function onEndBlockProfile(User $user, Profile $profile): bool
    {
        if ($this->flagOnBlock && !User_flag_profile::exists(
            $profile->id,
            $user->id
        )) {
            User_flag_profile::create($user->id, $profile->id);
        }
        return true;
    }

    /**
     * Ensure that flag entries for a profile are deleted
     * along with the profile when deleting users.
     * This prevents breakage of the admin profile flag UI.
     *
     * @param Profile $profile
     * @param array   &$related list of related tables; entries
     *                          with matching profile_id will be deleted.
     *
     * @return bool hook result
     */
    public function onProfileDeleteRelated(Profile $profile, array &$related): bool
    {
        $related[] = 'User_flag_profile';
        return true;
    }

    /**
     * Ensure that flag entries created by a user are deleted
     * when that user gets deleted.
     *
     * @param User  $user
     * @param array &$related list of related tables; entries
     *                        with matching user_id will be deleted.
     *
     * @return bool hook result
     */
    public function onUserDeleteRelated(User $user, array &$related): bool
    {
        $related[] = 'User_flag_profile';
        return true;
    }

    /**
     * Provide plugin version information.
     *
     * This data is used when showing the version page.
     *
     * @param array &$versions array of version data arrays; see EVENTS.txt
     *
     * @return bool hook value
     */
    public function onPluginVersion(array &$versions): bool
    {
        $url = GNUSOCIAL_ENGINE_REPO_URL . 'tree/master/plugins/UserFlag';

        $versions[] = ['name' => 'UserFlag',
            'version'         => self::PLUGIN_VERSION,
            'author'          => 'Evan Prodromou',
            'homepage'        => $url,
            'rawdescription'  => // TRANS: Plugin description.
            _m('This plugin allows flagging of profiles for review and reviewing flagged profiles.'), ];

        return true;
    }
}
