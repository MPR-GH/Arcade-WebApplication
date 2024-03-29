<?php
require_once(__DIR__ . "/../../partials/nav.php");
is_logged_in(true);

$db = getDB();
$user_id = get_user_id();
//save
if (isset($_POST["name"]) && !empty($_POST["name"])) {
    $cost = (int)se($_POST, "starting_reward", 0, false);
    $cost++;                                                //Creator pays 1 + start_reward to create
    $cost += (int)se($_POST, "join_fee", 0, false);         //Creator auto-pays an additional cost of join_fee to participate in the competition
    $name = se($_POST, "name", "N/A", false);
    $balance = get_total_points($user_id);

    if ($balance >= $cost) {
        $db->beginTransaction();
        if (insert_points($user_id,$cost*-1,"Create Competition $name"))   {
            $comp_id = save_data("Competitions", $_POST);
            if ($comp_id > 0) {
                if (add_to_competition($comp_id, get_user_id())) {
                    flash("Successfully created competition", "success");
                    $db->commit();
                } else {
                    $db->rollback();
                }
            } else {
                $db->rollback();
            }
        } else {
            flash("There was a problem deducting points", "warning");
            $db->rollback();
        }
    } else {
        flash("You can't afford this right now", "warning");
    }
}
?>

<div class="container-fluid">
    <h1>Create Competition</h1>
    <h2>Your Points: <?php echo get_total_points($user_id)?></h2>
    <form method="POST" onsubmit="return validate(this);">
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input id="name" name="name" class="form-control" />
        </div>
        <div class="mb-3">
            <label for="reward" class="form-label">Starting Reward</label>
            <input id="reward" type="number" name="starting_reward" class="form-control" onchange="updateCost()" placeholder=">= 1" min="1" />
        </div>
        <div class="mb-3">
            <label for="ms" class="form-label">Min. Score</label>
            <input id="ms" name="min_score" type="number" class="form-control" placeholder=">= 0" min="0" />
        </div>
        <div class="mb-3">
            <label for="mp" class="form-label">Min. Participants</label>
            <input id="mp" name="min_participants" type="number" class="form-control" placeholder=">= 3" min="3" />
        </div>
        <div class="mb-3">
            <label for="jc" class="form-label">Join Fee</label>
            <input id="jc" name="join_fee" type="number" class="form-control" onchange="updateCost()" placeholder=">= 0" min="0" />
        </div>
        <div class="mb-3">
            <label for="duration" class="form-label">Duration (in Days)</label>
            <input id="duration" name="duration" type="number" class="form-control" placeholder=">= 3" min="3" />
        </div>
        <div class="mb-3">
            <label for="po1" class="form-label">Payout First Place</label>
            <input id="po1" name="first_place_per" type="number" class="form-control" placeholder="70" max="100" min="0">
        </div>
        <div class="mb-3">
            <label for="po2" class="form-label">Payout Second Place</label>
            <input id="po2" name="second_place_per" type="number" class="form-control" placeholder="20" max="100" min="0">
        </div>
        <div class="mb-3">
            <label for="po3" class="form-label">Payout Third Place</label>
            <input id="po3" name="third_place_per" type="number" class="form-control" placeholder="10" max="100" min="0">
        </div>
        <div class="mb-3">
            <input type="submit" value="Create Competition (Cost: 2)" class="btn btn-primary" />
        </div>
    </form>
    <script>
        function updateCost() {
            let starting = parseInt(document.getElementById("reward").value || 0) + 1;
            let join = parseInt(document.getElementById("jc").value || 0);
            if (join < 0) {
                join = 1;
            }
            let cost = starting + join;
            document.querySelector("[type=submit]").value = `Create Competition (Cost: ${cost})`;
        }
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