<?php
require_once(__DIR__ . "/../../partials/nav.php");
is_logged_in(true);
$db = getDB();
//handle join
if (isset($_POST["join"])) {
    $user_id = get_user_id();
    $comp_id = se($_POST, "comp_id", 0, false);
    $cost = se($_POST, "join_fee", 0, false);
    $balance = get_total_points(get_user_id());
    join_competition($comp_id, $user_id, $cost);
}

$per_page = 10;
paginate("SELECT count(1) as total FROM Competitions WHERE paid_out < 1");


//handle page load
//TODO fix join
$query = "SELECT Competitions.id, name, min_participants, current_participants, current_reward, expires, min_score, join_fee, IF(comp_id is null, 0, 1) as joined,  CONCAT(first_place_per,'% - ', second_place_per, '% - ', third_place_per, '%') as place FROM Competitions
LEFT JOIN (SELECT * FROM CompetitionParticipants WHERE user_id = :uid) as uc ON uc.comp_id = Competitions.id WHERE paid_out < 1 ORDER BY expires ASC LIMIT :offset, :count";
/*$stmt = $db->prepare("SELECT BGD_Competitions.id, title, min_participants, current_participants, current_reward, expires, creator_id, min_score, join_cost, IF(competition_id is null, 0, 1) as joined,  CONCAT(first_place,'% - ', second_place, '% - ', third_place, '%') as place FROM BGD_Competitions
JOIN BGD_Payout_Options on BGD_Payout_Options.id = BGD_Competitions.payout_option
LEFT JOIN BGD_UserComps on BGD_UserComps.competition_id = BGD_Competitions.id WHERE user_id = :uid AND expires > current_timestamp() AND did_payout < 1 AND did_calc < 1 ORDER BY expires desc");*/
// $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
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
<div class="container-fluid">
    <h1>Archived Competitions</h1>
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
                        <td>
                            <?php if (se($row, "joined", 0, false)) : ?>
                                <button class="btn btn-primary disabled" onclick="event.preventDefault()" disabled>Already Joined</button>
                            <?php else : ?>
                                <form method="POST">
                                    <input type="hidden" name="comp_id" value="<?php se($row, 'id'); ?>" />
                                    <input type="hidden" name="cost" value="<?php se($row, 'join_fee', 0); ?>" />
                                    <input type="submit" name="join" class="btn btn-primary" value="Join (Cost: <?php se($row, "join_fee", 0) ?>)" />
                                </form>
                            <?php endif; ?>
                        </td>
                        <td><a class="btn btn-secondary" href="view_competition.php?id=<?php se($row, 'id'); ?>">View</a></td>
                        <td><a class="btn btn-secondary" href="edit_competitions.php?comp_id=<?php se($row, 'id'); ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="100%">No active competitions</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php include(__DIR__ . "/../../partials/pagination.php"); ?>
</div>
<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>