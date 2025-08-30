# kmin-template

一 个 用 于 开 发 kmin.js 的 php 模 板 引 擎

## 要求

- php 8.4 及以上版本
- ext-dom 扩展

## 安装

```bash
composer require kmin/template
```

## 配置

可以在实例化 `Kmin\Template` 类的时候传入模板引擎的配置参数

```php
$config = [
	'view_path'	    =>	'./template/',
	'cache_path'	=>	'./runtime/'
];
$template = new \Kmin\Template($config);
```

## 渲染模板

和常规的模板引擎一样，只需要传入模板文件的文件名和模板变量即可

```php
$template->fetch('index', ['name' => 'kmin']);
```

在`kmin.js`中通常接收的组件不是字符串而是js代码,所以响应时要返回js格式

```php
$body = $template->fetch('index', ['name' => 'kmin']);
// 返回js格式
return response($body,200,['Content-Type'=>'text/javascript']);
```

## 模板格式

```html
<template>
    <!-- 你自己的html代码 -->
</template>
<script type="module">
    // 你自己的js代码
    customElements.define('这里和KMim.js的组件名称要求一样', class extends KMin {
        // 你的KMim.js的组件内容
    })
</script>
<style>
    /* 你自己的css代码 */
</style>
```

生成结果:（生成对于的`css`函数和`render`函数）

```js
// 你自己的js代码
customElements.define('这里和KMim.js的组件名称要求一样', class extends KMin {
    css() {
        return `
            /* 你自己的css代码 */
        `
    }
    render() {
        return `
            <!-- 你自己的html代码 -->
        `
    }
    // 你的KMim.js的组件内容
})
```

## 变量输出

在模板中输出变量的方法很简单,我们可以在模板任意地方使用

```php
$body = $template->fetch('index', ['name' => 'kmin']);
// 返回js格式
return response($body,200,['Content-Type'=>'text/javascript']);
```

输出变量:

- 字符串 `kmStr(变量名)`
- 数字 `kmNum(变量名)`
- 布尔值 `kmBool(变量名)`
- json字符串 `kmJson(变量名)`
- `kmVar(变量名)` 输出变量的原始值

例如1:

```html
<template>
    <div>
        <p>姓名: kmStr($name)</p>
    </div>
</template>
```

例如2:

```html
<template>
    ...
</template>
<script type="module">
    customElements.define('组件名', class extends KMin {
        data = this.state({
            name: kmStr($name), // 兼容js代码
        })
    })
</script>
```

## webman 框架

公共模板方法

```php
/**
 * kmin view response
 *
 * @param mixed $template 模板文件名
 * @param array $vars 模板变量
 * @param string|null $app 应用名称
 * @param string|null $plugin 插件名称
 * @return Response
 */
function kmin_view(
    mixed $template = null,
    array $vars = [],
    ?string $app = null,
    ?string $plugin = null
): Response 
```
