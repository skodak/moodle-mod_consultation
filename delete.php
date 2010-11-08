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
 * Delete inquiry post, if last post deletes the inquiry completely.
 *
 * @package    mod
 * @subpackage consultation
 * @copyright  2009 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('locallib.php');
require_once($CFG->libdir.'/filelib.php');

$id      = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$post         = $DB->get_record('consultation_posts', array('id'=>$id), '*', MUST_EXIST);
$inquiry      = $DB->get_record('consultation_inquiries', array('id'=>$post->inquiryid), '*', MUST_EXIST);
$consultation = $DB->get_record('consultation', array('id'=>$inquiry->consultationid), '*', MUST_EXIST);
$course       = $DB->get_record('course', array('id'=>$consultation->course), '*', MUST_EXIST);
$cm           = get_coursemodule_from_instance('consultation', $consultation->id, $course->id, false, MUST_EXIST);

$PAGE->set_url('/mod/consultation/delete.php', array('id' => $cm->id));

require_login($course, false, $cm);

$PAGE->set_title($course->shortname.': '.$consultation->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($consultation);

consultation_no_guest_access($consultation, $cm, $course);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
require_capability('mod/consultation:deleteany', $context);

$count = $DB->count_records('consultation_posts', array('inquiryid'=>$inquiry->id));
$firstpost = reset($DB->get_records('consultation_posts', array('inquiryid'=>$inquiry->id), 'timecreated', '*', 0, 1));

if ($count > 1 and $post->id == $firstpost->id) {
    redirect('inquiry.php?id='.$inquiry->id, get_string('cannotdeleteinquiry', 'consultation'));
}

if ($confirm and confirm_sesskey()) {
    // NOTE: user may double click or otherwise cancel this request
    // this is not acceptable, we have to finish it!
    ignore_user_abort(true);

    //TODO: delete attachments

    $DB->delete_records('consultation_posts', array('id'=>$post->id));

    // log actions
    add_to_log($course->id, 'consultation', 'participate inquiry', "inquiry.php?id=$inquiry->id", $inquiry->id, $cm->id);

    if ($count == 1) {
        if (!$DB->delete_records('consultation_inquiries', array('id'=>$inquiry->id))) {
            error('Can not delete post');
        }
        redirect('view.php?id='.$cm->id);
    } else {
        redirect('inquiry.php?id='.$inquiry->id);
    }
}

$output = $PAGE->get_renderer('mod_consultation');

echo $output->header();

$optionsyes = new moodle_url('/mod/consultation/delete.php', array('id'=>$post->id, 'confirm'=>1, 'sesskey'=>sesskey()));
$optionsno  = new moodle_url('/mod/consultation/inquiry.php', array('id'=>$inquiry->id));
if ($count == 1) {
    $strconfirm = get_string('confirmdeleteinquiry', 'consultation', format_string($inquiry->subject));
} else {
    $options = (object)array('para'=>false, 'context'=>$context);
    $shortened = format_text($post->message, $post->messageformat, $options);
    $strconfirm = get_string('confirmdeletepost', 'consultation', $shortened);
}
echo $output->confirm($strconfirm, $optionsyes, $optionsno);

echo $output->footer();

