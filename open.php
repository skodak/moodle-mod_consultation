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
 * Open new inquiry
 *
 * @package    mod
 * @subpackage consultation
 * @copyright  2009 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('locallib.php');
require_once('open_form.php');

$id = required_param('id', PARAM_INT);

if (!$cm = get_coursemodule_from_id('consultation', $id)) {
    error('Course Module ID was incorrect');
}

if (!$course = get_record('course', 'id', $cm->course)) {
    error('Course is misconfigured');
}

if (!$consultation = get_record('consultation', 'id', $cm->instance)) {
    error('Course module is incorrect');
}

require_login($course, false, $cm);
consultation_no_guest_access($consultation, $cm, $course);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
if (!has_capability('mod/consultation:openany', $context)) {
    require_capability('mod/consultation:open', $context);
}

$mform = new consultation_open_form(null, array('consultation'=>$consultation, 'cm'=>$cm, 'course'=>$course));
$mform->set_data(array('id'=>$cm->id));

if ($mform->is_cancelled()) {
    redirect('view.php?id='.$cm->id);
}

if ($data = $mform->get_data(false)) {
    // NOTE: user may double click or otherwise cancel this request
    // this is not acceptable, we have to finish it!
    ignore_user_abort(true);

    $timenow = time();

    $inquiry = new object();
    $inquiry->consultationid = $consultation->id;
    $inquiry->userfrom       = $USER->id;
    $inquiry->userto         = $data->userto;
    $inquiry->subject        = $data->subject;
    $inquiry->timecreated    = $timenow;
    $inquiry->timemodified   = $timenow;

    if (!$inquiry->id = insert_record('consultation_inquiries', addslashes_recursive($inquiry))) {
        error('Can not insert new inquiry');
    }

    $post = new object();
    $post->inquiryid      = $inquiry->id;
    $post->userid         = $USER->id;
    $post->notified       = 0;
    $post->message        = $data->message;
    $post->messageformat  = $data->messageformat;
    $post->seenon         = null;
    $post->notified       = 0;
    $post->timecreated    = $timenow;
    $post->timemodified   = $timenow;
    $post->attachment     = '';
    if ($attachment = $mform->get_new_filename()) {
        $post->attachment = $attachment;
    }

    if (!$post->id = insert_record('consultation_posts', addslashes_recursive($post))) {
        error('Can not insert new inquiry post');
    }

    if ($post->attachment) {
        $mform->save_files(consultation_get_moddata_post_dir($post, $consultation));
    }

    // log actions
    add_to_log($course->id, 'consultation', 'open inquiry', "inquiry.php?id=$inquiry->id", $inquiry->id, $cm->id);

    // notify users if needed
    consultation_notify($post, true, $inquiry, $consultation, $cm, $course);

    redirect("view.php?id=$cm->id&cid=".$inquiry->id);
}

$strconsultation = get_string('modulename', 'consultation');
$strconsultations = get_string('modulenameplural', 'consultation');

$navlinks = array(array('name'=>get_string('openconsultation', 'consultation'), 'link'=>'', 'type'=>'title'));
$navigation = build_navigation($navlinks, $cm);

print_header_simple($consultation->name, '', $navigation, '', '', true, update_module_button($cm->id, $course->id, $strconsultation), navmenu($course, $cm));

print_box(format_text($consultation->intro, $consultation->introformat), 'generalbox consultationintro');

consultation_print_tabs('open', 'none', 0, $consultation, $cm, $course);

groups_print_activity_menu($cm, "open.php?id=$cm->id");

$mform->display();

print_footer($course);

