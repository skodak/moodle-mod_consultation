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
 * Consultation settings and defaults
 *
 * @package    mod
 * @subpackage consultation
 * @copyright  2009 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('consultationmodeditdefaults', get_string('modeditdefaults', 'mod_consultation'), get_string('modeditdefaultsexplain', 'mod_consultation')));

    $advstr = get_string('advanced').' ';

    $options = array (0=>get_string('no'), 1=>1, 5=>5, 10=>10, 30=>30, 60=>60);
    $settings->add(new admin_setting_configselect_with_advanced('consultation/edittime', get_string('modedittime', 'mod_consultation'), get_string('modedittimeexplain', 'mod_consultation'), array('value'=>0, 'adv'=>false), $options));

    $options = array (0=>get_string('no'), 1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6, 7=>7, 8=>8, 9=>9, 10=>10, 25=>25, 50=>50, 100=>100);
    $settings->add(new admin_setting_configselect_with_advanced('consultation/openlimit', get_string('modopenlimit', 'mod_consultation'), get_string('modopenlimitexplain', 'mod_consultation'), array('value'=>0, 'adv'=>false), $options));

    $strdays = ' '.get_string('days');
    $options = array (0=>get_string('never'), 7=>'7'.$strdays, 14=>'14'.$strdays, 30=>'30'.$strdays, 150=>'150'.$strdays, 365=>'365'.$strdays);
    $settings->add(new admin_setting_configselect_with_advanced('consultation/deleteafter', get_string('moddeleteafter', 'mod_consultation'), get_string('moddeleteafterexplain', 'mod_consultation'), array('value'=>0, 'adv'=>false), $options));

    $options = array(0=>get_string('no'), 1=>get_string('yes'));
    $settings->add(new admin_setting_configselect_with_advanced('consultation/notify', get_string('modnotify', 'mod_consultation'), get_string('modnotifyexplain', 'mod_consultation'), array('value'=>0, 'adv'=>false), $options));
}
