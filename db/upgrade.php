<?php

// This file is part of Consultation module for Moodle.
//
// Consultation is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Consultation is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Consultation.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrade file.
 *
 * @package    mod
 * @subpackage consultation
 * @copyright  2009 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_consultation_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    if ($oldversion < 2009080200) {
    /// Define field notify to be added to consultation
        $table = new xmldb_table('consultation');
        $field = new xmldb_field('notify');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'edittime');

    /// Launch add field notify
        $dbman->add_field($table, $field);

        upgrade_mod_savepoint(true, 2009080200, 'consultation');
    }

    // === 1.9.x upgrade line === //

    if ($oldversion < 2010101500) {

        /////////////////////////////////////
        /// new file storage upgrade code ///
        /////////////////////////////////////

        $fs = get_file_storage();

        $empty = $DB->sql_empty(); // silly oracle empty string handling workaround

        $sqlfrom = "FROM {consultation_posts} P
                    JOIN {consultation_inquiries} i ON i.id = p.inquiryid
                    JOIN {consultation} c ON c.id = i.consultationid
                    JOIN {modules} m ON m.name = 'consultation'
                    JOIN {course_modules} cm ON (cm.module = m.id AND cm.instance = c.id)
                   WHERE p.attachment <> '$empty'";

        $count = $DB->count_records_sql("SELECT COUNT('x') $sqlfrom");

        if ($rs = $DB->get_recordset_sql("SELECT p.id, p.attachment, p.userid, i.consultationid, c.course, cm.id AS cmid $sqlfrom ORDER BY c.course, c.id, i.id")) {

            $pbar = new progress_bar('migrateconsultationfiles', 500, true);

            $i = 0;
            foreach ($rs as $post) {
                $i++;
                upgrade_set_timeout(60); // set up timeout, may also abort execution
                $pbar->update($i, $count, "Migrating consultation posts - $i/$count.");

                $filepath = "$CFG->dataroot/$post->course/$CFG->moddata/consultation/$post->consultationid/$post->id/$post->attachment";
                if (!is_readable($filepath)) {
                    //file missing??
                    echo $OUTPUT->notification("File not readable, skipping: ".$filepath);
                    $post->attachment = '';
                    $DB->update_record('consultation_posts', $post);
                    continue;
                }
                $context = get_context_instance(CONTEXT_MODULE, $post->cmid);

                $filename = clean_param($post->attachment, PARAM_FILE);
                if ($filename === '') {
                    echo $OUTPUT->notification("Unsupported attachment filename, skipping: ".$filepath);
                    $post->attachment = '';
                    $DB->update_record('consultation_posts', $post);
                    continue;
                }
                if (!$fs->file_exists($context->id, 'mod_consultation', 'attachment', $post->id, '/', $filename)) {
                    $file_record = array('contextid'=>$context->id, 'component'=>'mod_consultation', 'filearea'=>'attachment', 'itemid'=>$post->id, 'filepath'=>'/', 'filename'=>$filename, 'userid'=>$post->userid);
                    if ($fs->create_file_from_pathname($file_record, $filepath)) {
                        $post->attachment = '';
                        $DB->update_record('consultation_posts', $post);
                        unlink($filepath);
                    }
                }

                // remove dirs if empty
                @rmdir("$CFG->dataroot/$post->course/$CFG->moddata/consultation/$post->consultationid/$post->id");
                @rmdir("$CFG->dataroot/$post->course/$CFG->moddata/consultation/$post->consultationid");
                @rmdir("$CFG->dataroot/$post->course/$CFG->moddata/consultation");
            }
            $rs->close();
        }

        upgrade_mod_savepoint(true, 2010101500, 'consultation');
    }

    if ($oldversion < 2010101501) {

        // Define field attachment to be dropped from consultation_posts
        $table = new xmldb_table('consultation_posts');
        $field = new xmldb_field('attachment');

        // Conditionally launch drop field attachment
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // consultation savepoint reached
        upgrade_mod_savepoint(true, 2010101501, 'consultation');
    }


    return true;
}

