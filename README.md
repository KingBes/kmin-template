# kmin-template

一 个 用 于 开 发 kmin.js 的 webman 模 板 引 擎，也 可 以 说 是 js 模 板 引 擎 。

[kmin.js](http://kminjs.kllxs.top/) 

[webman](https://www.workerman.net/doc/webman/)

- php 8.4 及以上版本

## 安装

1. composer 安装

```bash
composer require kmin/template
```

2. 修改配置`config/view.php` 为

```php
<?php
use Kmin\View;

return [
    'handler' => View::class
];
```

3. 例子如下

`app/controller/UserController.php` 

```php
<?php
namespace app\controller;

use support\Request;

class UserController
{
    public function hello(Request $request)
    {
        // 也可以使用公共函数 km_view 来渲染模板, 这样就不用修改配置文件
        return view('user/hello', ['name' => 'webman']);
    }
}
```

> 注意：尽可能在`script` 标签里面完成, 但不是必须的

`app/view/user/hello.php`文件

```php
<script>
    console.log('hello kmStr($name)');
</script>
```

## 配置说明

`config/plugin/kmin/template/app.php` 文件

```php
<?php
return [
    'enable' => true,
    'component' => [
        'enable' => true, // 是否开启组件
        'dir' => [ // 组件路由目录 /kmin/component
            "/" => app_path('component'), // “/” => 表示路由: /kmin/component/xxx
            // "/mm" => app_path('mm'), // “/mm” => 表示路由: /kmin/component/mm/xxx
            // ... 其他组件目录
        ]
    ],
];
```

### 例子

`"/" => app_path('component')`：

路由: `/kmin/component/hello`
文件： `app/component/hello.php`

```php
<script>
    console.log('hello 我是组件');
</script>
```

> 注意：必须在`script` 标签里面完成, 它只响应 `script` 标签里面的内容, 也就是说它响应的是`js`(`<script src="/kmin/component/hello"></script>`)。

具体组件路由逻辑看 `config/plugin/kmin/template/route.php` 文件(懂哥可操)

## 模板引擎

### 入门布局

在试图文件里的根目录可以创建 `main.php` 文件, 它是所有试图文件的布局文件。

`app/view/main.php` 文件

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    kmContent() // 这里是视图文件的内容,因为试图文件都是整个 script 标签输出的
</body>
</html>
```

### 变量输出

在视图文件里可以使用 `kmStr($name)` 来输出变量 `$name`。

`app/view/user/hello.php` 文件

```php
<script>
    console.log('hello kmStr($name)');
</script>
```

| 变量 | 说明 |
| --- | --- |
| `kmStr($str)` | 输出字符串变量 `$str` |
| `kmNum($num)` | 输出数字变量 `$num` |
| `kmVar($var)` | 输出变量 `$var` |
| `kmJson($array)` | 输出 json 变量 `$array` |
| `kmBool($bool)` | 输出布尔变量 `$bool` |
| `kmInclude($file)` | 包含文件 `$file` 会在试图目录查找文件 |


可以使用原生PHP代码

```php
<?php echo 'Hello,world!'; ?>
```