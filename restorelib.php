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

defined('MOODLE_INTERNAL') || die();

/**
 * This function executes all the restore procedure about this mod
 */
function consultation_restore_mods($mod, $restore) {
    global $CFG;

    //Get record from backup_ids
    $data = backup_getid($restore->backup_unique_code, $mod->modtype, $mod->id);

    if ($data) {
        //Now get completed xmlized object
        $info = $data->info;
        //traverse_xmlize($info);                                                               //Debug
        //print_object ($GLOBALS['traverse_array']);                                            //Debug
        //$GLOBALS['traverse_array']="";                                                        //Debug

        //Now, build the consultation record structure
        $consultation->course       = $restore->course_id;
        $consultation->name         = backup_todb($info['MOD']['#']['NAME']['0']['#']);
        $consultation->intro        = backup_todb($info['MOD']['#']['INTRO']['0']['#']);
        $consultation->introformat  = backup_todb($info['MOD']['#']['INTROFORMAT']['0']['#']);
        $consultation->openlimit    = backup_todb($info['MOD']['#']['OPENLIMIT']['0']['#']);
        $consultation->deleteafter  = backup_todb($info['MOD']['#']['DELETEAFTER']['0']['#']);
        $consultation->edittime     = backup_todb($info['MOD']['#']['EDITTIME']['0']['#']);
        $consultation->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

        //The structure is equal to the db, so insert the consultation
        if (!$consultation->id = insert_record ('consultation', $consultation)) {
            return false;
        }

        $consultation->oldid = $mod->id;

        //Do some output
        if (!defined('RESTORE_SILENTLY')) {
            echo '<li>'.get_string('modulename', 'consultation').' "'.$consultation->name.'"</li>';
        }
        backup_flush(300);

        //We have the newid, update backup_ids
        backup_putid($restore->backup_unique_code, $mod->modtype, $consultation->oldid, $consultation->id);
        //Now check if want to restore user data and do it.
        if (restore_userdata_selected($restore, 'consultation', $mod->id)) {
            //Restore consultation_inquiries
            if (!consultation_inquiries_restore($consultation, $info, $restore)) {
                return false;
            }
        }
    }

    return true;
}


/**
 * This function restores the consultation_inquiries
 */
function consultation_inquiries_restore($consultation, $info, $restore) {
    global $CFG;

    if (!empty($info['MOD']['#']['INQUIRIES']['0']['#']['INQUIRY'])) {
        //Get the entries array
        $inquiries = $info['MOD']['#']['INQUIRIES']['0']['#']['INQUIRY'];

        //Iterate over inquiries
        $i = 0;
        foreach($inquiries as $inquiry_info) {
            $i++;
            //traverse_xmlize($inquiry_info);                                  //Debug
            //print_object ($GLOBALS['traverse_array']);                            //Debug
            //$GLOBALS['traverse_array']="";                                        //Debug

            //Now, build the consultation_POSTS record structure
            $inquiry->consultationid = $consultation->id;
            $inquiry->userfrom       = backup_todb($inquiry_info['#']['USERFROM']['0']['#']);
            $inquiry->userto         = backup_todb($inquiry_info['#']['USERTO']['0']['#']);
            $inquiry->subject        = backup_todb($inquiry_info['#']['SUBJECT']['0']['#']);
            $inquiry->resolved       = backup_todb($inquiry_info['#']['RESOLVED']['0']['#']);
            $inquiry->timecreated    = backup_todb($inquiry_info['#']['TIMECREATED']['0']['#']);
            $inquiry->timemodified   = backup_todb($inquiry_info['#']['TIMEMODIFIED']['0']['#']);

            //We have to recode the userto and userfrom fields
            if (!$user = backup_getid($restore->backup_unique_code, 'user', $inquiry->userfrom)) {
                // incomplete, do not restore
                continue;
            }
            $inquiry->userfrom = $user->new_id;
            if (!$user = backup_getid($restore->backup_unique_code, 'user', $inquiry->userto)) {
                // incomplete, do not restore
                continue;
            }
            $inquiry->userto = $user->new_id;

            //The structure is equal to the db, so insert the consultation_inquiry
            if (!$inquiry->id = insert_record ('consultation_inquiries', $inquiry)) {
                return false;
            }

            $inquiry->oldid = backup_todb($inquiry_info['#']['ID']['0']['#']);

            //We have the newid, update backup_ids
            backup_putid($restore->backup_unique_code, 'consultation_inquiries', $inquiry->oldid, $inquiry->id);

            //Restore posts
            if (!consultation_posts_restore($consultation, $inquiry, $inquiry_info, $restore)) {
                return false;
            }
        }
    }

    return true;
}

