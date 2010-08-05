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
 * Adds post into inquiry
 *
 * @package    mod
 * @subpackage consultation
 * @copyright  2009 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class mod_consultation_post_form extends moodleform {
    function definition() {
        global $DB, $COURSE, $USER;

        //TODO: files
        //$this->set_upload_manager(new upload_manager('attachment', true, false, $COURSE, false, 0, true, true));

        $mform = $this->_form;
        $post         = $this->_customdata['current'];
        $inquiry      = $this->_customdata['inquiry'];
        $consultation = $this->_customdata['consultation'];
        $cm           = $this->_customdata['cm'];
        $course       = $this->_customdata['course'];
        $full         = $this->_customdata['full'];

        if ($full) {
            $with = '';
            if ($inquiry->userto == $USER->id) {
                $userwith = get_record('user', 'id', $inquiry->userfrom);
                $with = fullname($userwith);
            } else if ($inquiry->userfrom == $USER->id) {
                $userwith = get_record('user', 'id', $inquiry->userto);
                $with = fullname($userwith);
            } else {
                // interrupt
                $userfrom = $DB->get_record('user', array('id'=>$inquiry->userfrom));
                $userto   = $DB->get_record('user', array('id'=>$inquiry->userto));
                $with = fullname($userfrom).' - '.fullname($userto);
            }
            $mform->addElement('static', 'userto', get_string('consultationwith', 'mod_consultation').':', $with);
            $mform->addElement('static', 'subject', get_string('subject', 'mod_consultation').':', format_string($inquiry->subject));
        }
        $size = $full ? array('cols'=>80, 'rows'=>20) : array('cols'=>40, 'rows'=>15);
        $mform->addElement('htmleditor', 'message',  get_string('message', 'mod_consultation'), $size);
        $mform->setType('message', PARAM_RAW); // cleaned before printing or editing
        $mform->addRule('message', get_string('required'), 'required', null, 'client');
        $mform->setHelpButton('message', array('reading', 'writing', 'questions', 'richtext'), false, 'editorhelpbutton');
        $mform->addElement('format', 'messageformat',  get_string('format'));

        if (!empty($post->attachment)) {
            $mform->addElement('static', 'attachmentname', get_string('currentattachment', 'mod_consultation'), $post->attachment);
            $mform->addElement('checkbox', 'deleteattachment', get_string('deleteattachment', 'mod_consultation'));
        }

        $mform->addElement('file', 'attachment', get_string('attachment', 'mod_consultation'));

        if (!empty($post->attachment)) {
            $mform->disabledIf('attachment', 'deleteattachment', 'checked');
        }

        $mform->addElement('hidden', 'inquiryid');
        $mform->setType('inquiryid', PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        if ($post) {
            $this->add_action_buttons($full, get_string('addmynewpost', 'mod_consultation'));
        } else {
            $this->add_action_buttons($full);
        }

        // initialise current data values (db values or defaults)
        $this->set_data($post);
    }
}
