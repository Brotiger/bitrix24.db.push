CREATE TABLE IF NOT EXISTS `db_push` (
    `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` bigint UNSIGNED NOT NULL,
    `notify_id` bigint UNSIGNED NOT NULL,
    PRIMARY KEY (`id`)
) AUTO_INCREMENT=1;

CREATE INDEX b_im_message_author_id ON b_im_message (AUTHOR_ID);
CREATE INDEX b_im_message_notify_read ON b_im_message (NOTIFY_READ);
CREATE INDEX b_im_message_notify_module ON b_im_message (NOTIFY_MODULE);
CREATE INDEX db_push_user_id ON db_push (user_id);
CREATE INDEX db_push_notify_id ON db_push (notify_id);
CREATE INDEX b_user_personal_photo ON b_user (PERSONAL_PHOTO);