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

/**
 * Table Definition for user_group
 */
class User_group extends Managed_DataObject
{
    const JOIN_POLICY_OPEN = 0;
    const JOIN_POLICY_MODERATE = 1;
    const CACHE_WINDOW = 201;

    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'user_group';                      // table name
    public $id;                              // int(4)  primary_key not_null
    public $profile_id;                      // int(4)  primary_key not_null
    public $nickname;                        // varchar(64)
    public $fullname;                        // varchar(191)   not 255 because utf8mb4 takes more space
    public $homepage;                        // varchar(191)   not 255 because utf8mb4 takes more space
    public $description;                     // text
    public $location;                        // varchar(191)   not 255 because utf8mb4 takes more space
    public $original_logo;                   // varchar(191)   not 255 because utf8mb4 takes more space
    public $homepage_logo;                   // varchar(191)   not 255 because utf8mb4 takes more space
    public $stream_logo;                     // varchar(191)   not 255 because utf8mb4 takes more space
    public $mini_logo;                       // varchar(191)   not 255 because utf8mb4 takes more space
    public $created;                         // datetime()
    public $modified;                        // timestamp()    not_null default_CURRENT_TIMESTAMP
    public $uri;                             // varchar(191)  unique_key   not 255 because utf8mb4 takes more space
    public $mainpage;                        // varchar(191)   not 255 because utf8mb4 takes more space
    public $join_policy;                     // tinyint
    public $force_scope;                     // tinyint

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public function getObjectType()
    {
        return ActivityObject::GROUP;
    }


    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'id' => array('type' => 'serial', 'not null' => true, 'description' => 'unique identifier'),
                'profile_id' => array('type' => 'int', 'not null' => true, 'description' => 'foreign key to profile table'),

                'nickname' => array('type' => 'varchar', 'length' => 64, 'description' => 'nickname for addressing'),
                'fullname' => array('type' => 'varchar', 'length' => 191, 'description' => 'display name'),
                'homepage' => array('type' => 'varchar', 'length' => 191, 'description' => 'URL, cached so we dont regenerate'),
                'description' => array('type' => 'text', 'description' => 'group description'),
                'location' => array('type' => 'varchar', 'length' => 191, 'description' => 'related physical location, if any'),

                'original_logo' => array('type' => 'varchar', 'length' => 191, 'description' => 'original size logo'),
                'homepage_logo' => array('type' => 'varchar', 'length' => 191, 'description' => 'homepage (profile) size logo'),
                'stream_logo' => array('type' => 'varchar', 'length' => 191, 'description' => 'stream-sized logo'),
                'mini_logo' => array('type' => 'varchar', 'length' => 191, 'description' => 'mini logo'),

                'created' => array('type' => 'datetime', 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),

