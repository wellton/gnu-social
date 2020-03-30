<?php

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/soci
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as publ
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public Li
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
// }}}

namespace App\Entity;

/**
 * Entity for Group Alias
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
class GroupAlias
{
    // {{{ Autocode

    private string $alias;
    private int $group_id;
    private DateTime $modified;

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }
    public function getAlias(): string
    {
        return $this->alias;
    }

    public function setGroupId(int $group_id): self
    {
        $this->group_id = $group_id;
        return $this;
    }
    public function getGroupId(): int
    {
        return $this->group_id;
    }

    public function setModified(DateTime $modified): self
    {
        $this->modified = $modified;
        return $this;
    }
    public function getModified(): DateTime
    {
        return $this->modified;
    }

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'   => 'group_alias',
            'fields' => [
                'alias'    => ['type' => 'varchar', 'length' => 64, 'not null' => true, 'description' => 'additional nickname for the group'],
                'group_id' => ['type' => 'int', 'not null' => true, 'description' => 'group profile is blocked from'],
                'modified' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date alias was created'],
            ],
            'primary key'  => ['alias'],
            'foreign keys' => [
                'group_alias_group_id_fkey' => ['user_group', ['group_id' => 'id']],
            ],
            'indexes' => [
                'group_alias_group_id_idx' => ['group_id'],
            ],
        ];
    }
}