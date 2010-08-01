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
 * Consultation setup form
 *
 * @package    mod
 * @subpackage consultation
 * @copyright  2009 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_consultation_mod_form extends moodleform_mod {

    function definition() {

        $mform =& $this->_form;

        $config = get_config('consultation');

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('modname', 'consultation'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('htmleditor', 'intro', get_string('modintro', 'consultation'));
        $mform->setType('intro', PARAM_RAW);
        $mform->addRule('intro', null, 'required', null, 'client');
        $mform->addElement('format', 'introformat', get_string('format'));

        $options = array (0=>get_string('no'), 1=>1, 5=>5, 10=>10, 30=>30, 60=>60);
        $mform->addElement('select', 'edittime', get_string('modedittime', 'consultation'), $options);
        $mform->setDefault('edittime', $config->edittime);
        $mform->setAdvanced('edittime', $config->edittime_adv);

        $options = array (0=>get_string('no'), 1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6, 7=>7, 8=>8, 9=>9, 10=>10, 25=>25, 50=>50, 100=>100);
        $mform->addElement('select', 'openlimit', get_string('modopenlimit', 'consultation'), $options);
        $mform->setDefault('openlimit', $config->openlimit);
        $mform->setAdvanced('openlimit', $config->openlimit_adv);

        $strdays = ' '.get_string('days');
        $options = array (0=>get_string('never'), 7=>'7'.$strdays, 14=>'14'.$strdays, 30=>'30'.$strdays, 150=>'150'.$strdays, 365=>'365'.$strdays);
        $mform->addElement('select', 'deleteafter', get_string('moddeleteafter', 'consultation'), $options);
        $mform->setDefault('deleteafter', $config->deleteafter);
        $mform->setadvanced('deleteafter', $config->deleteafter_adv);

        $mform->addElement('advcheckbox', 'notify', get_string('modnotify', 'consultation'));
        $mform->setDefault('notify', $config->notify);
        $mform->setAdvanced('notify', $config->notify_adv);

//-------------------------------------------------------------------------------
        $features = new object();
        $features->groups           = true;
        $features->groupings        = true;
        $features->groupmembersonly = true;
        $this->standard_coursemodule_elements($features);

//-------------------------------------------------------------------------------
        $this->add_action_buttons();
    }
}