/**
 * This function restores the consultation_posts
 */
function consultation_posts_restore($consultation, $inquiry, $info, $restore) {
    global $CFG;

    //Get the posts array - alwys at least one post
    $posts = $info['#']['POSTS']['0']['#']['POST'];

    //Iterate over posts
    $i = 0;
    foreach($posts as $post_info) {
        $i++;
        //traverse_xmlize($post_info);                                                       //Debug
        //print_object ($GLOBALS['traverse_array']);                                         //Debug
        //$GLOBALS['traverse_array']="";                                                     //Debug

        //Now, build the consultation_POSTS record structure
        $post->inquiryid = $inquiry->id;
        $post->userid         = backup_todb($post_info['#']['USERID']['0']['#']);
        $post->timecreated    = backup_todb($post_info['#']['TIMECREATED']['0']['#']);
        $post->message        = backup_todb($post_info['#']['MESSAGE']['0']['#']);
        $post->messageformat  = backup_todb($post_info['#']['MESSAGEFORMAT']['0']['#']);
        $post->attachment     = backup_todb($post_info['#']['ATTACHMENT']['0']['#']);
        $post->notified       = backup_todb($post_info['#']['NOTIFIED']['0']['#']);
        $post->seenon         = backup_todb($post_info['#']['SEENON']['0']['#']);
        $post->timecreated    = backup_todb($post_info['#']['TIMECREATED']['0']['#']);
        $post->timemodified   = backup_todb($post_info['#']['TIMEMODIFIED']['0']['#']);

        //We have to recode the userid field
        if (!$user = backup_getid($restore->backup_unique_code, 'user', $post->userid)) {
            // bad luck - unknown user interrupted consultation - ignore
            continue;
        }
        $post->userid = $user->new_id;

        //The structure is equal to the db, so insert the consultation_posts
        if (!$post->id = insert_record ('consultation_posts', $post)) {
            return false;
        }

        $post->oldid = backup_todb($post_info['#']['ID']['0']['#']);

        if ($post->attachment) {
            $temp_path = "$CFG->dataroot/temp/backup/$restore->backup_unique_code/moddata/consultation/$consultation->oldid/$post->oldid/$post->attachment";
            $newdir = "$CFG->dataroot/$consultation->course/$CFG->moddata/consultation/$consultation->id/$post->id";
            check_dir_exists($newdir, true, true);
            backup_copy_file($temp_path, "$newdir/$post->attachment");
        }

        //Do some output
        if ($i % 50 == 0) {
            echo '.';
            if ($i % 1000 == 0) {
                echo '<br />';
            }
            backup_flush(300);
        }

        //We have the newid, update backup_ids
        backup_putid($restore->backup_unique_code, 'consultation_posts', $post->oldid, $post->id);
    }

    return true;
}

/**
 * Return a content decoded to support interactivities linking.
 */
