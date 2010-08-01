<?php

// This file is part of Consultation module for Moodle.
//
// Consultation is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 2 of the License, or
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
 * Consultation settings - backported from HEAD for now
 *
 * @package   mod-consultation
 * @copyright 2009 Tim Hunt
 *            2009 Petr Skoda (http://skodak.org)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */


/**
 * Dropdown menu with an advanced checkbox, that controls a additional $name.'_adv' setting.
 */
class consultation_setting_configselect_with_advanced extends admin_setting_configselect {
    public function consultation_setting_configselect_with_advanced($name, $visiblename, $description, $defaultsetting, $choices) {
        parent::admin_setting_configselect($name, $visiblename, $description, $defaultsetting, $choices);
    }

    /**
     * Loads the current setting and returns array
     *
     * @return array Returns array value=>xx, adv=>xx
     */
    public function get_setting() {
        $value = parent::get_setting();
        $adv = $this->config_read($this->name.'_adv');
        if (is_null($value) or is_null($adv)) {
            return NULL;
        }
        return array('value' => $value, 'adv' => $adv);
    }

    /**
     * Saves the new settings passed in $data
     *
     * @todo Add vartype handling to ensure $data is an array
     * @param array $data
     * @return mixed string or Array
     */
    public function write_setting($data) {
        $error = parent::write_setting($data['value']);
        if (!$error) {
            $value = empty($data['adv']) ? 0 : 1;
            $this->config_write($this->name.'_adv', $value);
        }
        return $error;
    }

    public function output_select_html($data, $current, $default, $extraname = '') {
        if (!$this->load_choices() or empty($this->choices)) {
            return array('', '');
        }

        $warning = '';
        if (is_null($current)) {
            // first run
        } else if (empty($current) and (array_key_exists('', $this->choices) or array_key_exists(0, $this->choices))) {
            // no warning
        } else if (!array_key_exists($current, $this->choices)) {
            $warning = get_string('warningcurrentsetting', 'admin', s($current));
            if (!is_null($default) and $data == $current) {
                $data = $default; // use default instead of first value when showing the form
            }
        }

        $selecthtml = '<select id="'.$this->get_id().'" name="'.$this->get_full_name().$extraname.'">';
        foreach ($this->choices as $key => $value) {
            // the string cast is needed because key may be integer - 0 is equal to most strings!
            $selecthtml .= '<option value="'.$key.'"'.((string)$key==$data ? ' selected="selected"' : '').'>'.$value.'</option>';
        }
        $selecthtml .= '</select>';
        return array($selecthtml, $warning);
    }
    
    /**
     * Return XHTML for the control
     *
     * @param array $data Default data array
     * @param string $query
     * @return string XHTML to display control
     */
    public function output_html($data, $query='') {
        $default = $this->get_defaultsetting();
        $current = $this->get_setting();

        list($selecthtml, $warning) = $this->output_select_html($data['value'],
                $current['value'], $default['value'], '[value]');
        if (!$selecthtml) {
            return '';
        }

        if (!is_null($default) and array_key_exists($default['value'], $this->choices)) {
            $defaultinfo = array();
            if (isset($this->choices[$default['value']])) {
                $defaultinfo[] = $this->choices[$default['value']];
            }
            if (!empty($default['adv'])) {
                $defaultinfo[] = get_string('advanced');
            }
            $defaultinfo = implode(', ', $defaultinfo);
        } else {
            $defaultinfo = '';
        }

        $adv = !empty($data['adv']);
        $return = '<div class="form-select defaultsnext">' . $selecthtml .
                ' <input type="checkbox" class="form-checkbox" id="' .
                $this->get_id() . '_adv" name="' . $this->get_full_name() .
                '[adv]" value="1" ' . ($adv ? 'checked="checked"' : '') . ' />' .
                ' <label for="' . $this->get_id() . '_adv">' .
                get_string('advanced') . '</label></div>';

        return format_admin_setting($this, $this->visiblename, $return, $this->description, true, $warning, $defaultinfo, $query);
    }
}
