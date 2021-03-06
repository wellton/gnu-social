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
 * Conversation tree list item
 *
 * Special class of NoticeListItem for use inside conversation trees.
 *
 * @category  Widget
 * @package   ConversationTreePlugin
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ConversationTreeItem extends NoticeListItem
{
    /**
     * start a single notice.
     *
     * The default creates the <li>; we skip, since the ConversationTree
     * takes care of that.
     *
     * @return void
     */
    public function showStart(): void
    {
        return;
    }

    /**
     * finish the notice
     *
     * The default closes the <li>; we skip, since the ConversationTree
     * takes care of that.
     *
     * @return void
     */
    public function showEnd(): void
    {
        return;
    }

    /**
     * show people this notice is in reply to
     *
     * Tree context shows this, so we skip it.
     *
     * @return void
     */
    public function showAddressees(): void
    {
        return;
    }
}
