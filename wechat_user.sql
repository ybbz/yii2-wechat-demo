CREATE TABLE `wechat_user` (
  `id` int(11) NOT NULL,
  `openid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nickname` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '微信昵称',
  `sex` tinyint(4) NOT NULL COMMENT '性别',
  `headimgurl` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '头像',
  `country` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '国家',
  `province` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '省份',
  `city` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '城市',
  `access_token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `refresh_token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `wechat_user`
  ADD PRIMARY KEY (`id`);
