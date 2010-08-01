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
 * Print list of inquires with unread posts
 *
 * @package   mod-consultation
 * @copyright 2009 Petr Skoda (http://skodak.org)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once('../../config.php');
require_once('locallib.php');

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

// log actions
add_to_log($course->id, 'consultation', 'view', "unread.php?id=$cm->id", $consultation->id, $cm->id);

$strconsultation  = get_string('modulename', 'consultation');
$strconsultations = get_string('modulenameplural', 'consultation');

$navigation = build_navigation('', $cm);

print_header_simple($consultation->name, '', $navigation, '', '', true,
                    update_module_button($cm->id, $course->id, $strconsultation), navmenu($course, $cm));

print_box(format_text($consultation->intro, $consultation->introformat), 'generalbox consultationintro');

consultation_print_tabs('unread', '', 0, $consultation, $cm, $course);

consultation_print_my_inquiries('unread', $consultation, $cm, $course, 'unread.php', array('id'=>$cm->id));

print_footer($course);

