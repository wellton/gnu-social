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
 * EmbedPlugin implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Mikael Nordfeldth
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

define('INSTALLDIR', dirname(__DIR__, 3));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

$longoptions = ['dry-run', 'h-bug', 'broken-oembed', 'limit='];

$helptext = <<<END_OF_USERROLE_HELP
fixup_files.php [options]
Patches attachments with broken oembed.

     --dry-run       look but don't touch
     --h-bug         Patches up file entries with corrupted types and titles (the "h bug")
     --broken-oembed Attempts refecting info for broken attachments
     --limit [date]  Only affect files from after this date. This is a timestamp, format is: yyyy-mm-dd (optional time hh:mm:ss may be provided)
END_OF_USERROLE_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

$dry = have_option('dry-run');
$h_bug = have_option('h-bug');
$broken = have_option('broken-oembed');
$limit = get_option_value('limit');

if (!($broken ^ $h_bug)) {
    echo "Exactly one of --h-bug and --broken-oembed are required\n";
    die();
}

$fn = new DB_DataObject();

$query = <<<'END'
    SELECT file_to_post.file_id
      FROM file_to_post
      INNER JOIN file ON file_to_post.file_id = file.id
      INNER JOIN notice ON file_to_post.post_id = notice.id
      WHERE
    END;

if ($h_bug) {
    $query .= <<<'END'
              file.title = 'h'
          AND file.mimetype = 'h'
          AND file.size = 0
          AND file.protected = 0
        END;
} elseif ($broken) {
    $query .= ' file.filename IS NULL';
}

if (!empty($limit)) {
    $query .= " AND notice.modified >= '{$fn->escape($limit)}'";
}
$query .= ' GROUP BY file_to_post.file_id ORDER BY MAX(notice.modified)';

$fn->query($query);

if ($h_bug) {
    echo "Found {$fn->N} bad items:\n";
} else {
    echo "Found {$fn->N} files.\n";
}

while ($fn->fetch()) {
    $f = File::getByID($fn->file_id);

    try {
        $data = File_embed::getByFile($f);
    } catch (Exception $e) {
        // Carry on
    }

    if ($broken && $data instanceof File_embed) {
        try {
            $thumb = File_thumbnail::byFile($f, true /* not null url */);
            $thumb->getPath(); // Check we have the file
        } catch (Exception $e) {
            $no_thumb = true;
            // Doesn't exist, no problem
        }
    }


    if ($h_bug) {
        echo "ID: {$f->id}, URL {$f->url}";

        if ($dry) {
            if ($data instanceof File_embed) {
                echo " (unchanged)\n";
            } else {
                echo " (unchanged, but embedding lookup failed)\n";
            }
        } elseif (!$dry) {
            $f->query(sprintf(
                <<<'END'
                UPDATE file
                  SET mimetype = NULL, title = NULL, size = NULL,
                      protected = NULL, modified = CURRENT_TIMESTAMP
                  WHERE id = %d;
                END,
                $f->getID()
            ));
            $f->decache();
            if ($data instanceof File_embed) {
                $fetch = true;
                echo " (ok)\n";
            } else {
                echo " (ok, but embedding lookup failed)\n";
            }
        }
    } elseif (
        $broken
        && (
            !($data instanceof File_embed)
            || empty($data->title)
            || empty($f->title)
            || ($thumb instanceof File_thumbnail && empty($thumb->filename))
        )
    ) {
        // print_r($thumb);

        if (!$dry) {
            echo "Will refetch for file with ";
        } else {
            echo "Found broken file with ";
        }

        echo "ID: {$f->getID()}, URL {$f->url}\n";
        if (!$dry) {
            $fetch = true;
            $f->query(sprintf(
                <<<'END'
                UPDATE file
                  SET title = NULL, size = NULL,
                      protected = NULL, modified = CURRENT_TIMESTAMP
                  WHERE id = %d;
                END,
                $f->getID()
            ));
            $f->decache();

            if ($data instanceof File_embed) {
                $data->delete();
                $data->decache();
            }

            if ($thumb instanceof File_thumbnail) {
                // Delete all thumbnails, not just this one
                $f->query("DELETE FROM file_thumbnail WHERE file_id = {$f->getID()}");
                $thumb->decache();
            }
        }
    }

    if (isset($fetch) && $fetch === true && !$dry) {
        $fetch = false;
        echo "Attempting to fetch Embed data\n";
        Event::handle('EndFileSaveNew', array($f));
    }
}

echo "Done.\n";
