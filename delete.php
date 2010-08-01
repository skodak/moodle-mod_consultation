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
 * Delete inquiry post, if last post deletes the inquiry completely.
 *
 * @package   mod-consultation
 * @copyright 2009 Petr Skoda (http://skodak.org)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once('../../config.php');
require_once('locallib.php');
require_once($CFG->libdir.'/filelib.php');

$id      = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

if (!$post = get_record('consultation_posts', 'id', $id)) {
    error('Post id is incorrect');
}

if (!$inquiry = get_record('consultation_inquiries', 'id', $post->inquiryid)) {
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
require_capability('mod/consultation:deleteany', $context);

$count = count_records('consultation_posts', 'inquiryid', $inquiry->id);
$firstpost = reset(get_records('consultation_posts', 'inquiryid', $inquiry->id, 'timecreated', '*', 0, 1));

if ($count > 1 and $post->id == $firstpost->id) {
    redirect('inquiry.php?id='.$inquiry->id, get_string('cannotdeleteinquiry', 'consultation'));
}

if ($confirm and confirm_sesskey()) {
    // NOTE: user may double click or otherwise cancel this request
    // this is not acceptable, we have to finish it!
    ignore_user_abort(true);

    fulldelete($CFG->dataroot.'/'.consultation_get_moddata_post_dir($post, $consultation));
    if (!delete_records('consultation_posts', 'id', $post->id)) {
        error('Can not delete post');
    }
    // log actions
    add_to_log($course->id, 'consultation', 'participate inquiry', "inquiry.php?id=$inquiry->id", $inquiry->id, $cm->id);

    if ($count == 1) {
        if (!delete_records('consultation_inquiries', 'id', $inquiry->id)) {
            error('Can not delete post');
        }
        redirect('view.php?id='.$cm->id);
    } else {
        redirect('inquiry.php?id='.$inquiry->id);
    }
}

$strconsultations = get_string('modulenameplural', 'consultation');

$navlinks = array(array('name'=>format_string($inquiry->subject), 'link'=>'', 'type'=>'title'));
$navigation = build_navigation($navlinks, $cm);

print_header_simple($consultation->name, '', $navigation, '', '', true);

$optionsyes = array('id'=>$post->id, 'confirm'=>1, 'sesskey'=>sesskey());
$optionsno  = array('id'=>$inquiry->id);
if ($count == 1) {
    $strconfirm = get_string('confirmdeleteinquiry', 'consultation', format_string($inquiry->subject));
} else {
    $options = (object)array('para'=>false);
    $shortened = format_text($post->message, $post->messageformat, $options, $course->id);
    $strconfirm = get_string('confirmdeletepost', 'consultation', $shortened);
}
notice_yesno($strconfirm, 'delete.php', 'inquiry.php', $optionsyes, $optionsno, 'post', 'get');

print_footer($course);

