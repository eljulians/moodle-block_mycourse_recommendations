<?php

require_once('../../config.php');
require_once('classes/db/database_helper.php');

$db = new \block_mycourse_recommendations\database_helper();

$recommendationid = optional_param('recommendationid', 0, PARAM_INT);
$modname = optional_param('modname', '', PARAM_TEXT);
$moduleid = optional_param('moduleid', 0, PARAM_INT);

$db->increment_recommendation_view($recommendationid);

$resourceurl = new \moodle_url("/mod/$modname/view.php", array('id' => $moduleid));

redirect($resourceurl);
