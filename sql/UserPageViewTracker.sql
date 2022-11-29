CREATE TABLE IF NOT EXISTS /*_*/user_page_views (
	user_id INT(5) UNSIGNED NOT NULL,
	page_id INT(8) UNSIGNED NOT NULL,
	hits INT(10) UNSIGNED NOT NULL,
	last CHAR(14) DEFAULT NULL,
	PRIMARY KEY ( user_id, page_id )
);
