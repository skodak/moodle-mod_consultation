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

require('../../config.php');
require_once('locallib.php');
require_once('open_form.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('consultation', $id, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$consultation = $DB->get_record('consultation', array('id'=>$cm->instance), '*', MUST_EXIST);

$PAGE->set_url('/mod/consultation/open.php', array('id' => $cm->id));

require_login($course, false, $cm);

$PAGE->set_title($course->shortname.': '.$consultation->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($consultation);

consultation_no_guest_access($consultation, $cm, $course);


$context = get_context_instance(CONTEXT_MODULE, $cm->id);
if (!has_capability('mod/consultation:openany', $context)) {
    require_capability('mod/consultation:open', $context);
}

$draftitemid = file_get_submitted_draft_itemid('attachment');
file_prepare_draft_area($draftitemid, $context->id, 'mod_consultation', 'attachment', null);

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

    $inquiry->id = $DB->insert_record('consultation_inquiries', $inquiry);

    $post = new object();
    $post->inquiryid      = $inquiry->id;
    $post->userid         = $USER->id;
    $post->notified       = 0;
    $post->message        = $data->message['text'];
    $post->messageformat  = $data->message['format'];
    $post->seenon         = null;
    $post->notified       = 0;
    $post->timecreated    = $timenow;
    $post->timemodified   = $timenow;
    $post->attachment     = '';
    if ($attachment = $mform->get_new_filename()) {
        $post->attachment = $attachment;
    }

    $post->id = $DB->insert_record('consultation_posts', $post);

    file_save_draft_area_files($data->attachment, $context->id, 'mod_consultation', 'attachment', $post->id);

    // log actions
    add_to_log($course->id, 'consultation', 'open inquiry', "inquiry.php?id=$inquiry->id", $inquiry->id, $cm->id);

    // notify users if needed
    consultation_notify($post, true, $inquiry, $consultation, $cm, $course);

    redirect("view.php?id=$cm->id&cid=".$inquiry->id);
}

$output = $PAGE->get_renderer('mod_consultation');

echo $output->header();

if (trim(strip_tags($consultation->intro))) {
    echo $output->box_start('mod_introbox');
    echo format_module_intro('consultation', $consultation, $cm->id);
    echo $output->box_end();
}

echo $output->consultation_tabs('open', 'none', 0, $consultation, $cm, $course);

groups_print_activity_menu($cm, "$CFG->wwwroot/mod/consultation/open.php?id=$cm->id");

$mform->display();

echo $output->footer();

