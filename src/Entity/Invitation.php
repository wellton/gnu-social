<?php

/* {{{ License
 * This file is part of GNU social - https://www.gnu.org/software/social
 *
 * GNU social is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * GNU social is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
 }}} */

namespace App\Entity;

/**
 * Entity for user invitations
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Invitation
{
    // AUTOCODE BEGIN

    // AUTOCODE END

    public static function schemaDef(): array
    {
        return [
            'name' => 'invitation',

            'fields' => [
                'code'               => ['type' => 'varchar', 'length' => 32, 'not null' => true, 'description' => 'random code for an invitation'],
                'user_id'            => ['type' => 'int', 'not null' => true, 'description' => 'who sent the invitation'],
                'address'            => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'invitation sent to'],
                'address_type'       => ['type' => 'varchar', 'length' => 8, 'not null' => true, 'description' => 'address type ("email", "xmpp", "sms")'],
                'created'            => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'registered_user_id' => ['type' => 'int', 'not null' => false, 'description' => 'if the invitation is converted, who the new user is'],
            ],
            'primary key'  => ['code'],
            'foreign keys' => [
                'invitation_user_id_fkey'            => ['user', ['user_id' => 'id']],
                'invitation_registered_user_id_fkey' => ['user', ['registered_user_id' => 'id']],
            ],
            'indexes' => [
                'invitation_address_idx'            => ['address', 'address_type'],
                'invitation_user_id_idx'            => ['user_id'],
                'invitation_registered_user_id_idx' => ['registered_user_id'],
            ],
        ];
    }
}
