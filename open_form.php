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
 * New inquiry form
 *
 * @package    mod
 * @subpackage consultation
 * @copyright  2009 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class consultation_open_form extends moodleform {
    function definition() {
        global $CFG, $USER;

        $mform = $this->_form;

        $consultation = $this->_customdata['consultation'];
        $cm     = $this->_customdata['cm'];
        $course = $this->_customdata['course'];

        $context = get_context_instance(CONTEXT_MODULE, $cm->id);

        if ($consultation->openlimit and !has_capability('mod/consultation:openany', $context)) {
            $alredayopened = consultation_count_started_inquiries($consultation, $USER->id);
            if ($alredayopened >= $consultation->openlimit) {
                $mform->addElement('static', 'nousers', get_string('consultationwith', 'consultation').':', get_string('toomanyinquiries', 'consultation', $consultation->openlimit));
                return;
            }
        }

        $currentgroup = groups_get_activity_group($cm, true);
        list($names, $names2) = consultation_get_candidates($USER, $currentgroup, $consultation, $cm, $course);

        if (!$names and !$names2) {
            $mform->addElement('static', 'nousers', get_string('consultationwith', 'consultation').':', get_string('noavailablepeople', 'consultation'));
            return;
        }

        $mform->addElement('selectgroups', 'userto', get_string('consultationwith', 'consultation'), array(get_string('candidatesnotyet', 'consultation')=>$names,
                                                                                               get_string('candidatesopened', 'consultation')=>$names2),
                                                                null, true);
        $mform->addRule('userto', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'subject', get_string('subject', 'consultation'), 'size="65"');
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('required'), 'required', null, 'client');

        $mform->addElement('editor', 'message',  get_string('message', 'consultation'), array('cols'=>80, 'rows'=>20, 'context'=>$context));
        $mform->setType('message', PARAM_RAW); // cleaned before printing or editing
        $mform->addRule('message', get_string('required'), 'required', null, 'client');

        $mform->addElement('filemanager', 'attachment', get_string('attachment', 'consultation'));


        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('openconsultation', 'consultation'));
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!isset($data['userto']) or $data['userto'] <= 0) {
            $errors['userto'] = get_string('required');
        }
        return $errors;
    }
}
