CREATE TABLE IF NOT EXISTS /*_*/user_page_views (
	user_id INT(5) UNSIGNED NOT NULL,
	page_id INT(8) UNSIGNED NOT NULL,
	hits INT(10) UNSIGNED NOT NULL,
	last CHAR(14) DEFAULT NULL,
	PRIMARY KEY ( user_id, page_id )
);

CREATE OR REPLACE VIEW /*_*/user_page_hits AS SELECT
	u.user_name AS user_name,
	u.user_real_name AS user_real_name,
	p.page_namespace AS page_namespace,
	p.page_title AS page_title,
	v.hits AS hits,
	v.last AS last
FROM (/*_*/user u JOIN /*_*/page p) JOIN /*_*/user_page_views v
WHERE u.user_id = v.user_id AND p.page_id = v.page_id
ORDER BY u.user_id, v.hits DESC;
