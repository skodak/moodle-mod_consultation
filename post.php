<?php

// This file is part of Consultation module for Moodle.
//
// Consultation is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 2 of the License, or
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
 * Add and edit posts
 *
 * @package   mod-consultation
 * @copyright 2009 Petr Skoda (http://skodak.org)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/filelib.php');
require_once('locallib.php');
require_once('post_form.php');

$cid = required_param('inquiryid', PARAM_INT);
$id  = optional_param('id', 0, PARAM_INT);

if (!$inquiry = get_record('consultation_inquiries', 'id', $cid)) {
    error('Inquiry id is incorrect');
}

if (!$consultation = get_record('consultation', 'id', $inquiry->consultationid)) {
    error('Course module is incorrect');
}

if (!$course = get_record('course', 'id', $consultation->course)) {
    error('Course is misconfigured');
}

if (!$cm = get_coursemodule_from_instance('consultation', $consultation->id, $course->id)) {
    error('Course Module ID was incorrect');
}

require_login($course, false, $cm);
consultation_no_guest_access($consultation, $cm, $course);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

if ($USER->id != $inquiry->userto and $USER->id != $inquiry->userfrom) {
    require_capability('mod/consultation:viewany', $context);
    require_capability('mod/consultation:interrupt', $context);
}

if ($inquiry->resolved) {
    error(get_string('inquiryalreadyresolved', 'consultation'), 'inquiry.php?id='.$inquiry->id);
}

if ($id) {
    $post = get_record('consultation_posts', 'id', $id, 'inquiryid', $inquiry->id);

    // can edit only own!
    if (!$post or $post->userid != $USER->id) {
        redirect('inquiry.php?id='.$inquiry->id);
    }
    // check edit time
    if (time() > $post->timecreated + ($consultation->edittime * 60)) {
        redirect('inquiry.php?id='.$inquiry->id, get_string('timeeditoever', 'consultation'));
    }

} else {
    $post = new object();
    $post->inquiryid = $inquiry->id;
}

$mform = new mod_consultation_post_form('post.php', array('current'=>$post, 'inquiry'=>$inquiry, 'consultation'=>$consultation, 'cm'=>$cm, 'course'=>$course, 'full'=>true));

if ($mform->is_cancelled()) {
    redirect('inquiry.php?id='.$inquiry->id);
}

if ($post = $mform->get_data(false)) {
    // NOTE: user may double click or otherwise cancel this request
    // this is not acceptable, we have to finish it!
    ignore_user_abort(true);
    
    if ($post->id) {
        $post->timemodified = time();
        $post->seenon       = 0;

        if (!empty($post->deleteattachment)) {
            fulldelete($CFG->dataroot.'/'.consultation_get_moddata_post_dir($post, $consultation));
            $post->attachment = '';
        } else if ($attachment = $mform->get_new_filename()) {
            $post->attachment = $attachment;
        } else {
            unset($post->attachment);
        }

        if (!update_record('consultation_posts', addslashes_recursive($post))) {
            error('Can not update inquiry post');
        }
        if (!empty($post->attachment)) {
            $mform->save_files(consultation_get_moddata_post_dir($post, $consultation));
        }
        set_field('consultation_inquiries', 'timemodified', $post->timemodified, 'id', $inquiry->id);

        // note: do not resend notification here
        
        // log actions
        add_to_log($course->id, 'consultation', 'participate inquiry', "inquiry.php?id=$inquiry->id", $inquiry->id, $cm->id);

    } else {
        $post->consultationid       = $consultation->id;
        $post->userid         = $USER->id;
        $post->timecreated    = time();
        $post->timemodified   = $post->timecreated;
        $post->notified       = 0;
        if ($attachment = $mform->get_new_filename()) {
            $post->attachment = $attachment;
        } else {
            $post->attachment = '';
        }

        if (!$post->id = insert_record('consultation_posts', addslashes_recursive($post))) {
            error('Can not insert new inquiry post');
        }

        if ($post->attachment) {
            $mform->save_files(consultation_get_moddata_post_dir($post, $consultation));
        }
        set_field('consultation_inquiries', 'timemodified', $post->timemodified, 'id', $inquiry->id);

        // notify users if needed
        consultation_notify($post, false, $inquiry, $consultation, $cm, $course);
        
        // log actions
        add_to_log($course->id, 'consultation', 'participate inquiry', "inquiry.php?id=$inquiry->id", $inquiry->id, $cm->id);
    }

    redirect("inquiry.php?id=$inquiry->id");
}

$strconsultations = get_string('modulenameplural', 'consultation');

$navlinks = array(array('name'=>format_string($inquiry->subject), 'link'=>'', 'type'=>'title'));
$navigation = build_navigation($navlinks, $cm);

print_header_simple($consultation->name, '', $navigation, '', '', true);
$mform->display();
print_footer($course);

