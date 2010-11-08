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
 * Inquiry related functions
 *
 * @package    mod
 * @subpackage consultation
 * @copyright  2009 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('locallib.php');

$cid    = required_param('id', PARAM_INT);            // one inquiry detail
$action = optional_param('action', '', PARAM_ACTION); // action

$inquiry      = $DB->get_record('consultation_inquiries', array('id'=>$cid), '*', MUST_EXIST);
$consultation = $DB->get_record('consultation', array('id'=>$inquiry->consultationid), '*', MUST_EXIST);
$course       = $DB->get_record('course', array('id'=>$consultation->course), '*',MUST_EXIST);
$cm           = get_coursemodule_from_instance('consultation', $consultation->id, $course->id, false, MUST_EXIST);

$PAGE->set_url('/mod/consultation/inquiry.php', array('id' => $cm->id));

require_login($course, false, $cm);

$PAGE->set_title($course->shortname.': '.$consultation->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($consultation);

consultation_no_guest_access($consultation, $cm, $course);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

if ($inquiry->userfrom != $USER->id and $inquiry->userto != $USER->id) {
    require_capability('mod/consultation:viewany', $context);
}

$output = $PAGE->get_renderer('mod_consultation');

if ($action === 'resolve') {
    echo $output->header();
    $optionsyes = new moodle_url('/mod/consultation/inquiry.php', array('id'=>$inquiry->id, 'action'=>'confirmresolve', 'sesskey'=>sesskey()));
    $optionsno  = new moodle_url('/mod/consultation/inquiry.php', array('id'=>$inquiry->id));
    echo $output->confirm(get_string('confirmclosure', 'consultation', format_string($inquiry->subject)), $optionsyes, $optionsno);
    echo $output->footer();
    die;

} else if ($action === 'confirmresolve' and confirm_sesskey()) {
    if ($inquiry->userfrom == $USER->id or $inquiry->userto == $USER->id) {
        require_capability('mod/consultation:resolve', $context);
    } else {
        require_capability('mod/consultation:resolveany', $context);
    }

    $DB->set_field('consultation_inquiries', 'timemodified', time(), array('id'=>$inquiry->id));
    $DB->set_field('consultation_inquiries', 'resolved', 1, array('id'=>$inquiry->id));

    // log actions
    add_to_log($course->id, 'consultation', 'resolve inquiry', "inquiry.php?id=$inquiry->id", $inquiry->id, $cm->id);
    redirect("view.php?id=$cm->id");

} else if ($action === 'reopen') {
    echo $output->header();
    $optionsyes = new moodle_url('/mod/consultation/inquiry.php', array('id'=>$inquiry->id, 'action'=>'confirmreopen', 'sesskey'=>sesskey()));
    $optionsno  = new moodle_url('/mod/consultation/inquiry.php', array('id'=>$inquiry->id));
    echo $output->confirm(get_string('confirmreopen', 'consultation', format_string($inquiry->subject)), $optionsyes, $optionsno);
    echo $output->footer();
    die;

} else if ($action === 'confirmreopen' and confirm_sesskey()) {
    if ($inquiry->userfrom == $USER->id or $inquiry->userto == $USER->id) {
        require_capability('mod/consultation:reopen', $context);
    } else {
        require_capability('mod/consultation:reopenany', $context);
    }

    $DB->set_field('consultation_inquiries', 'timemodified', time(), array('id'=>$inquiry->id));
    $DB->set_field('consultation_inquiries', 'resolved', 0, array('id'=>$inquiry->id));

    // log actions
    add_to_log($course->id, 'consultation', 'reopen inquiry', "inquiry.php?id=$inquiry->id", $inquiry->id, $cm->id);
    redirect("inquiry.php?id=$inquiry->id");
}


// log actions
add_to_log($course->id, 'consultation', 'view inquiry', "inquiry.php?id=$inquiry->id", $inquiry->id, $cm->id);

echo $output->header();

if (trim(strip_tags($consultation->intro))) {
    echo $output->box_start('mod_introbox');
    echo format_module_intro('consultation', $consultation, $cm->id);
    echo $output->box_end();
}

$currenttab = $inquiry->resolved ? 'resolved' : 'view';
$mode = ($inquiry->userfrom != $USER->id and $inquiry->userto != $USER->id) ? 'others' : 'my';
echo $output->consultation_tabs($currenttab, $mode, $inquiry->id, $consultation, $cm, $course);

/// we want to view the inquiry
consultation_print_inquiry($inquiry, $consultation, $cm, $course, 'inquiry.php', array('id'=>$inquiry->id));

consultation_mark_inquiry_read($inquiry, $consultation, $cm, $course);

echo $output->footer();

