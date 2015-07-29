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
 * Aspire Lists block API.
 *
 * @package    block_aspirelists
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_aspirelists;

defined('MOODLE_INTERNAL') || die();

require_once("{$CFG->libdir}/externallib.php");

use external_api;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_function_parameters;

/**
 * Aspire Lists block API.
 */
class api extends external_api
{
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_lists_parameters() {
        return new external_function_parameters(array(
            'courseid' => new external_value(
                PARAM_INT,
                'The course ID',
                VALUE_DEFAULT,
                ''
            )
        ));
    }

    /**
     * Expose to AJAX
     * @return boolean
     */
    public static function get_lists_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns the course's reading lists.
     *
     * @return array[string]
     */
    public static function get_lists($courseid) {
        global $DB;

        $params = self::validate_parameters(self::get_lists_parameters(), array(
            'courseid' => $courseid
        ));
        $courseid = $params['courseid'];

        $course = $DB->get_record('course', array(
            'id' => $courseid
        ), 'shortname', \MUST_EXIST);

        // Extract shortcodes.
        $api = new \mod_aspirelists\core\API();
        $shortcodes = $api->extract_shortcodes($course->shortname);

        // Build Lists.
        $lists = array();
        foreach ($shortcodes as $match) {
            $lists = array_merge($lists, $api->get_lists($match));
        }

        // For merged modules.
        $lists = array_unique($lists);

        // Return them in a suitable format.
        $formatted = array();
        foreach ($lists as $list) {
            $formatted[] = array(
                'name' => $list->get_name(),
                'url' => $list->get_url(),
                'items' => $list->get_item_count(),
                'campus' => $list->get_campus(),
                'lastupdated' => $list->get_last_updated(true)
            );
        }

        if (empty($formatted)) {
            $message = \html_writer::tag('p', 'This Moodle course is not yet linked to the resource lists system.
            You may be able to find your list through searching the resource lists system,
            or you can consult your Moodle module or lecturer for further information.');

            if (has_capability('moodle/course:update', \context_course::instance($courseid))) {
                $message = <<<HTML5
                    <p>If your list is available on the <a href="http://resourcelists.kent.ac.uk">resource list</a>
                    system and you would like assistance in linking it to Moodle please contact
                    <a href="mailto:readinglisthelp@kent.ac.uk">Reading List Helpdesk</a>.</p>
HTML5;
            }

            throw new \moodle_exception($message);
        }

        return $formatted;
    }

    /**
     * Returns description of get_lists() result value.
     *
     * @return external_multiple_structure
     */
    public static function get_lists_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'name' => new external_value(PARAM_TEXT, 'Name of list'),
                    'url' => new external_value(PARAM_URL, 'URL of list'),
                    'items' => new external_value(PARAM_INT, 'Number of items in the list'),
                    'campus' => new external_value(PARAM_ALPHA, 'Name of the campus the lists are on (Canterbury/Medway)'),
                    'lastupdated' => new external_value(PARAM_TEXT, 'Last updated date')
                )
            )
        );
    }
}