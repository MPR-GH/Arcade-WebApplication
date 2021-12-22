<?php
require_once(__DIR__ . "/../../partials/nav.php");
//is_logged_in(true);
/**
 * Logic:
 * Check if query params have an id
 * If so, use that id
 * Else check logged in user id
 * otherwise redirect away
 */
$user_id = se($_GET, "id", get_user_id(), false);
error_log("user id $user_id");
$isMe = $user_id === get_user_id();
//!! makes the value into a true or false value regardless of the data https://stackoverflow.com/a/2127324
$edit = !!se($_GET, "edit", false, false); //if key is present allow edit, otherwise no edit
if ($user_id < 1) {
    flash("Invalid user", "danger");
    redirect("home.php");
    //die(header("Location: home.php"));
}
?>
<?php
if (isset($_POST["save"]) && $isMe && $edit) {
    $db = getDB();
    $email = se($_POST, "email", null, false);
    $username = se($_POST, "username", null, false);
    $hasError = false;
    //sanitize
    $email = sanitize_email($email);
    //validate
    if (!is_valid_email($email)) {
        flash("Invalid email address", "danger");
        $hasError = true;
    }
    if (!preg_match('/^[a-z0-9_-]{3,16}$/i', $username)) {
        flash("Username must only be alphanumeric and can only contain - or _", "danger");
        $hasError = true;
    }
    if (!$hasError) {
        $params = [":email" => $email, ":username" => $username, ":id" => get_user_id()];
        
        $stmt = $db->prepare("UPDATE Users set email = :email, username = :username where id = :id");
        try {
            $stmt->execute($params);
        } catch (Exception $e) {
            users_check_duplicate($e->errorInfo);
        }
    }
    //select fresh data from table
    $stmt = $db->prepare("SELECT id, email, IFNULL(username, email) as `username` from Users where id = :id LIMIT 1");
    try {
        $stmt->execute([":id" => get_user_id()]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            //$_SESSION["user"] = $user;
            $_SESSION["user"]["email"] = $user["email"];
            $_SESSION["user"]["username"] = $user["username"];
        } else {
            flash("User doesn't exist", "danger");
        }
    } catch (Exception $e) {
        flash("An unexpected error occurred, please try again", "danger");
        //echo "<pre>" . var_export($e->errorInfo, true) . "</pre>";
    }


    //check/update password
    $current_password = se($_POST, "currentPassword", null, false);
    $new_password = se($_POST, "newPassword", null, false);
    $confirm_password = se($_POST, "confirmPassword", null, false);
    if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
        if ($new_password === $confirm_password) {
            //TODO validate current
            $stmt = $db->prepare("SELECT password from Users where id = :id");
            try {
                $stmt->execute([":id" => get_user_id()]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if (isset($result["password"])) {
                    if (password_verify($current_password, $result["password"])) {
                        $query = "UPDATE Users set password = :password where id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->execute([
                            ":id" => get_user_id(),
                            ":password" => password_hash($new_password, PASSWORD_BCRYPT)
                        ]);

                        flash("Password reset", "success");
                    } else {
                        flash("Current password is invalid", "warning");
                    }
                }
            } catch (Exception $e) {
                echo "<pre>" . var_export($e->errorInfo, true) . "</pre>";
            }
        } else {
            flash("New passwords don't match", "warning");
        }
    }

    $vb = se($_POST, "visib", null, false);
    $tv = ($vb=="private") ? (0) : (1);
    $visib = get_visibility($user_id);
    $vis = ($visib=="Private") ? (0) : (1);
    if($tv != $vis)   {
        update_visibility($user_id,$tv);
    }
}
?>

