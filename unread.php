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
 * Print list of inquires with unread posts
 *
 * @package    mod
 * @subpackage consultation
 * @copyright  2009 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('locallib.php');

$id = required_param('id', PARAM_INT);

$cm           = get_coursemodule_from_id('consultation', $id, 0, false, MUST_EXIST);
$course       = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$consultation = $DB->get_record('consultation', array('id'=>$cm->instance), '*', MUST_EXIST);

$PAGE->set_url('/mod/consultation/unread.php', array('id' => $cm->id));

require_login($course, false, $cm);

$PAGE->set_title($course->shortname.': '.$consultation->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($consultation);

consultation_no_guest_access($consultation, $cm, $course);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

// log actions
add_to_log($course->id, 'consultation', 'view', "unread.php?id=$cm->id", $consultation->id, $cm->id);

$output = $PAGE->get_renderer('mod_consultation');

echo $output->header();

if (trim(strip_tags($consultation->intro))) {
    echo $output->box_start('mod_introbox');
    echo format_module_intro('consultation', $consultation, $cm->id);
    echo $output->box_end();
}

echo $output->consultation_tabs('unread', '', 0, $consultation, $cm, $course);

consultation_print_my_inquiries('unread', $consultation, $cm, $course, 'unread.php', array('id'=>$cm->id));

echo $output->footer();

