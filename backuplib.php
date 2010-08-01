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
 * This php script contains all the stuff to backup/restore consultation mods
 *
 * @package    mod
 * @subpackage consultation
 * @copyright  2009 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

//This is the "graphical" structure of the consultation mod:
//
//                   consultation
//                   (CL, pk->id)
//                        |
//                        |
//                        |
//               consultation_inquiries
//            (UL, pk->id, fk->consultationid)
//                        |
//                        |
//                        |
//                 consultation_posts
//     (UL, pk->id, fk->inquiryid, files->attachment)
//
// Meaning: pk->primary key field of the table
//          fk->foreign key to link with parent
//          CL->course level info
//          UL->user level info
//          files->table may have files
//
//-----------------------------------------------------------

function consultation_backup_mods($bf, $preferences) {
    global $CFG;

    $status = true;

    //Iterate over consultation table
    $consultations = get_records ('consultation', 'course', $preferences->backup_course, 'id');
    if ($consultations) {
        foreach ($consultations as $consultation) {
            $status = $status && consultation_backup_one_mod($bf, $preferences, $consultation);
        }
    }

    return $status;
}

function consultation_backup_one_mod($bf, $preferences, $consultation) {

    if (is_numeric($consultation)) {
        $consultation = get_record('consultation', 'id', $consultation);
    }

    $status = true;

    //Start mod
    $status = $status && fwrite($bf, start_tag('MOD', 3, true));
    //Print consultation data
    $status = $status && fwrite($bf, full_tag('ID', 4, false, $consultation->id));
    $status = $status && fwrite($bf, full_tag('MODTYPE', 4, false, 'consultation'));
    $status = $status && fwrite($bf, full_tag('NAME', 4, false, $consultation->name));
    $status = $status && fwrite($bf, full_tag('INTRO', 4, false, $consultation->intro));
    $status = $status && fwrite($bf, full_tag('INTROFORMAT', 4, false, $consultation->introformat));
    $status = $status && fwrite($bf, full_tag('OPENLIMIT', 4, false, $consultation->openlimit));
    $status = $status && fwrite($bf, full_tag('DELETEAFTER', 4, false, $consultation->deleteafter));
    $status = $status && fwrite($bf, full_tag('EDITTIME', 4, false, $consultation->edittime));
    $status = $status && fwrite($bf, full_tag('TIMEMODIFIED', 4, false, $consultation->timemodified));

    //if we've selected to backup users info, then execute backup_consultation_inquiries
    if (backup_userdata_selected($preferences, 'consultation', $consultation->id)) {
        $status = $status && backup_consultation_inquiries($bf, $preferences, $consultation);
    }

    //End mod
    $status = $status && fwrite($bf, end_tag('MOD', 3, true));

    return $status;
}

/**
 * Backup consultation_inquiries contents, executed from consultation_backup_mods()
 */
function backup_consultation_inquiries($bf, $preferences, $consultation) {
    global $CFG;

    $status = true;

    $consultation_inquiries = get_records('consultation_inquiries', 'consultationid', $consultation->id, 'id');
    //If there is inquiries
    if ($consultation_inquiries) {
        //Write start tag
        $status = $status && fwrite($bf, start_tag('INQUIRIES', 4, true));
        //Iterate over each entry
        foreach ($consultation_inquiries as $inquiry) {
            //Start entry
            $status = $status && fwrite($bf, start_tag('INQUIRY', 5, true));
            //Print consultation_entries contents
            $status = $status && fwrite($bf, full_tag('ID', 6, false, $inquiry->id));
            $status = $status && fwrite($bf, full_tag('USERFROM', 6, false, $inquiry->userfrom));
            $status = $status && fwrite($bf, full_tag('USERTO', 6, false, $inquiry->userto));
            $status = $status && fwrite($bf, full_tag('SUBJECT', 6, false, $inquiry->subject));
            $status = $status && fwrite($bf, full_tag('RESOLVED', 6, false, $inquiry->resolved));
            $status = $status && fwrite($bf, full_tag('TIMECREATED', 6, false, $inquiry->timecreated));
            $status = $status && fwrite($bf, full_tag('TIMEMODIFIED', 6, false, $inquiry->timemodified));

            $status = $status && backup_consultation_posts($bf, $preferences, $inquiry, $consultation);

            //End entry
            $status = $status && fwrite($bf, end_tag('INQUIRY', 5, true));
         }

        //Write end tag
        $status = $status && fwrite($bf, end_tag('INQUIRIES', 4, true));
    }
    return $status;
}

/**
 * Backup consultation_inquiries contents, executed from backup_consultation_inquiries()
 */
