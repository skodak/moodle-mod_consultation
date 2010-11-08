<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Consultation module renderer
 *
 * @package    mod
 * @subpackage consultation
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class mod_consultation_renderer extends plugin_renderer_base {

    /**
     * Print tabs
     * @param string $currenttab
     * @param string $mode
     * @param int $ignoreunreadin
     * @param object $consultation
     * @param object $cm
     * @param object $course
     * @return void
     */
    public function consultation_tabs($currenttab, $mode, $ignoreunreadin, $consultation, $cm, $course) {
        global $USER;

        $tabs = array();

        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        $viewall = has_capability('mod/consultation:viewany', $context);

        $counts = consultation_get_counts($USER->id, $consultation->id, $viewall, $ignoreunreadin);

        $activetwo = null;

        $row = array();
        if (has_capability('mod/consultation:open', $context) or has_capability('mod/consultation:openany', $context)) {
            $row[] = new tabobject('open', "open.php?id=$cm->id", get_string('tabopen', 'mod_consultation'));
        }
        if ($counts->myunread) {
            $row[] = new tabobject('unread', "unread.php?id=$cm->id", get_string('tabunread', 'mod_consultation', $counts->myunread));
        }
        if ($viewall) {
            $row[] = new tabobject('view', "view.php?id=$cm->id", get_string('tabview', 'mod_consultation'));
        } else {
            $row[] = new tabobject('view', "view.php?id=$cm->id", get_string('tabviewany', 'mod_consultation', $counts->myopen));
        }
        if ($counts->myresolved or $counts->othersresolved or $currenttab === 'resolved') {
            if ($viewall) {
                $row[] = new tabobject('resolved', "resolved.php?id=$cm->id", get_string('tabresolved', 'mod_consultation'));
            } else {
                $row[] = new tabobject('resolved', "resolved.php?id=$cm->id", get_string('tabresolvedany', 'mod_consultation', $counts->myresolved));
            }
        }

        $tabs[] = $row;

        if ($viewall and $currenttab === 'view') {
            $row = array();
            $row[] = new tabobject('viewmy', "view.php?id=$cm->id&mode=my", get_string('subtabviewmy', 'mod_consultation', $counts->myopen));
            $row[] = new tabobject('viewothers', "view.php?id=$cm->id&mode=others", get_string('subtabviewothers', 'mod_consultation', $counts->othersopen));
            $tabs[] = $row;
            $activetwo = array('view'.$mode);
        }

        if ($viewall and $currenttab === 'resolved') {
            $row = array();
            $row[] = new tabobject('resolvedmy', "resolved.php?id=$cm->id&mode=my", get_string('subtabresolvedmy', 'mod_consultation', $counts->myresolved));
            $row[] = new tabobject('resolvedothers', "resolved.php?id=$cm->id&mode=others", get_string('subtabresolvedothers', 'mod_consultation', $counts->othersresolved));
            $tabs[] = $row;
            $activetwo = array('resolved'.$mode);
        }

        return print_tabs($tabs, $currenttab, $activetwo, null, true);
    }

}
