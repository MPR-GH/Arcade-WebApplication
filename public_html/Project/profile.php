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
<div>
    Total Points: <?php echo get_total_points($user_id)?>
    Best Score: <?php echo get_best_score($user_id); ?>
</div>
<div>
    <?php $scores = get_latest_scores($user_id); ?>
    <h3>Score History</h3>
    <table class="table text-light">
        <thead>
            <th>Score</th>
            <th>Time</th>
        </thead>
        <tbody>
            <?php foreach ($scores as $score) : ?>
                <tr>
                    <td><?php se($score, "score", 0); ?></td>
                    <td><?php se($score, "created", "-"); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if (!$edit) : ?>
    <div>Username: <?php se($username); ?></div>
    <div>Joined: <?php se($created); ?></div>
    <!-- TODO any other public info -->
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