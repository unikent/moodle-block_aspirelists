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

defined('MOODLE_INTERNAL') || die();

// Copyright (c) Talis Education Limited, 2013
// Released under the LGPL Licence - http://www.gnu.org/licenses/lgpl.html. Anyone is free to change or redistribute this code.

class block_aspirelists extends block_base {

    /**
     * Init function
     */
    public function init() {
        $this->title = get_string('aspirelists', 'block_aspirelists');
    }

    /**
     * Required JS
     */
    public function get_required_javascript() {
        global $COURSE;

        parent::get_required_javascript();

        $this->page->requires->string_for_js('ajaxwait', 'block_aspirelists');
        $this->page->requires->string_for_js('ajaxerror', 'block_aspirelists');

        $this->page->requires->js_init_call('M.block_aspirelists.init', array(
            $COURSE->id,
            $COURSE->shortname
        ));

        $this->page->requires->css(new moodle_url('/blocks/aspirelists/styles.css'));
    }

    /**
     * Get Block Content
     */
    public function get_content() {
        global $OUTPUT;

        $this->content = new stdClass();
        $this->content->text = $OUTPUT->box($OUTPUT->pix_icon('y/loading', 'Loading...'), 'centered_cell block_loading', 'aspire_block_contents');
        $this->content->footer = '';
        return $this->content;
    }

    /**
     * This block has configuration
     */
    public function has_config() {
        return false;
    }
}
