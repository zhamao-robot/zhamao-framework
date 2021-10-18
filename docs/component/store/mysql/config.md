# 配置

炸毛框架的数据库组件支持原生 SQL、查询构造器，去掉了复杂的对象模型关联，同时默认为数据库连接池，使开发变得简单。

数据库的配置位于 `config/global.php` 文件的 `mysql_config` 段，见 [全局配置](../../../../guide/basic-config#mysql_config)。

如果 `mysql_config.host` 字段为空，则不创建数据库连接池，填写后将创建，且默认保持长连接。