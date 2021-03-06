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
 * Mandatory module api - standard module functions
 *
 * @package    mod
 * @subpackage consultation
 * @copyright  2009 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 * @param object $data (with magic quotes)
 * @return mixed integer id if success, string if error
 */
function consultation_add_instance($data) {
    global $DB;

    $data->timemodified = time();
    return $DB->insert_record('consultation', $data);
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 * @param object $data (with magic quotes)
 * @return bool success
 */
function consultation_update_instance($data) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;
    return $DB->update_record('consultation', $data);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 * @param int $id
 * @return bool success
 */
function consultation_delete_instance($id) {
    global $DB;

    if (!$consultation = $DB->get_record('consultation', array('id'=>$id))) {
        return false;
    }

    $DB->delete_records_select('consultation_posts', "inquiryid IN (SELECT c.id
                                                                      FROM {consultation_inquiries} c
                                                                     WHERE c.consultationid = ?)", array($consultation->id));
    $DB->delete_records('consultation_inquiries', array('consultationid'=>$consultation->id));
    $DB->delete_records('consultation', array('id'=>$consultation->id));

    // file are deleted automatically together with the context

    return true;
}


/**
 * Execute cron actions.
 * @return bool success
 */
function consultation_cron() {
    global $DB;

    $now = time();

    $mid = $DB->get_field('modules', 'id', array('name'=>'consultation'));

/// delete old resolved consultations
    $sql = "SELECT ci.id, c.id AS cid, c.course, cm.id AS cmid
              FROM {consultation_inquiries} ci
              JOIN {consultation} c ON c.id = ci.consultationid
              JOIN {course_modules} cm ON (cm.instance = c.id AND cm.module = :mid)
             WHERE ci.resolved = 1 AND c.deleteafter <> 0 AND ci.timemodified < $now - (c.deleteafter*60*60*24)";

    $fs = get_file_storage();
    $rs = $DB->get_recordset_sql($sql, array('mid'=>$mid));
    foreach ($rs as $inquiry) {
        $context = get_context_instance(CONTEXT_MODULE, $inquiry->cmid);
        $posts = $DB->get_records('consultation_posts', array('inquiryid' => $inquiry->id, 'attachment' => ''), 'id', 'id');
        foreach ($posts as $post) {
            $fs->delete_area_files($context->id, 'mod_consultation', 'attachment', $post->id);
        }
        $DB->delete_records('consultation_posts', array('inquiryid'=>$inquiry->id));
        $DB->delete_records('consultation_inquiries', array('id'=>$inquiry->id));
    }
    $rs->close();

    return true;
}

/**
 * Returns the users with data in one consultation,
 * used from backup code.
 * @param int $consultationid
 * @return array
 */
function consultation_get_participants($consultationid) {
    global $DB;

    $sql = "SELECT DISTINCT u.id, u.id
              FROM {user} u,
                   {consultation_posts} p
              JOIN {consultation_inquiries} c ON (c.id = p.inquiryid AND c.consultationid = ?)
             WHERE u.id = c.userfrom OR u.id = c.userto OR u.id = p.userid";

    return $DB->get_records_sql($sql, array($consultationid));
}


/**
 * This function returns if a scale is being used
 * @param int $consultationid
 * @param int $scaleid negative number
 * @return bool true if the scale is used by this consultation
 */
function consultation_scale_used($consultationid, $scaleid) {
    return false;
}

/**
 * Checks if scale is being used by any instance of consultation
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean true if the scale is used by any consultation
 */
function consultation_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * Returns array of view type log actions
 * @return array read actions array
 */
function consultation_get_view_actions() {
    return array('view', 'view inquiry', 'view all');
}

/**
 * Returns array of post type log actions
 * @return array post actions array
 */
function consultation_get_post_actions() {
    return array('participate inquiry', 'open inquiry', 'resolve inquiry', 'reopen inquiry');
}

/**
 * Returns all other caps used in module
 * @return array list of extra capabilities used in module context
 */
function consultation_get_extra_capabilities() {
    return array('moodle/site:accessallgroups', 'moodle/site:viewfullnames');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all posts from the specified consultation
 * and clean up any related data.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function consultation_reset_userdata($data) {
    global $DB;

    $componentstr = get_string('modulenameplural', 'mod_consultation');
    $status = array();

    $fs = get_file_storage();

    if (!empty($data->reset_consultation_all)) {
        if ($consultations = $DB->get_records('consultation', array('course'=>$data->courseid))) {
            foreach ($consultations as $consultationid=>$unused) {
                $DB->delete_records_select('consultation_posts', "inquiryid IN (SELECT c.id
                                                                                  FROM {consultation_inquiries} c
                                                                                 WHERE c.consultationid = ?)", array($consultationid));
                $DB->delete_records('consultation_inquiries', array('consultationid'=>$consultationid));
            }
            $cm = get_coursemodule_from_instance('consultation', $consultationid);
            $context = get_context_instance(CONTEXT_MODULE, $cm->id);
            $fs->delete_area_files($context->id, 'mod_consultation', 'attachment');
        }

        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleted'), 'error'=>false);
    }

    return $status;
}

/**
 * Called by course/reset.php
 * @param $mform form passed by reference
 */
function consultation_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'consultationheader', get_string('modulenameplural', 'mod_consultation'));
    $mform->addElement('checkbox', 'reset_consultation_all', get_string('resetconsultationsall', 'mod_consultation'));
}

/**
 * Course reset form defaults.
 */
