CREATE TABLE IF NOT EXISTS `db_push` (
    `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` bigint UNSIGNED NOT NULL,
    `notify_id` bigint UNSIGNED NOT NULL,
    PRIMARY KEY (`id`)
) AUTO_INCREMENT=1;