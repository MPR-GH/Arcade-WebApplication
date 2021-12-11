CREATE TABLE IF NOT EXISTS Competitions(
    -- Remember to refer to your proposal for your exact columns
    id int AUTO_INCREMENT PRIMARY KEY,
    name varchar(240) not null,             --user input *
    duration int default 3,                 --user input *
    expires TIMESTAMP DEFAULT (DATE_ADD(CURRENT_TIMESTAMP, INTERVAL duration DAY)),
    current_reward int DEFAULT (starting_reward),
    starting_reward int DEFAULT 1,          --user input *
    join_fee int default 0,                 --user input *
    current_participants int default 0,
    min_participants int DEFAULT 3,         --user input *
    paid_out tinyint(1) DEFAULT 0,
    min_score int DEFAULT 0,                --user input *
    first_place_per int DEFAULT 70,         --user input *
    second_place_per int DEFAULT 20,        --user input *
    third_place_per int DEFAULT 10,         --user input *
    cost_to_create int DEFAULT 5,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    check (min_score >= 0),
    check (starting_reward >= 1),
    check (current_reward >= starting_reward),
    check (min_participants >= 3),
    check (current_participants >= 0),
    check(join_fee >= 0),
    check (first_place_per + second_place_per + third_place_per = 100)
)