function consultation_reset_course_form_defaults($course) {
    return array('reset_consultation_all'=>1);
}


/**
 * Given a course and a date, prints a summary of all updated inquiries
 * @param object $course
 * @param bool $viewfullnames capability
 * @param int $timestart
 * @return bool success
 */
function consultation_print_recent_activity($course, $viewfullnames, $timestart) {
    global $CFG, $USER, $DB, $OUTPUT;

    // do not use log table if possible, it may be huge and is expensive to join with other tables

    $sql = "SELECT DISTINCT ci.*
              FROM {consultation_inquiries} ci
              JOIN {consultation} c ON (c.id = ci.consultationid AND c.course = :courseid)
             WHERE ci.timemodified > :timestart AND (ci.userfrom = :userid1 OR ci.userto = :userid2)
          ORDER BY ci.timemodified ASC";

    if (!$active_inquiries = $DB->get_records_sql($sql, array('timestart'=>$timestart, 'courseid'=>$course->id, 'userid1'=>$USER->id, 'userid2'=>$USER->id))) {
        return false;
    }

    $modinfo = get_fast_modinfo($course);

    $strftimerecent = get_string('strftimerecent');
    $users = array($USER->id=>$USER->id);

    foreach ($active_inquiries as $key=>$inquiry) {
        if (!isset($modinfo->instances['consultation'][$inquiry->consultationid])) {
            // not visible
            unset($active_inquiries[$key]);
            continue;
        }
        $cm = $modinfo->instances['consultation'][$inquiry->consultationid];
        if (!$cm->uservisible) {
            unset($active_inquiries[$key]);
            continue;
        }
        $users[$inquiry->userto]   = $inquiry->userto;
        $users[$inquiry->userfrom] = $inquiry->userfrom;
    }

    if (!$active_inquiries) {
        return false;
    }

    require_once("$CFG->dirroot/mod/consultation/locallib.php");
    $users = consultation_load_users($users);

    echo $OUTPUT->heading(get_string('updatedinquiries', 'mod_consultation').':');
    echo "\n<ul class='unlist'>\n";

    foreach ($active_inquiries as $inquiry) {
        $userid = ($USER->id == $inquiry->userfrom) ? $inquiry->userto : $inquiry->userfrom;
        $user = $users[$userid];

        echo '<li><div class="head">'.
               '<div class="date">'.userdate($inquiry->timemodified, $strftimerecent).'</div>'.
               '<div class="name">'.fullname($user, $viewfullnames).'</div>'.
             '</div>';
        echo '<div class="info">';
        echo '"<a href="'.$CFG->wwwroot.'/mod/consultation/inquiry.php?id='.$inquiry->id.'">';
        echo break_up_long_words(format_string($inquiry->subject, true));
        echo "</a>\"</div></li>\n";
    }

    echo "</ul>\n";

    return true;
}

/**
 * Print consultation overview
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $consultation
 * @return object A standard object with 2 variables: info (number of posts for this user) and time (last modified)
 */
function consultation_user_outline($course, $user, $mod, $consultation) {
    global $CFG, $USER, $DB;

    $modinfo = get_fast_modinfo($course);
    if (!isset($modinfo->instances['consultation'][$consultation->id])) {
        return;
    }
    $cm = $modinfo->instances['consultation'][$consultation->id];
    if (!$cm->uservisible) {
        return;
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    if ($USER->id != $user->id and !has_capability('mod/consultation:viewany', $context)) {
        return;
    }

    $sql = "SELECT COUNT(id) AS icount, MAX(timecreated) AS icreated
              FROM {consultation_inquiries}
             WHERE consultationid = :cid AND userfrom = :userid";
    if (!$results = $DB->get_records_sql($sql, array('cid'=>$consultation->id, 'userid'=>$user->id))) {
        return null;
    }
    $info = reset($results);

    $result = new stdClass();
    $result->info = get_string('numstartedinquiries', 'mod_consultation', $info->icount);
    $result->time = $info->icreated;
    return $result;
}


/**
 * Prints parts of complete user report
 * @param object $course
 * @param object $user
 * @param object unused
 * @param object $consultation
 * @return void
 */
function consultation_user_complete($course, $user, $mod, $consultation) {
    global $CFG, $USER, $DB;
    require_once("$CFG->dirroot/mod/consultation/locallib.php");

    $modinfo = get_fast_modinfo($course);
    if (!isset($modinfo->instances['consultation'][$consultation->id])) {
        return;
    }
    $cm = $modinfo->instances['consultation'][$consultation->id];
    if (!$cm->uservisible) {
        return;
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    if ($USER->id != $user->id and !has_capability('mod/consultation:viewany', $context)) {
        return;
    }

    $sql = "SELECT *
              FROM {consultation_inquiries}
             WHERE consultationid = :cid AND userfrom = :userid";
    if (!$inquiries = $DB->get_records_sql($sql, array('cid'=>$consultation->id, 'userid'=>$user->id))) {
        return;
    }

    foreach ($inquiries as $inquiry) {
        echo '<div class="consultationoverview">';
        consultation_print_inquiry($inquiry, $consultation, $cm, $course, '', array(), false);
        echo '</div>';
    }
}

/**
 * Returns info for My Moodle page
 * @param array $courses my courses
 * @param array $htmlarray
 * @return void
 */
function consultation_print_overview($courses, &$htmlarray) {
    //TODO: My Moodle support
}

/**
 * Indicates API features that the forum supports.
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 */
function consultation_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_BACKUP_MOODLE2:          return false; //TODO

        default: return null;
    }
}

