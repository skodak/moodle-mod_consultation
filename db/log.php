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
 * Definition of log events
 *
 * @package    mod
 * @subpackage consulation
 * @copyright  2010 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    array('module'=>'consultation', 'action'=>'view', 'mtable'=>'consultation', 'field'=>'name'),
    array('module'=>'consultation', 'action'=>'view all', 'mtable'=>'consultation', 'field'=>'name'),
    array('module'=>'consultation', 'action'=>'add', 'mtable'=>'consultation', 'field'=>'name'),
    array('module'=>'consultation', 'action'=>'update', 'mtable'=>'consultation', 'field'=>'name'),
    array('module'=>'consultation', 'action'=>'open inquiry', 'mtable'=>'consultation_inquiries', 'field'=>'subject'),
    array('module'=>'consultation', 'action'=>'view inquiry', 'mtable'=>'consultation_inquiries', 'field'=>'subject'),
    array('module'=>'consultation', 'action'=>'participate inquiry', 'mtable'=>'consultation_inquiries', 'field'=>'subject'),
    array('module'=>'consultation', 'action'=>'resolve inquiry', 'mtable'=>'consultation_inquiries', 'field'=>'subject'),
    array('module'=>'consultation', 'action'=>'reopen inquiry', 'mtable'=>'consultation_inquiries', 'field'=>'subject'),
);
