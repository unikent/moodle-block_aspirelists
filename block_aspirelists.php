<?php

require_once(dirname(__FILE__) . '/../moodleblock.class.php');

// Copyright (c) Talis Education Limited, 2013
// Released under the LGPL Licence - http://www.gnu.org/licenses/lgpl.html. Anyone is free to change or redistribute this code.

class block_aspirelists extends block_base {
	function init() {
		$this->title = get_string('aspirelists', 'block_aspirelists');
	}

    /**
     * Required JS
     */
    public function get_required_javascript() {
        parent::get_required_javascript();

        global $COURSE;

        $this->page->requires->string_for_js('ajaxwait', 'block_aspirelists');
        $this->page->requires->string_for_js('ajaxerror', 'block_aspirelists');

        $this->page->requires->js_init_call('M.block_aspirelists.init', array(
        	$COURSE->id,
        	$COURSE->shortname
        ));
    }

	function get_content() {
        $this->content = new stdClass();
        $this->content->text = '<div id="aspirelists-block">Requesting reading lists...</div>';
        $this->content->footer = '';
        return $this->content;
	}

	function has_config() {
		return true;
	}
}
