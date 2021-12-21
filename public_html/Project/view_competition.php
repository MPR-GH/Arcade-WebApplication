<?php
require_once(__DIR__ . "/../../partials/nav.php");
is_logged_in(true);
$db = getDB();
$comp_id = se($_GET,"id",0,false);

$duration = "lifetime";
$compTableParse = true;
require(__DIR__ . "/../../partials/score_table.php");
?>