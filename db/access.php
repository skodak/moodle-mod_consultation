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
 * Capability definitions for the consultation module.
 *
 * @package    mod
 * @subpackage consultation
 * @copyright  2009-2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'mod/consultation:addinstance' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/course:manageactivities'
    ),

    'mod/consultation:open' => array( // start consultation with somebody with mod/consultation:answer capability
        'riskbitmask' => RISK_SPAM,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'student' => CAP_ALLOW,
        ),
    ),


    'mod/consultation:answer' => array( // override if you want student-to-student consultations
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        ),
    ),

    'mod/consultation:openany' => array( // start consultation with anybody in course
        'riskbitmask' => RISK_SPAM,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        ),
    ),

    'mod/consultation:resolve' => array( // resolve consultation

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        ),
    ),

    'mod/consultation:reopen' => array( // mark participating inquiry active again

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
        ),
    ),

    'mod/consultation:reopenany' => array( // mark any inquiry active again

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
        ),
    ),

    'mod/consultation:resolveany' => array( // resolve any consultation

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
        ),
    ),

    'mod/consultation:viewany' => array( // can view any consultations
        'riskbitmask' => RISK_PERSONAL,

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
        ),
        'clonepermissionsfrom' => 'mod/consultation:viewall'
    ),

    'mod/consultation:interrupt' => array( // can interrupt other people talking in consultation == post in consultations of others
        'riskbitmask' => RISK_SPAM,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
        ),
    ),

    'mod/consultation:deleteany' => array( // can delete any posts or inquiries
        'riskbitmask' => RISK_DATALOSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetype' => array(
        ),
    ),
);
