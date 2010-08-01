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
 * Main interface - displays open inquiries
 *
 * @package    mod
 * @subpackage consultation
 * @copyright  2009 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('locallib.php');

$id   = optional_param('id', PARAM_INT);
$c    = optional_param('c', 0, PARAM_INT);         // consultation id
$mode = optional_param('mode', 'my', PARAM_ALPHA);   // sub tab

if ($c) {
    if (!$consultation = get_record('consultation', 'id', $c)) {
        error('Course module is incorrect');
    }

    if (!$course = get_record('course', 'id', $consultation->course)) {
        error('Course is misconfigured');
    }

    if (!$cm = get_coursemodule_from_instance('consultation', $consultation->id, $course->id)) {
        error('Course Module ID was incorrect');
    }

} else { // $Id
    if (!$cm = get_coursemodule_from_id('consultation', $id)) {
        error('Course Module ID was incorrect');
    }

    if (!$course = get_record('course', 'id', $cm->course)) {
        error('Course is misconfigured');
    }

    if (!$consultation = get_record('consultation', 'id', $cm->instance)) {
        error('Course module is incorrect');
    }
}

require_login($course, false, $cm);
consultation_no_guest_access($consultation, $cm, $course);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

// verify access control
if (!in_array($mode, array('my', 'others')) or !has_capability('mod/consultation:viewany', $context)) {
    $mode = 'my';
}

// log actions
add_to_log($course->id, 'consultation', 'view', "view.php?id=$cm->id&mode=$mode", $consultation->id, $cm->id);

$strconsultation  = get_string('modulename', 'consultation');
$strconsultations = get_string('modulenameplural', 'consultation');

$navigation = build_navigation('', $cm);

print_header_simple($consultation->name, '', $navigation, '', '', true,
                    update_module_button($cm->id, $course->id, $strconsultation), navmenu($course, $cm));

print_box(format_text($consultation->intro, $consultation->introformat), 'generalbox consultationintro');

consultation_print_tabs('view', $mode, 0, $consultation, $cm, $course);

/// show all my inquiries
if ($mode === 'others') {
    consultation_print_others_inquiries('open', $consultation, $cm, $course, 'view.php', array('id'=>$cm->id, 'mode'=>$mode));
} else {
    consultation_print_my_inquiries('open', $consultation, $cm, $course, 'view.php', array('id'=>$cm->id, 'mode'=>$mode));
}

print_footer($course);

