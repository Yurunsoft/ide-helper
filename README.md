# ide-helper

[![Latest Version](https://img.shields.io/packagist/v/yurunsoft/ide-helper.svg)](https://packagist.org/packages/yurunsoft/ide-helper)
[![Php Version](https://img.shields.io/badge/php-%3E=7.0-brightgreen.svg)](https://secure.php.net/)
[![IMI License](https://img.shields.io/github/license/yurunsoft/ide-helper.svg)](https://github.com/yurunsoft/ide-helper/blob/master/LICENSE)

## 介绍

让 PHP 扩展支持 IDE 代码提示，通过在 composer.json 配置，可以支持所有扩展，包括但不限于 Swoole、Redis 等

技术支持群: 17916227[![点击加群](https://pub.idqqimg.com/wpa/images/group.png "点击加群")](https://jq.qq.com/?_wv=1027&k=5wXf4Zq)，如有问题可以及时解答和修复。

## 使用

在项目中引入`yurunsoft/ide-helper`，然后在`composer.json`的`extra`中配置。

如下所示，可以生成 `swoole`、`redis` 的代码提示帮助文件。

> 支持所有 PHP 扩展，前提是你的环境中有安装。

```php
{
    "require-dev": {
        "yurunsoft/ide-helper": "~1.0"
    },
    "extra": {
        "ide-helper": {
            "list": [
                "swoole",
                "redis"
            ]
        }
    }
}
```

最后执行`composer update`即可。