                'uri' => array('type' => 'varchar', 'length' => 191, 'description' => 'universal identifier'),
                'mainpage' => array('type' => 'varchar', 'length' => 191, 'description' => 'page for group info to link to'),
                'join_policy' => array('type' => 'int', 'size' => 'tiny', 'description' => '0=open; 1=requires admin approval'),
                'force_scope' => array('type' => 'int', 'size' => 'tiny', 'description' => '0=never,1=sometimes,-1=always'),
            ),
            'primary key' => array('id'),
            'unique keys' => array(
                'user_group_uri_key' => array('uri'),
// when it's safe and everyone's run upgrade.php                'user_profile_id_key' => array('profile_id'),
            ),
            'foreign keys' => array(
                'user_group_profile_id_fkey' => array('profile', array('profile_id' => 'id')),
            ),
            'indexes' => array(
                'user_group_nickname_idx' => array('nickname'),
                'user_group_profile_id_idx' => array('profile_id'), //make this unique in future
            ),
        );
    }

    protected $_profile = array();

    /**
     * @return Profile
     *
     * @throws GroupNoProfileException if user has no profile
     */
    public function getProfile()
    {
        if (!isset($this->_profile[$this->profile_id])) {
            $profile = Profile::getKV('id', $this->profile_id);
            if (!$profile instanceof Profile) {
                throw new GroupNoProfileException($this);
            }
            $this->_profile[$this->profile_id] = $profile;
        }
        return $this->_profile[$this->profile_id];
    }

    public function getNickname()
    {
        return $this->getProfile()->getNickname();
    }

    public function getFullname()
    {
        return $this->getProfile()->getFullname();
    }

    public static function defaultLogo($size)
    {
        static $sizenames = array(AVATAR_PROFILE_SIZE => 'profile',
                                  AVATAR_STREAM_SIZE => 'stream',
                                  AVATAR_MINI_SIZE => 'mini');
        return Theme::path('default-avatar-'.$sizenames[$size].'.png');
    }

    public function homeUrl()
    {
        return $this->getProfile()->getUrl();
    }

    public function getUri()
    {
        $uri = null;
        if (Event::handle('StartUserGroupGetUri', array($this, &$uri))) {
            if (!empty($this->uri)) {
                $uri = $this->uri;
            } elseif ($this->isLocal()) {
                $uri = common_local_url('groupbyid', ['id' => $this->id]);
            }
        }
        Event::handle('EndUserGroupGetUri', array($this, &$uri));
        return $uri;
    }

    public function permalink()
    {
        $url = null;
        if (Event::handle('StartUserGroupPermalink', array($this, &$url))) {
            if ($this->isLocal()) {
                $url = common_local_url('groupbyid', ['id' => $this->id]);
            }
        }
        Event::handle('EndUserGroupPermalink', array($this, &$url));
        return $url;
    }

    public function getNotices($offset, $limit, $since_id = null, $max_id = null)
    {
        // FIXME: Get the Profile::current() some other way, to avoid
        // possible confusion between current session and queue process.
        $stream = new GroupNoticeStream($this, Profile::current());

        return $stream->getNotices($offset, $limit, $since_id, $max_id);
    }

    public function getMembers($offset = 0, $limit = null)
    {
        $ids = null;
        if (is_null($limit) || $offset + $limit > User_group::CACHE_WINDOW) {
            $ids = $this->getMemberIDs($offset, $limit);
        } else {
            $key = sprintf('group:member_ids:%d', $this->id);
            $window = self::cacheGet($key);
            if ($window === false) {
                $window = $this->getMemberIDs(0, User_group::CACHE_WINDOW);
                self::cacheSet($key, $window);
            }

            $ids = array_slice($window, $offset, $limit);
        }

        return Profile::multiGet('id', $ids);
    }

    public function getMemberIDs($offset = 0, $limit = null)
    {
        $gm = new Group_member();

        $gm->selectAdd();
        $gm->selectAdd('profile_id');

        $gm->group_id = $this->id;

        $gm->orderBy('created DESC');

        if (!is_null($limit)) {
            $gm->limit($offset, $limit);
        }

        $ids = array();

        if ($gm->find()) {
            while ($gm->fetch()) {
                $ids[] = $gm->profile_id;
            }
        }

        return $ids;
    }

    /**
     * Get pending members, who have not yet been approved.
     *
     * @param int $offset
     * @param int $limit
     * @return Profile
     */
    public function getRequests($offset = 0, $limit = null)
    {
        $rq = new Group_join_queue();
        $rq->group_id = $this->id;

        $members = new Profile();

        $members->joinAdd(['id', $rq, 'profile_id']);

        if ($limit != null) {
            $members->limit($offset, $limit);
        }

        $members->find();

        return $members;
    }

    public function getAdminCount()
    {
        $block = new Group_member();
        $block->group_id = $this->id;
        $block->is_admin = true;

        return $block->count();
    }

    public function getMemberCount()
    {
        $key = sprintf("group:member_count:%d", $this->id);

        $cnt = self::cacheGet($key);

        if (is_integer($cnt)) {
            return (int) $cnt;
        }

        $mem = new Group_member();
        $mem->group_id = $this->id;

        // XXX: why 'distinct'?

        $cnt = (int) $mem->count('distinct profile_id');

        self::cacheSet($key, $cnt);

        return $cnt;
    }

    public function getBlockedCount()
    {
        // XXX: WORM cache this

        $block = new Group_block();
        $block->group_id = $this->id;

        return $block->count();
    }

    public function getQueueCount()
    {
        // XXX: WORM cache this

        $queue = new Group_join_queue();
        $queue->group_id = $this->id;

        return $queue->count();
    }

    // offset is null because DataObject wants it, 0 would mean no results
    public function getAdmins($offset = null, $limit = null)
    {
        $admins = new Profile();
        $admins->joinAdd(['id', 'group_member:profile_id']);
        $admins->whereAdd(sprintf(
            'group_member.group_id = %d AND group_member.is_admin IS TRUE',
            $this->getID()
        ));
        $admins->orderBy('group_member.modified ASC');
        $admins->limit($offset, $limit);
        $admins->find();

        return $admins;
    }

    // offset is null because DataObject wants it, 0 would mean no results
    public function getBlocked($offset = null, $limit = null)
    {
        $blocked = new Profile();
        $blocked->joinAdd(array('id', 'group_block:blocked'));
        $blocked->whereAdd(sprintf('group_block.group_id = %u', $this->id));
        $blocked->orderBy('group_block.modified DESC');
        $blocked->limit($offset, $limit);
        $blocked->find();

        return $blocked;
    }

    public function setOriginal($filename)
    {
        // This should be handled by the Profile->setOriginal function so user and group avatars are handled the same
        $imagefile = new ImageFile(null, Avatar::path($filename));

        $sizes = array('homepage_logo' => AVATAR_PROFILE_SIZE,
                       'stream_logo' => AVATAR_STREAM_SIZE,
                       'mini_logo' => AVATAR_MINI_SIZE);

        $orig = clone($this);
        $this->original_logo = Avatar::url($filename);
        foreach ($sizes as $name=>$size) {
            $filename = Avatar::filename(
                $this->profile_id,
                image_type_to_extension($imagefile->preferredType()),
                $size,
                common_timestamp()
            );
            $imagefile->resizeTo(Avatar::path($filename), array('width'=>$size, 'height'=>$size));
            $this->$name = Avatar::url($filename);
        }
        common_debug(common_log_objstring($this));
        return $this->update($orig);
    }

    public function getBestName()
    {
        return ($this->fullname) ? $this->fullname : $this->nickname;
    }

    /**
     * Gets the full name (if filled) with nickname as a parenthetical, or the nickname alone
     * if no fullname is provided.
     *
     * @return string
     */
    public function getFancyName()
    {
        if ($this->fullname) {
            // TRANS: Full name of a profile or group followed by nickname in parens
            return sprintf(_m('FANCYNAME', '%1$s (%2$s)'), $this->fullname, $this->nickname);
        } else {
            return $this->nickname;
        }
    }

    public function getAliases()
    {
        $aliases = array();

        // XXX: cache this

        $alias = new Group_alias();

        $alias->group_id = $this->id;

        if ($alias->find()) {
            while ($alias->fetch()) {
                $aliases[] = $alias->alias;
            }
        }

        $alias->free();

        return $aliases;
    }

    public function setAliases($newaliases)
    {
        $newaliases = array_unique($newaliases);

        $oldaliases = $this->getAliases();

        // Delete stuff that's old that not in new

        $to_delete = array_diff($oldaliases, $newaliases);

        // Insert stuff that's in new and not in old

        $to_insert = array_diff($newaliases, $oldaliases);

        $alias = new Group_alias();

        $alias->group_id = $this->id;

        foreach ($to_delete as $delalias) {
            $alias->alias = $delalias;
            $result = $alias->delete();
            if (!$result) {
                common_log_db_error($alias, 'DELETE', __FILE__);
                return false;
            }
        }

        foreach ($to_insert as $insalias) {
            if ($insalias === $this->nickname) {
                continue;
            }
            $alias->alias = Nickname::normalize($insalias, true);
            $result = $alias->insert();
            if (!$result) {
                common_log_db_error($alias, 'INSERT', __FILE__);
                return false;
            }
        }

        return true;
    }

    public static function getForNickname($nickname, Profile $profile = null)
    {
        $nickname = Nickname::normalize($nickname);

        // Are there any matching remote groups this profile's in?
        if ($profile instanceof Profile) {
            $group = $profile->getGroups(0, null);
            while ($group instanceof User_group && $group->fetch()) {
                if ($group->nickname == $nickname) {
                    // @fixme is this the best way?
                    return clone($group);
                }
            }
        }

        // If not, check local groups.
        $group = Local_group::getKV('nickname', $nickname);
        if ($group instanceof Local_group) {
            return User_group::getKV('id', $group->group_id);
        }
        $alias = Group_alias::getKV('alias', $nickname);
        if ($alias instanceof Group_alias) {
            return User_group::getKV('id', $alias->group_id);
        }
        return null;
    }

    public function getUserMembers()
    {
        // XXX: cache this

        $user = new User();

        $user->query(sprintf(
            'SELECT id FROM %1$s INNER JOIN group_member ' .
            'ON %1$s.id = group_member.profile_id ' .
            'WHERE group_member.group_id = %2$d ',
            $user->escapedTableName(),
            $this->id
        ));

        $ids = [];

        while ($user->fetch()) {
            $ids[] = $user->id;
        }

        $user->free();

        return $ids;
    }

    public static function maxDescription()
    {
        $desclimit = common_config('group', 'desclimit');
        // null => use global limit (distinct from 0!)
        if (is_null($desclimit)) {
            $desclimit = common_config('site', 'textlimit');
        }
        return $desclimit;
    }

    public static function descriptionTooLong($desc)
    {
        $desclimit = self::maxDescription();
        return ($desclimit > 0 && !empty($desc) && (mb_strlen($desc) > $desclimit));
    }

    public function asAtomEntry($namespace = false, $source = false)
    {
        $xs = new XMLStringer(true);

        if ($namespace) {
            $attrs = array('xmlns' => 'http://www.w3.org/2005/Atom',
                           'xmlns:thr' => 'http://purl.org/syndication/thread/1.0');
        } else {
            $attrs = array();
        }

        $xs->elementStart('entry', $attrs);

        if ($source) {
            $xs->elementStart('source');
            $xs->element('id', null, $this->permalink());
            $xs->element('title', null, $profile->nickname . " - " . common_config('site', 'name'));
            $xs->element('link', array('href' => $this->permalink()));
            $xs->element('updated', null, $this->modified);
            $xs->elementEnd('source');
        }

        $xs->element('title', null, $this->nickname);
        $xs->element('summary', null, common_xml_safe_str($this->description));

        $xs->element('link', array('rel' => 'alternate',
                                   'href' => $this->permalink()));

        $xs->element('id', null, $this->permalink());

        $xs->element('published', null, common_date_w3dtf($this->created));
        $xs->element('updated', null, common_date_w3dtf($this->modified));

        $xs->element(
            'content',
            array('type' => 'html'),
            common_xml_safe_str($this->description)
        );

        $xs->elementEnd('entry');

        return $xs->getString();
    }

    public function asAtomAuthor()
    {
        $xs = new XMLStringer(true);

        $xs->elementStart('author');
        $xs->element('name', null, $this->nickname);
        $xs->element('uri', null, $this->permalink());
        $xs->elementEnd('author');

        return $xs->getString();
    }

    /**
     * Returns an XML string fragment with group information as an
     * Activity Streams noun object with the given element type.
     *
     * Assumes that 'activity', 'georss', and 'poco' namespace has been
     * previously defined.
     *
     * @param string $element one of 'actor', 'subject', 'object', 'target'
     *
     * @return string
     */
    public function asActivityNoun($element)
    {
        $noun = ActivityObject::fromGroup($this);
        return $noun->asString('activity:' . $element);
    }

    public function getAvatar()
    {
        return empty($this->homepage_logo)
            ? User_group::defaultLogo(AVATAR_PROFILE_SIZE)
            : $this->homepage_logo;
    }

    public static function register($fields)
    {
        if (!empty($fields['userid'])) {
            $profile = Profile::getKV('id', $fields['userid']);
            if ($profile && !$profile->hasRight(Right::CREATEGROUP)) {
                common_log(LOG_WARNING, "Attempted group creation from banned user: " . $profile->nickname);

                // TRANS: Client exception thrown when a user tries to create a group while banned.
                throw new ClientException(_('You are not allowed to create groups on this site.'), 403);
            }
        }

        $fields['nickname'] = Nickname::normalize($fields['nickname']);

        // MAGICALLY put fields into current scope
        // @fixme kill extract(); it makes debugging absurdly hard

        $defaults = [
            'nickname'    => null,
            'fullname'    => null,
            'homepage'    => null,
            'description' => null,
            'location'    => null,
            'uri'         => null,
            'mainpage'    => null,
            'aliases'     => [],
            'userid'      => null,
        ];

        $fields = array_merge($defaults, $fields);

        extract($fields);

        $group = new User_group();

        if (empty($uri)) {
            // fill in later...
            $uri = null;
        }
        if (empty($mainpage)) {
            $mainpage = common_local_url('showgroup', array('nickname' => $nickname));
        }

        // We must create a new, incrementally assigned profile_id
        $profile = new Profile();
        $profile->nickname   = $nickname;
        $profile->fullname   = $fullname;
        $profile->profileurl = $mainpage;
        $profile->homepage   = $homepage;
        $profile->bio        = $description;
        $profile->location   = $location;
        $profile->created    = common_sql_now();

        $group->nickname    = $profile->nickname;
        $group->fullname    = $profile->fullname;
        $group->homepage    = $profile->homepage;
        $group->description = $profile->bio;
        $group->location    = $profile->location;
        $group->mainpage    = $profile->profileurl;
        $group->created     = $profile->created;

        $profile->query('START TRANSACTION');
        $id = $profile->insert();
        if ($id === false) {
            $profile->query('ROLLBACK');
            throw new ServerException(_('Profile insertion failed'));
        }

        $group->profile_id = $id;
        $group->uri        = $uri;

        if (isset($fields['join_policy'])) {
            $group->join_policy = intval($fields['join_policy']);
        } else {
            $group->join_policy = 0;
        }

        if (isset($fields['force_scope'])) {
            $group->force_scope = intval($fields['force_scope']);
        } else {
            $group->force_scope = 0;
        }

        if (Event::handle('StartGroupSave', array(&$group))) {
            $result = $group->insert();

            if ($result === false) {
                common_log_db_error($group, 'INSERT', __FILE__);
                // TRANS: Server exception thrown when creating a group failed.
                throw new ServerException(_('Could not create group.'));
            }

            if (!isset($uri) || empty($uri)) {
                $orig = clone($group);
                $group->uri = common_local_url('groupbyid', array('id' => $group->id));
                $result = $group->update($orig);
                if (!$result) {
                    common_log_db_error($group, 'UPDATE', __FILE__);
                    // TRANS: Server exception thrown when updating a group URI failed.
                    throw new ServerException(_('Could not set group URI.'));
                }
            }

            $result = $group->setAliases($aliases);

            if (!$result) {
                // TRANS: Server exception thrown when creating group aliases failed.
                throw new ServerException(_('Could not create aliases.'));
            }

            $member = new Group_member();

            $member->group_id   = $group->id;
            $member->profile_id = $userid;
            $member->is_admin   = true;
            $member->created    = $group->created;

            $result = $member->insert();

            if (!$result) {
                common_log_db_error($member, 'INSERT', __FILE__);
                // TRANS: Server exception thrown when setting group membership failed.
                throw new ServerException(_('Could not set group membership.'));
            }

            self::blow('profile:groups:%d', $userid);
            
            if ($local) {
                $local_group = new Local_group();

                $local_group->group_id = $group->id;
                $local_group->nickname = $nickname;
                $local_group->created  = common_sql_now();

                $result = $local_group->insert();

                if (!$result) {
                    common_log_db_error($local_group, 'INSERT', __FILE__);
                    // TRANS: Server exception thrown when saving local group information failed.
                    throw new ServerException(_('Could not save local group info.'));
                }
            }

            Event::handle('EndGroupSave', array($group));
        }

        $profile->query('COMMIT');

        return $group;
    }

    /**
     * Handle cascading deletion, on the model of notice and profile.
     *
     * This should handle freeing up cached entries for the group's
     * id, nickname, URI, and aliases. There may be other areas that
     * are not de-cached in the UI, including the sidebar lists on
     * GroupsAction
     */
    public function delete($useWhere = false)
    {
        if (empty($this->id)) {
            common_log(LOG_WARNING, "Ambiguous User_group->delete(); skipping related tables.");
            return parent::delete($useWhere);
        }

        // Safe to delete in bulk for now

        $related = array('Group_inbox',
                         'Group_block',
                         'Group_member',
                         'Related_group');

        Event::handle('UserGroupDeleteRelated', array($this, &$related));

        foreach ($related as $cls) {
            $inst = new $cls();
            $inst->group_id = $this->id;

            if ($inst->find()) {
                while ($inst->fetch()) {
                    $dup = clone($inst);
                    $dup->delete();
                }
            }
        }

        // And related groups in the other direction...
        $inst = new Related_group();
        $inst->related_group_id = $this->id;
        $inst->delete();

        // Aliases and the local_group entry need to be cleared explicitly
        // or we'll miss clearing some cache keys; that can make it hard
        // to create a new group with one of those names or aliases.
        $this->setAliases(array());

        // $this->isLocal() but we're using the resulting object
        $local = Local_group::getKV('group_id', $this->id);
        if ($local instanceof Local_group) {
            $local->delete();
        }

        $result = parent::delete($useWhere);

        try {
            $profile = $this->getProfile();
            $profile->delete();
        } catch (GroupNoProfileException $unp) {
            common_log(
                LOG_INFO,
                "Group {$this->nickname} has no profile; continuing deletion."
            );
        }

        // blow the cached ids
        self::blow('user_group:notice_ids:%d', $this->id);

        return $result;
    }

    public function update($dataObject=false)
    {
        // Whenever the User_group is updated, find the Local_group
        // and update its nickname too.
        if ($this->nickname != $dataObject->nickname) {
            $local = Local_group::getKV('group_id', $this->id);
            if ($local instanceof Local_group) {
                common_debug("Updating Local_group ({$this->id}) nickname from {$dataObject->nickname} to {$this->nickname}");
                $local->setNickname($this->nickname);
            }
        }

        // Also make sure the Profile table is up to date!
        $fields = array(/*group field => profile field*/
                    'nickname'      => 'nickname',
                    'fullname'      => 'fullname',
                    'mainpage'      => 'profileurl',
                    'homepage'      => 'homepage',
                    'description'   => 'bio',
                    'location'      => 'location',
                    'created'       => 'created',
                    'modified'      => 'modified',
                    );
        $profile = $this->getProfile();
        $origpro = clone($profile);
        foreach ($fields as $gf=>$pf) {
            $profile->$pf = $this->$gf;
        }
        if ($profile->update($origpro) === false) {
            throw new ServerException(_('Unable to update profile'));
        }

        return parent::update($dataObject);
    }

    public function isPrivate()
    {
        return ($this->join_policy == self::JOIN_POLICY_MODERATE &&
                intval($this->force_scope) === 1);
    }

    public function isLocal()
    {
        $local = Local_group::getKV('group_id', $this->id);
        return ($local instanceof Local_group);
    }

    public static function groupsFromText($text, Profile $profile)
    {
        $groups = array();

        /* extract all !group */
        $count = preg_match_all(
            '/(?:^|\s)!(' . Nickname::DISPLAY_FMT . ')/',
            strtolower($text),
            $match
        );

        if (!$count) {
            return $groups;
        }

        foreach (array_unique($match[1]) as $nickname) {
            $group = self::getForNickname($nickname, $profile);
            if ($group instanceof User_group && $profile->isMember($group)) {
                $groups[] = clone($group);
            }
        }

        return $groups;
    }

    public static function idsFromText($text, Profile $profile)
    {
        $ids = array();
        $groups = self::groupsFromText($text, $profile);
        foreach ($groups as $group) {
            $ids[$group->id] = true;
        }
        return array_keys($ids);
    }
}
