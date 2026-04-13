SELECT ID, u.user_login, u.user_nicename, u.user_email, u.display_name, m.meta_value
FROM wp_users AS u
LEFT JOIN wp_usermeta AS m ON u.ID = m.user_id
WHERE m.meta_key = 'nickname' AND meta_value NOT REGEXP '^[0-9]{4}$'; 