function backup_consultation_posts($bf, $preferences, $inquiry, $consultation) {
    global $CFG;

    $status = true;

    if (!$consultation_posts = get_records('consultation_posts', 'inquiryid', $inquiry->id, 'id')) {
        // there must be posts
        return false;
    }

    //Write start tag
    $status = $status && fwrite($bf, start_tag('POSTS', 4, true));

    //Iterate over each post
    foreach ($consultation_posts as $post) {
        if ($status and $post->attachment) {
            $file = "$CFG->dataroot/$consultation->course/$CFG->moddata/consultation/$consultation->id/$post->id/$post->attachment";
            if (is_readable($file)) {
                $target = "$CFG->dataroot/temp/backup/$preferences->backup_unique_code/moddata/consultation/$consultation->id/$post->id";
                check_dir_exists($target, true, true);
                $status = $status && backup_copy_file($file, "$target/$post->attachment");
            } else {
                // somebody messed with files directly :-(
                $post->attachment = '';
            }
        }
        //Start post
        $status = $status && fwrite($bf, start_tag('POST', 5, true));
        //Print consultation_posts contents
        $status = $status && fwrite($bf, full_tag('ID', 6, false, $post->id));
        $status = $status && fwrite($bf, full_tag('USERID', 6, false, $post->userid));
        $status = $status && fwrite($bf, full_tag('MESSAGE', 6, false, $post->message));
        $status = $status && fwrite($bf, full_tag('MESSAGEFORMAT', 6, false, $post->messageformat));
        $status = $status && fwrite($bf, full_tag('ATTACHMENT', 6, false, $post->attachment));
        $status = $status && fwrite($bf, full_tag('NOTIFIED', 6, false, $post->notified));
        $status = $status && fwrite($bf, full_tag('SEENON', 6, false, $post->seenon));
        $status = $status && fwrite($bf, full_tag('TIMECREATED', 6, false, $post->timecreated));
        $status = $status && fwrite($bf, full_tag('TIMEMODIFIED', 6, false, $post->timemodified));
        //End post
        $status = $status && fwrite($bf, end_tag('POST', 5, true));
    }

    //Write end tag
    $status = $status && fwrite($bf, end_tag('POSTS', 4, true));

    return $status;
}

/**
 * Return a content encoded to support interactivities linking. Every module
 */
function consultation_encode_content_links ($content, $preferences) {
    global $CFG;

    $base = preg_quote($CFG->wwwroot, "/");

    //Link to the list of consultations
    $buscar="/(".$base."\/mod\/consultation\/index.php\?id\=)([0-9]+)/";
    $result= preg_replace($buscar,'$@consultationINDEX*$2@$', $content);

    //Link to consultation view by moduleid
    $buscar="/(".$base."\/mod\/consultation\/view.php\?id\=)([0-9]+)/";
    $result= preg_replace($buscar,'$@consultationVIEWBYID*$2@$', $result);

    //Link to consultation view by consultationid
    $buscar="/(".$base."\/mod\/consultation\/view.php\?c\=)([0-9]+)/";
    $result= preg_replace($buscar,'$@consultationVIEWBYD*$2@$', $result);

    return $result;
}

/**
 * Returns details module counts
 * @param $course
 * @param $user_data
 * @param $backup_unique_code
 * @param $instances
 * @return unknown_type
 */
function consultation_check_backup_mods($courseid, $user_data=false, $backup_unique_code, $instances=null) {
    global $CFG;

    if ($instances) {
        return consultation_check_backup_mods_instances($courseid, $user_data, $instances);
    }

    //First the course data
    $info[0][0] = get_string('modulenameplural', 'consultation');
    $info[0][1] = count_records('consultation', 'course', $courseid);

    //Now, if requested, the user_data
    if ($user_data) {
        $info[1][0] = get_string('inquiries', 'consultation');
        $info[1][1] = count_record_sql("SELECT COUNT('x')
                                          FROM {$CFG->prefix}consultation_inquiries c
                                          JOIN {$CFG->prefix}consultation d ON d.id = c.consultationid
                                         WHERE d.course=$courseid");
        $info[2][0] = get_string('entries');
        $info[2][1] = count_record_sql("SELECT COUNT('x')
                                          FROM {$CFG->prefix}consultation_posts p
                                          JOIN {$CFG->prefix}consultation_inquiries c ON c.id = p.inquiryid
                                          JOIN {$CFG->prefix}consultation d ON d.id = c.consultationid
                                         WHERE d.course=$courseid");
    }

    return $info;
}

function consultation_check_backup_mods_instances($courseid, $user_data, $consultations) {
    global $CFG;

    $info = array();

    $consultationids = implode(',', array_keys($consultations));

    $sql = "SELECT d.id, d.name, (SELECT COUNT('x')
                                    FROM {$CFG->prefix}consultation_inquiries c1
                                   WHERE c1.consultationid = d.id) AS convcount,
                                 (SELECT COUNT('x')
                                    FROM {$CFG->prefix}consultation_posts p2
                                    JOIN {$CFG->prefix}consultation_inquiries c2 ON c2.id = p2.inquiryid
                                   WHERE c2.consultationid = d.id) AS postcount
              FROM {$CFG->prefix}consultation d
             WHERE d.course=$courseid AND d.id IN ($consultationids)";

    if ($ds = get_records_sql($sql)) {
        foreach ($ds as $id=>$consultation) {
            $info[$id.'0'][0] = '<b>'.$consultation->name.'</b>';
            $info[$id.'0'][1] = '';
            if ($user_data) {
                $info[$id.'1'][0] = get_string('inquiries', 'consultation');
                $info[$id.'1'][1] = $consultation->convcount;
                $info[$id.'2'][0] = get_string('entries');
                $info[$id.'2'][1] = $consultation->postcount;
            }
        }
    }

    return $info;
}

