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
 * Index of all consultations in course
 *
 * @package    mod
 * @subpackage consultation
 * @copyright  2009 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');
require_once('locallib.php');

$id = required_param('id', PARAM_INT);

if (!$course = get_record('course', 'id', $id)) {
    error('Course ID is incorrect');
}

require_login($course);
add_to_log($course->id, 'consultation', 'view all', "index.php?id=$course->id", '');

$strconsultation        = get_string('modulename', 'consultation');
$strconsultations       = get_string('modulenameplural', 'consultation');
$strname          = get_string('name');
$stropenconsultations   = get_string('openconsultations', 'consultation');
$strresolvedconsultations = get_string('resolvedconsultations', 'consultation');

$navlinks = array(array('name' => $strconsultations, 'link' => '', 'type' => 'activity' ));
$navigation = build_navigation($navlinks);

print_header_simple($strconsultations, '', $navigation,  '', '', true, '', navmenu($course));

$consultations = get_records('consultation', 'course', $course->id);

$table = new object();
$table->head  = array ($strname, $stropenconsultations, $strresolvedconsultations);
$table->align = array ('center', 'center', 'center');

$modinfo =& get_fast_modinfo($course);

if (!isset($modinfo->instances['consultation'])) {
    $modinfo->instances['consultation'] = array();
}

foreach ($modinfo->instances['consultation'] as $consultationid=>$cm) {
    if (!$cm->uservisible or !isset($consultations[$consultationid])) {
        continue;
    }
    $consultation = $consultations[$consultationid];

    $dimmedclass = '';
    if (!$cm->visible) {      // Show dimmed if the mod is hidden
        $dimmedclass = 'class="dimmed"';
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    if (isguestuser()) {
        $table->data[] = array ("<a $dimmedclass href=\"view.php?id=$cm->id\">".format_string($consultation->name)."</a>", '-', '-');
    } else {
        $counts = consultation_get_counts($USER->id, $consultation->id, has_capability('mod/consultation:viewany', $context));

        $table->data[] = array ("<a $dimmedclass href=\"view.php?id=$cm->id\">".format_string($consultation->name)."</a>",
                                $counts->myunread.'/'.$counts->myopen, $counts->myresolved);
    }
}

echo '<br />';
if (!empty($table->data)) {
    print_table($table);
} else {
    notice('There are no consultations', "../../course/view.php?id=$course->id");
}

print_footer($course);
