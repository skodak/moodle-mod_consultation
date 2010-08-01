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

if ($inquiry->userfrom != $USER->id and $inquiry->userto != $USER->id) {
    require_capability('mod/consultation:viewany', $context);
}

$strconsultation  = get_string('modulename', 'consultation');
$strconsultations = get_string('modulenameplural', 'consultation');

$navlinks = array(array('name'=>format_string($inquiry->subject), 'link'=>'', 'type'=>'title'));
$navigation = build_navigation($navlinks, $cm);

if ($action === 'resolve') {
    print_header_simple($consultation->name, '', $navigation, '', '', true,
                        update_module_button($cm->id, $course->id, $strconsultation), navmenu($course, $cm));

    $optionsyes = array('id'=>$inquiry->id, 'action'=>'confirmresolve', 'sesskey'=>sesskey());
    $optionsno  = array('id'=>$inquiry->id);
    notice_yesno(get_string('confirmclosure', 'consultation', format_string($inquiry->subject)), 'inquiry.php', 'inquiry.php', $optionsyes, $optionsno, 'post', 'get');
    print_footer($course);
    die;

} else if ($action === 'confirmresolve' and confirm_sesskey()) {
    if ($inquiry->userfrom == $USER->id or $inquiry->userto == $USER->id) {
        require_capability('mod/consultation:resolve', $context);
    } else {
        require_capability('mod/consultation:resolveany', $context);
    }

    set_field('consultation_inquiries', 'timemodified', time(), 'id', $inquiry->id);
    if (!set_field('consultation_inquiries', 'resolved', 1, 'id', $inquiry->id)) {
        error('Resolve consultation: unable to set resolved');
    }

    // log actions
    add_to_log($course->id, 'consultation', 'resolve inquiry', "inquiry.php?id=$inquiry->id", $inquiry->id, $cm->id);
    redirect("view.php?id=$cm->id");

} else if ($action === 'reopen') {
    print_header_simple($consultation->name, '', $navigation, '', '', true,
                        update_module_button($cm->id, $course->id, $strconsultation), navmenu($course, $cm));

    $optionsyes = array('id'=>$inquiry->id, 'action'=>'confirmreopen', 'sesskey'=>sesskey());
    $optionsno  = array('id'=>$inquiry->id);
    notice_yesno(get_string('confirmreopen', 'consultation', format_string($inquiry->subject)), 'inquiry.php', 'inquiry.php', $optionsyes, $optionsno, 'post', 'get');
    print_footer($course);
    die;

} else if ($action === 'confirmreopen' and confirm_sesskey()) {
    if ($inquiry->userfrom == $USER->id or $inquiry->userto == $USER->id) {
        require_capability('mod/consultation:reopen', $context);
    } else {
        require_capability('mod/consultation:reopenany', $context);
    }

    set_field('consultation_inquiries', 'timemodified', time(), 'id', $inquiry->id);
    if (!set_field('consultation_inquiries', 'resolved', 0, 'id', $inquiry->id)) {
        error('Resolve consultation: unable to set resolved');
    }

    // log actions
    add_to_log($course->id, 'consultation', 'reopen inquiry', "inquiry.php?id=$inquiry->id", $inquiry->id, $cm->id);
    redirect("inquiry.php?id=$inquiry->id");
}


// log actions
add_to_log($course->id, 'consultation', 'view inquiry', "inquiry.php?id=$inquiry->id", $inquiry->id, $cm->id);

print_header_simple($consultation->name, '', $navigation, '', '', true,
                    update_module_button($cm->id, $course->id, $strconsultation), navmenu($course, $cm));

print_box(format_text($consultation->intro, $consultation->introformat), 'generalbox consultationintro');

$currenttab = $inquiry->resolved ? 'resolved' : 'view';
$mode = ($inquiry->userfrom != $USER->id and $inquiry->userto != $USER->id) ? 'others' : 'my';
consultation_print_tabs($currenttab, $mode, $inquiry->id, $consultation, $cm, $course);

/// we want to view the inquiry
consultation_print_inquiry($inquiry, $consultation, $cm, $course, 'inquiry.php', array('id'=>$inquiry->id));

consultation_mark_inquiry_read($inquiry, $consultation, $cm, $course);

print_footer($course);

