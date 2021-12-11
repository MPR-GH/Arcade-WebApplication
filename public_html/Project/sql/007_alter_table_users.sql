ALTER TABLE Users ADD COLUMN points INTEGER 
not null default 0
check (points >= 0)
COMMENT 'points field that tracks a users total points earned';