function consultation_decode_content_links($content, $restore) {
    global $CFG;

    $result = $content;

    //Link to the list of consultations

    $searchstring = '/\$@(CONSULTATIONINDEX)\*([0-9]+)@\$/';
    //We look for it
    preg_match_all($searchstring, $content, $foundset);
    //If found, then we are going to look for its new id (in backup tables)
    if ($foundset[0]) {
        //print_object($foundset);                                     //Debug
        //Iterate over foundset[2]. They are the old_ids
        foreach($foundset[2] as $old_id) {
            //We get the needed variables here (course id)
            $rec = backup_getid($restore->backup_unique_code, 'course', $old_id);
            //Personalize the searchstring
            $searchstring = '/\$@(CONSULTATIONINDEX)\*('.$old_id.')@\$/';
            //If it is a link to this course, update the link to its new location
            if($rec->new_id) {
                //Now replace it
                $result = preg_replace($searchstring, $CFG->wwwroot.'/mod/consultation/index.php?id='.$rec->new_id, $result);
            } else {
                //It's a foreign link so leave it as original
                $result = preg_replace($searchstring, $restore->original_wwwroot.'/mod/consultation/index.php?id='.$old_id, $result);
            }
        }
    }

    //Link to consultation view by moduleid

    $searchstring = '/\$@(CONSULTATIONVIEWBYID)\*([0-9]+)@\$/';
    //We look for it
    preg_match_all($searchstring, $result, $foundset);
    //If found, then we are going to look for its new id (in backup tables)
    if ($foundset[0]) {
        //print_object($foundset);                                     //Debug
        //Iterate over foundset[2]. They are the old_ids
        foreach($foundset[2] as $old_id) {
            //We get the needed variables here (course_modules id)
            $rec = backup_getid($restore->backup_unique_code, 'course_modules', $old_id);
            //Personalize the searchstring
            $searchstring = '/\$@(CONSULTATIONVIEWBYID)\*('.$old_id.')@\$/';
            //If it is a link to this course, update the link to its new location
            if($rec->new_id) {
                //Now replace it
                $result = preg_replace($searchstring, $CFG->wwwroot.'/mod/consultation/view.php?id='.$rec->new_id, $result);
            } else {
                //It's a foreign link so leave it as original
                $result = preg_replace($searchstring, $restore->original_wwwroot.'/mod/consultation/view.php?id='.$old_id, $result);
            }
        }
    }

    //Link to consultation view by consultationid

    $searchstring = '/\$@(CONSULTATIONVIEWBYD)\*([0-9]+)@\$/';
    //We look for it
    preg_match_all($searchstring, $result, $foundset);
    //If found, then we are going to look for its new id (in backup tables)
    if ($foundset[0]) {
        //print_object($foundset);                                     //Debug
        //Iterate over foundset[2]. They are the old_ids
        foreach($foundset[2] as $old_id) {
            //We get the needed variables here (consultation id)
            $rec = backup_getid($restore->backup_unique_code, 'consultation', $old_id);
            //Personalize the searchstring
            $searchstring='/\$@(CONSULTATIONVIEWBYF)\*('.$old_id.')@\$/';
            //If it is a link to this course, update the link to its new location
            if($rec->new_id) {
                //Now replace it
                $result = preg_replace($searchstring, $CFG->wwwroot.'/mod/consultation/view.php?c='.$rec->new_id, $result);
            } else {
                //It's a foreign link so leave it as original
                $result = preg_replace($searchstring, $restore->original_wwwroot.'/mod/consultation/view.php?c='.$old_id, $result);
            }
        }
    }

    return $result;
}

/**
 * This function makes all the necessary calls to xxxx_decode_content_links()
 * function in each module, passing them the desired contents to be decoded
 * from backup format to destination site/course in order to mantain inter-activities
 * working in the backup/restore process. It's called from restore_decode_content_links()
 * function in restore process
 */
function consultation_decode_content_links_caller($restore) {
    global $CFG;
    $status = true;

    //Process every POST (message) in the course
    if ($rs = get_recordset_sql("SELECT p.id, p.message
                                   FROM {$CFG->prefix}consultation_posts p
                                   JOIN {$CFG->prefix}consultation_inquiries c ON c.id = p.inquiryid
                                   JOIN {$CFG->prefix}consultation d ON d.id = c.consultationid
                                  WHERE d.course = $restore->course_id")) {
        //Iterate over each post->message
        $i = 0;   //Counter to send some output to the browser to avoid timeouts
        while ($post = rs_fetch_next_record($rs)) {
            //Increment counter
            $i++;
            $content = $post->message;
            $result = restore_decode_content_links_worker($content, $restore);
            if ($result !== $content) {
                //Update record
                $post->message = addslashes($result);
                $status = update_record('consultation_posts', $post);
                if (debugging()) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                    }
                }
            }
            //Do some output
            if ($i % 5 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo '.';
                    if ($i % 100 == 0) {
                        echo '<br />';
                    }
                }
                backup_flush(300);
            }
        }
        rs_close($rs);
    }

    //Process every CONSULTATION (intro) in the course
    if ($rs = get_recordset_sql("SELECT id, intro
                                   FROM {$CFG->prefix}consultation
                                  WHERE course = $restore->course_id")) {
        //Iterate over each consultation->intro
        $i = 0;   //Counter to send some output to the browser to avoid timeouts
        while ($consultation = rs_fetch_next_record($rs)) {
            //Increment counter
            $i++;
            $content = $consultation->intro;
            $result = restore_decode_content_links_worker($content,$restore);
            if ($result != $content) {
                //Update record
                $consultation->intro = addslashes($result);
                $status = update_record('consultation', $consultation);
                if (debugging()) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                    }
                }
            }
            //Do some output
            if ($i % 5 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo '.';
                    if ($i % 100 == 0) {
                        echo '<br />';
                    }
                }
                backup_flush(300);
            }
        }
        rs_close($rs);
    }

    return $status;
}
