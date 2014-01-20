<?php

define('AJAX_SCRIPT', true);

/** Include config */
require_once(dirname(__FILE__) . '/../../config.php');

require_sesskey();

$id = required_param('id', PARAM_INT);
$shortname = required_param('shortname', PARAM_RAW);

$content = \mod_aspirelists\core\aspirelists::get_block_content($id, $shortname);

echo json_encode(array(
    "text" => $content->text,
    "footer" => $content->footer
));
