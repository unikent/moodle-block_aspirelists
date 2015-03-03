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

require_login();
require_sesskey();

$id = required_param('id', PARAM_INT);

$PAGE->set_context(\context_course::instance($id));

$course = $DB->get_record('course', array(
    'id' => $id
), 'shortname');

if (!$course) {
    print_error("Invalid course specified!");
}

// Build Readinglists API object.
$api = new \mod_aspirelists\core\API();
$shortcodes = $api->extract_shortcodes($course->shortname);

// Build Lists.
$lists = array();
foreach ($shortcodes as $match) {
    $lists = array_merge($lists, $api->get_lists($match));
}

// Turn lists into block content.
$formattedlists = array();
foreach ($lists as $list) {
    $campus = $list->get_campus();
    if (!isset($formattedlists[$campus])) {
        $formattedlists[$campus] = array();
    }

    $listhtml = "<p><a href=\"" . s($list->get_url()) . "\" target=\"_blank\">" . s($list->get_name()) . "</a>";

    $count = $list->get_item_count();
    if ($count > 0) {
        $itemnoun = ($count == 1) ? "item" : "items";
        $listhtml .= " ({$count} {$itemnoun})";
    }

    $lastupdated = $list->get_last_updated(true);
    if (!empty($lastupdated)) {
        $listhtml .= ', last updated: ' . $lastupdated;
    }

    $listhtml .= "</p>";
    $formattedlists[$campus][] .= $listhtml;
}

$content = '';
foreach ($formattedlists as $campus => $lists) {
    $content .= '<h3>' . $campus . '</h3>';

    foreach ($lists as $list) {
        $content .= $list;
    }
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
