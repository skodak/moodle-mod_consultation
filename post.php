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
 * Add and edit posts
 *
 * @package    mod
 * @subpackage consultation
 * @copyright  2009 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir.'/filelib.php');
require_once('locallib.php');
require_once('post_form.php');

$cid = required_param('inquiryid', PARAM_INT);
$id  = optional_param('id', 0, PARAM_INT);

$inquiry = $DB->get_record('consultation_inquiries', array('id'=>$cid), '*', MUST_EXIST);
$consultation = $DB->get_record('consultation', array('id'=>$inquiry->consultationid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$consultation->course), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('consultation', $consultation->id, $course->id, false, MUST_EXIST);

$PAGE->set_url('/mod/consultation/unread.php', array('id' => $cm->id));

require_login($course, false, $cm);

$PAGE->set_title($course->shortname.': '.$consultation->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($consultation);

consultation_no_guest_access($consultation, $cm, $course);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

if ($USER->id != $inquiry->userto and $USER->id != $inquiry->userfrom) {
    require_capability('mod/consultation:viewany', $context);
    require_capability('mod/consultation:interrupt', $context);
}

if ($inquiry->resolved) {
    print_error('inquiryalreadyresolved', 'consultation', 'inquiry.php?id='.$inquiry->id);
}

if ($id) {
    $post = $DB->get_record('consultation_posts', array('id'=>$id, 'inquiryid'=>$inquiry->id));

    // can edit only own!
    if (!$post or $post->userid != $USER->id) {
        redirect('inquiry.php?id='.$inquiry->id);
    }
    // check edit time
    if (time() > $post->timecreated + ($consultation->edittime * 60)) {
        redirect('inquiry.php?id='.$inquiry->id, get_string('timeeditoever', 'consultation'));
    }

} else {
    $post = new stdClass();
    $post->inquiryid = $inquiry->id;
    $post->id        = null;
}

$draftitemid = file_get_submitted_draft_itemid('attachment');
file_prepare_draft_area($draftitemid, $context->id, 'mod_consultation', 'attachment', $post->id);
$post = file_prepare_standard_editor($post, 'message', array('maxfiles'=>0));

$mform = new mod_consultation_post_form('post.php', array('current'=>$post, 'inquiry'=>$inquiry, 'consultation'=>$consultation, 'cm'=>$cm, 'course'=>$course, 'full'=>true));

if ($mform->is_cancelled()) {
    redirect('inquiry.php?id='.$inquiry->id);
}

if ($post = $mform->get_data()) {
    // NOTE: user may double click or otherwise cancel this request
    // this is not acceptable, we have to finish it!
    ignore_user_abort(true);

    $post->message       = $post->message_editor['text'];
    $post->messageformat = $post->message_editor['format'];

    if ($post->id) {
        $newpost = false;

        $post->timemodified = time();
        $post->seenon       = 0;

        $DB->update_record('consultation_posts', $post);

    } else {
        $newpost = true;

        $post->consultationid = $consultation->id;
        $post->userid         = $USER->id;
        $post->timecreated    = time();
        $post->timemodified   = $post->timecreated;
        $post->notified       = 0;

        $post->id = $DB->insert_record('consultation_posts', $post);
    }

    $DB->set_field('consultation_inquiries', 'timemodified', $post->timemodified, array('id'=>$inquiry->id));
    file_save_draft_area_files($data->attachment, $context->id, 'mod_consultation', 'attachment', $post->id);

    if ($newpost) {
        // notify users if needed
        consultation_notify($post, false, $inquiry, $consultation, $cm, $course);
    }

    // log actions
    add_to_log($course->id, 'consultation', 'participate inquiry', "inquiry.php?id=$inquiry->id", $inquiry->id, $cm->id);

    redirect("inquiry.php?id=$inquiry->id");
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();

