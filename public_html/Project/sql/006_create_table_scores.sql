CREATE TABLE IF NOT EXISTS 'Scores'(
    id int AUTO_INCREMENT PRIMARY KEY,
    user_id int,
    score int,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id),
    check (score > 0)
)