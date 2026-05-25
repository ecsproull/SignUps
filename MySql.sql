SELECT ID, u.user_login, u.user_nicename, u.user_email, u.display_name, m.meta_value
FROM wp_users AS u
LEFT JOIN wp_usermeta AS m ON u.ID = m.user_id
WHERE m.meta_key = 'nickname' AND meta_value NOT REGEXP '^[0-9]{4}$'; 

SELECT DEFINER, ROUTINE_NAME, ROUTINE_TYPE
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = 'wordpress';

SELECT DEFINER, TRIGGER_NAME
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = 'wordpress';

SELECT DEFINER, TABLE_NAME
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA = 'wordpress';

SHOW TRIGGERS;

DROP TRIGGER upd_att;
DROP TRIGGER del_att;

DROP TRIGGER ins_rol_att;
DROP TRIGGER upd_rol_att;
DROP TRIGGER del_rol_att;

DROP TABLE wp_spidercalendar_calendar;
DROP TABLE wp_spidercalendar_theme;
DROP TABLE wp_spidercalendar_widget_theme;