<?php
$email = get_user_email();
$username = get_username();
$created = "";
$public = false;
//TODO pull any other public info you want
$db = getDB();
$stmt = $db->prepare("SELECT username, created, visibility from Users where id = :id");
try {
    $stmt->execute([":id" => $user_id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("user: " . var_export($r, true));
    $username = se($r, "username", "", false);
    $created = se($r, "created", "", false);
    $public = se($r, "visibility", 0, false) > 0;
    if (!$public && !$isMe) {
        flash("User's profile is private", "warning");
        redirect("home.php");
        //die(header("Location: home.php"));
    }
} catch (Exception $e) {
    echo "<pre>" . var_export($e->errorInfo, true) . "</pre>";
}
?>
<h1>Profile</h1>
<?php if ($isMe) : ?>
    <?php if ($edit) : ?>
        <a class="btn btn-primary" href="?">View</a>
    <?php else : ?>
        <a class="btn btn-primary" href="?edit=true">Edit</a>
    <?php endif; ?>
<?php endif; ?>
<?php
$per_page = 10;
paginate("SELECT count(1) as total FROM Scores WHERE user_id=:uid",[":uid"=>$user_id]);


//handle page load
$query =    "SELECT score, created
            FROM Scores
            WHERE user_id=:uid
            ORDER BY created ASC
            LIMIT :offset, :count";

$stmt = $db->prepare($query);
$params = [];
$results = [];
$params[":offset"] = $offset;
$params[":count"] = $per_page;
$params[":uid"] = get_user_id();

foreach ($params as $key => $value) {
    $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key, $value, $type);
}
$params = null; //set it to null to avoid issues

try {
    $stmt->execute($params); //dynamically populated params to bind
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    flash("<pre>" . var_export($e, true) . "</pre>");
}
?>
<div>
    <?php $scores = get_latest_scores($user_id); ?>
    <h3>Score History</h3>
    <table class="table text-light">
        <thead>
            <th>Score</th>
            <th>Time</th>
        </thead>
        <tbody>
            <?php if (count($results) > 0) : ?>
                <?php foreach ($results as $row) : ?>
                    <tr>
                        <td><?php se($row, "score"); ?></td>
                        <td><?php se($row, "created"); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="100%">No Score History</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php include(__DIR__ . "/../../partials/pagination.php"); ?>
</div>
<?php if (!$edit) : ?>
    <div>Username: <?php se($username); ?></div>
    <div>Joined: <?php se($created); ?></div>
    <div>Total Points: <?php echo get_total_points($user_id)?></div>
    <div>Best Score: <?php echo get_best_score($user_id); ?></div>
    <!-- TODO any other public info -->
    <?php
    $per_page = 10;
    paginate("SELECT count(1) as total FROM CompetitionParticipants WHERE user_id = :uid",[":uid" => $user_id]);    
    
    //handle page load
    //TODO fix join
    $query = "SELECT Competitions.id, name, min_participants, current_participants, current_reward, expires, min_score, join_fee, IF(comp_id is null, 0, 1) as joined,  CONCAT(first_place_per,'% - ', second_place_per, '% - ', third_place_per, '%') as place FROM Competitions
    RIGHT JOIN (SELECT * FROM CompetitionParticipants WHERE user_id = :uid) as uc ON uc.comp_id = Competitions.id ORDER BY expires ASC LIMIT :offset, :count";
    /*$stmt = $db->prepare("SELECT BGD_Competitions.id, title, min_participants, current_participants, current_reward, expires, creator_id, min_score, join_cost, IF(competition_id is null, 0, 1) as joined,  CONCAT(first_place,'% - ', second_place, '% - ', third_place, '%') as place FROM BGD_Competitions
    JOIN BGD_Payout_Options on BGD_Payout_Options.id = BGD_Competitions.payout_option
    LEFT JOIN BGD_UserComps on BGD_UserComps.competition_id = BGD_Competitions.id WHERE user_id = :uid AND expires > current_timestamp() AND did_payout < 1 AND did_calc < 1 ORDER BY expires desc");*/
    // $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $stmt = $db->prepare($query);
    $params = [];
    $results = [];
    $params[":offset"] = $offset;
    $params[":count"] = $per_page;
    $params[":uid"] = $user_id;
    
    foreach ($params as $key => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $type);
    }
    $params = null; //set it to null to avoid issues
    
    try {
        $stmt->execute($params); //dynamically populated params to bind
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($r) {
            $results = $r;
        }
    } catch (PDOException $e) {
        flash("<pre>" . var_export($e, true) . "</pre>");
    }
?>
<div class="container-fluid">
    <h1>Competition History</h1>
    <h2>Your Points: <?php echo get_total_points(get_user_id())?></h2>
    <table class="table text-light">
        <thead>
            <th>Title</th>
            <th>Participants</th>
            <th>Reward</th>
            <th>Min Score</th>
            <th>Expires</th>
            <th>Actions</th>
        </thead>
        <tbody>
            <?php if (count($results) > 0) : ?>
                <?php foreach ($results as $row) : ?>
                    <tr>
                        <td><?php se($row, "name"); ?></td>
                        <td><?php se($row, "current_participants"); ?>/<?php se($row, "min_participants"); ?></td>
                        <td><?php se($row, "current_reward"); ?><br>Payout: <?php se($row, "place", "-"); ?></td>
                        <td><?php se($row, "min_score"); ?></td>
                        <td><?php se($row, "expires", "-"); ?></td>
                        <td><a class="btn btn-secondary" href="view_competition.php?id=<?php se($row, 'id'); ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="100%">No Competition History</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php include(__DIR__ . "/../../partials/pagination.php"); ?>
</div>
<?php endif; ?>

<?php if ($isMe && $edit) : ?>
    <form method="POST" onsubmit="return validate(this);">
        <div class="mb-3">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?php se($email); ?>" />
        </div>
        <div class="mb-3">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" value="<?php se($username); ?>" />
        </div>
        <!-- DO NOT PRELOAD PASSWORD -->
        <div>Password Reset</div>
        <div class="mb-3">
            <label for="cp">Current Password</label>
            <input type="password" name="currentPassword" id="cp" />
        </div>
        <div class="mb-3">
            <label for="np">New Password</label>
            <input type="password" name="newPassword" id="np" />
        </div>
        <div class="mb-3">
            <label for="conp">Confirm Password</label>
            <input type="password" name="confirmPassword" id="conp" />
        </div>
        <div>Privacy</div>
        <?php 
            $visib = get_visibility($user_id);
        ?>
        <div class="mb-3">
            <label for="vb">Currently set to: <?php echo $visib; ?></label>
            <br>
            <select id="vb" name="visib">
                <option value="public">Public</option>
                <option value="private">Private</option>
            </select>
        </div>
        <input type="submit" value="Update Profile" name="save" />
    </form>
<?php endif; ?>

<script>
    function validate(form) {
        let pw = form.newPassword.value;
        let con = form.confirmPassword.value;
        let isValid = true;
        //TODO add other client side validation....

        //example of using flash via javascript
        //find the flash container, create a new element, appendChild
        if (pw !== con) {
            //find the container
            /*let flash = document.getElementById("flash");
            //create a div (or whatever wrapper we want)
            let outerDiv = document.createElement("div");
            outerDiv.className = "row justify-content-center";
            let innerDiv = document.createElement("div");
            //apply the CSS (these are bootstrap classes which we'll learn later)
            innerDiv.className = "alert alert-warning";
            //set the content
            innerDiv.innerText = "Password and Confirm password must match";
            outerDiv.appendChild(innerDiv);
            //add the element to the DOM (if we don't it merely exists in memory)
            flash.appendChild(outerDiv);*/
            flash("Password and Confirm password must match", "warning");
            isValid = false;
        }
        return isValid;
    }
</script>
<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>