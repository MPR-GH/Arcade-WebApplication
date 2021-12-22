<?php
require(__DIR__ . "/../../partials/nav.php");
?>
<h1>Home</h1>
<h2><a href="<?php echo get_url('game.php'); ?>">Try out our main game</a></h2>
<?php

if (is_logged_in(true)) {
    echo "Welcome home, " . get_username();
    //comment this out if you don't want to see the session variables
    //echo "<pre>" . var_export($_SESSION, true) . "</pre>";
    $duration = "day";
    require(__DIR__ . "/../../partials/score_table.php");
    $duration = "week";
    require(__DIR__ . "/../../partials/score_table.php");
    $duration = "month";
    require(__DIR__ . "/../../partials/score_table.php");
    $duration = "lifetime";
    require(__DIR__ . "/../../partials/score_table.php");
}
?>
<?php
require(__DIR__ . "/../../partials/flash.php");
?>