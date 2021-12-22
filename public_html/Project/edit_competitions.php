<?php
require_once(__DIR__ . "/../../partials/nav.php");
is_logged_in(true);
$user_id = get_user_id();

$comp_id = se($_GET, "comp_id", 0, false);
$r = get_competition_info($comp_id);


if (isset($_POST) && !empty($_POST)) {
    $duration = se($_POST,"duration",0,false);
    $expire = date_increment(se($r,"created","",false),$duration);
    $_POST["expires"]=$expire;
    $db->beginTransaction();
    if (update_data("Competitions", $comp_id, $_POST)) {
        flash("Successfully updated competition", "success");
        $db->commit();
    } else {
        flash("error", "danger");
        $db->rollback();
    }
}

?>

<div class="container-fluid">
    <h1>Edit Competition : <?php se($r,"name","",true);?></h1>
    <form method="POST" onsubmit="return validate(this);">
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input id="name" name="name" class="form-control" disabled value=<?php se($r,"name","",true);?> />
        </div>
        <div class="mb-3">
            <label for="reward" class="form-label">Starting Reward</label>
            <input id="reward" type="number" name="starting_reward" class="form-control" placeholder=">= 1" min="1" disabled value=<?php se($r,"starting_reward","",true);?> />
        </div>
        <div class="mb-3">
            <label for="reward" class="form-label">Current Reward [This will change the winning reward for this competition] </label>
            <input id="reward" type="number" name="current_reward" class="form-control" placeholder=">= 1" min="1" value=<?php se($r,"current_reward","",true);?> />
        </div>
        <div class="mb-3">
            <label for="ms" class="form-label">Min. Score [This will change the base minimum score to qualify for this competition]</label>
            <input id="ms" name="min_score" type="number" class="form-control" placeholder=">= 0" min="0" value=<?php se($r,"min_score","",true);?> />
        </div>
        <div class="mb-3">
            <label for="mp" class="form-label">Min. Participants [This will change the base mimumun participants for this competition]</label>
            <input id="mp" name="min_participants" type="number" class="form-control" placeholder=">= 3" min="3" value=<?php se($r,"min_participants","",true);?> />
        </div>
        <div class="mb-3">
            <label for="jc" class="form-label">Join Fee [This will change the base Join Fee for this competition]</label>
            <input id="jc" name="join_fee" type="number" class="form-control" placeholder=">= 0" min="0" value=<?php se($r,"join_fee","",true);?> />
        </div>
        <div class="mb-3">
            <label for="duration" class="form-label">Duration (in Days) [This will change the duration of the competition from the competition's creation day <?php se($r,"created","",true);?>]</label>
            <input id="duration" name="duration" type="number" class="form-control" placeholder=">= 3" min="3" value=<?php se($r,"duration","",true);?> />
        </div>
        <div class="mb-3">
            <label for="po1" class="form-label">Payout First Place [This will change the payout for this competition]</label>
            <input id="po1" name="first_place_per" type="number" class="form-control" placeholder="70" max="100" min="0" value=<?php se($r,"first_place_per","",true);?> />
        </div>
        <div class="mb-3">
            <label for="po2" class="form-label">Payout Second Place [This will change the payout for this competition]</label>
            <input id="po2" name="second_place_per" type="number" class="form-control" placeholder="20" max="100" min="0" value=<?php se($r,"second_place_per","",true);?> />
        </div>
        <div class="mb-3">
            <label for="po3" class="form-label">Payout Third Place [This will change the payout for this competition]</label>
            <input id="po3" name="third_place_per" type="number" class="form-control" placeholder="10" max="100" min="0" value=<?php se($r,"third_place_per","",true);?> />
        </div>
        <div class="mb-3">
            <input type="submit" value="Update" class="btn btn-primary" />
        </div>
    </form>
    <script>
        function validate(form) {
            let po1 = parseInt(document.getElementById("po1").value);
            let po2 = parseInt(document.getElementById("po2").value);
            let po3 = parseInt(document.getElementById("po3").value);
            if(!(po1 + po2 + po3 == 100))    {
                flash("Please ensure that the relative payout for first,second,and third place equal 100", "danger");
                return false;
            }
            return true;
        }
    </script>
</div>
<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>