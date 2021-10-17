# MySQL 数据库简介

炸毛框架的数据库组件对接了 MySQL 连接池，在使用过程中无需配置即可实现 MySQL 查询，同时拥有高并发。

目前 2.5 版本后炸毛框架底层采用了 `doctrine/dbal` 组件，可以方便地构建 SQL 语句。

本章大体查询内容均以下表 `users` 为基础：

| id | username | gender | update_time |
| -- | -------- | ------ | ----------- |
| 1 | jack | man | 2021-10-12 |
| 2 | rose | woman | 2021-10-11 |

#