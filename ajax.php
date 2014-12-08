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

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . "/lib/readinglists/src/API.php");
require_once(dirname(__FILE__) . "/lib/readinglists/src/Parser.php");
require_once(dirname(__FILE__) . "/lib/readinglists/src/ReadingList.php");

require_login();
require_sesskey();

$id = required_param('id', PARAM_INT);

$PAGE->set_context(\context_course::instance($id));

$course = $DB->get_record('course', array(
    'id' => $id
));

if (!$course) {
    print_error("Invalid course specified!");
}

// Extract the shortnames.
$subject = strtolower($course->shortname);
preg_match_all("([a-z]{2,4}[0-9]{3,4})", $subject, $matches);
if (empty($matches)) {
    print_error("Invalid course specified!");
}

// Build MUC object.
$cache = \cache::make('block_aspirelists', 'data');

// Build Readinglists API object.
$api = new \unikent\ReadingLists\API();
$api->set_cache_layer($cache);
$api->set_timeout(3000);
$api->set_timeperiod(get_config('aspirelists', 'timeperiod'));

// Build Lists.
$lists = array();
foreach ($matches as $match) {
    $lists = array_merge($lists, $api->get_lists($match[0]));
}

// Turn lists into block content.
$content = '';
foreach ($lists as $list) {
    $count = $list->get_item_count();
    if ($count <= 0) {
        continue;
    }

    $content .= '<h3 style="margin-bottom: 2px;">' . $list->get_campus() . '</h3>';

    // Get a friendly, human readable noun for the items.
    $itemnoun = ($count == 1) ? "item" : "items";

    // Finally, we're ready to output information to the browser.
    $content .= "<p><a href='" . $list->get_url() . "'>" . $list->get_name() . "</a>";

    if ($count > 0) {
        $content .= " ({$count} {$itemnoun})";
    }

    $lastupdated = $list->get_last_updated(true);
    if (!empty($lastupdated)) {
        $content .= ', last updated: ' . $lastupdated;
    }

    $content .= "</p>";
}

if (empty($content)) {
    if (!has_capability('moodle/course:update', \context_course::instance($id))) {
        $content = <<<HTML
            <p>This Moodle course is not yet linked to the resource lists system.
            You may be able to find your list through searching the resource lists system,
            or you can consult your Moodle module or lecturer for further information.</p>
HTML;
    } else {
        $content = <<<HTML
            <p>If your list is available on the <a href="http://resourcelists.kent.ac.uk">resource list</a>
            system and you would like assistance in linking it to Moodle please contact
            <a href="mailto:readinglisthelp@kent.ac.uk">Reading List Helpdesk</a>.</p>
HTML;
    }
}

echo $OUTPUT->header();
echo json_encode(array(
    "text" => trim($content),
    "footer" => ''
));
