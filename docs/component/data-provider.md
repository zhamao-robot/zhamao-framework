# 存储管理（文件）

DataProvider 是框架内提供的一个简易的文件管理类。

定义：`\ZM\Utils\DataProvider`

## DataProvider::getWorkingDir()

同 `working_dir()`。

## DataProvider::getFrameworkLink()

同 `ZMConfig::get("global", "http_reverse_link")`，获取反向代理的链接。

## DataProvider::getDataFolder()

获取配置项 `zm_data` 指定的目录。

## DataProvider::saveToJson()

将变量内容保存为 json 格式的文件，存储在 `zm_data/config/` 目录下或子目录下。

定义：`saveToJson($filename, $file_array)`

`$filename` 是文件名，不需要加后缀，比如你想保存成 `foo/bar.json`，这里写 `foo/bar` 就好。如果不想要二级目录，就直接写 `bar`，不需要加 `.json` 后缀。

这里只支持二级目录，不支持更多级的子目录。

`$file_array` 为内容，一般是数组，比如你缓存了一个 API 接口返回的数据，然后直接解析成数组后丢给它就好了。

## DataProvider::loadFromJson()

从 json 文件加载内容至变量。

定义：`loadFromJson($filename)`

文件名同上 `saveToJson()` 的定义，解析后的返回值为原先的内容或 `null`（如果文件不存在或 json 解析失败）。