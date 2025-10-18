<?php

use Webman\Route;
use support\Request;
use Kmin\Template;
use support\Response;

/**
 * 获取目录下所有文件（包含子目录）
 *
 * @param string $rootDir 根目录
 * @return \Generator 所有文件路径
 */
function get_dir_files(string $rootDir): \Generator
{
    // 使用栈结构替代递归
    $dirStack = [$rootDir];
    while (!empty($dirStack)) {
        $currentDir = array_pop($dirStack);
        // 打开目录句柄
        if (!$dh = @opendir($currentDir)) continue;
        while (($item = readdir($dh)) !== false) {
            if ($item === '.' || $item === '..') continue;
            $path = $currentDir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $dirStack[] = $path;  // 目录入栈
            } else {
                yield $path;  // 生成文件路径
            }
        }
        closedir($dh);
    }
}

/**
 * 渲染javascript模板
 *
 * @param array $data
 * @return Response
 */
function javascript_template(array $data): Response
{
    // 配置模板引擎
    $options = [
        'view_path' => $data['view_path'],
        'cache_path' => runtime_path() . '/component/' . $data['cache'],
        'view_suffix' => 'php',
        'is_code' => true
    ];
    // 实例化模板引擎
    $views = new Template($options);
    // 渲染模板
    $content = $views->fetch($data['template'], $data['vars']);
    return response($content, 200, ['Content-Type' => 'application/javascript']);
}

// 组件路由
if (config('plugin.kmin.template.app.component.enable')) { // 开启组件路由
    // 组件目录
    $componentDir = config('plugin.kmin.template.app.component.route');
    // 遍历目录
    foreach ($componentDir as $key => $dir) {
        if (trim($key) == "") { // 组件路由键不能为空
            throw new Exception('The component route key cannot be empty.');
        }
        // 遍历目录下所有文件
        foreach (get_dir_files($dir) as $file) {
            // 组件路由路径
            $route = str_replace(["\\", "//", "\\\\"], '/', $key . str_replace($dir, '', $file));
            // 路由
            Route::any($route, function (Request $request) use ($key, $dir, $file) {
                // 后缀为.php时，渲染javascript模板
                if (str_ends_with($file, '.php')) {
                    // 模板路径
                    $template = str_replace([$dir, '.php'], [''], $file);
                    $vars = []; // 视图变量
                    if (isset($request->_view_vars)) {
                        $vars = (array)$request->_view_vars;
                    }
                    // 渲染javascript模板
                    return javascript_template([
                        'view_path' => $dir,
                        'template' => $template,
                        'cache' => $key,
                        'vars' => $vars
                    ]);
                } else {
                    // 其他文件，直接返回文件内容
                    $template = file_get_contents($file);
                    // 返回文件内容，设置Content-Type为文件类型
                    return response($template, 200, ['Content-Type' => 'text/' . pathinfo($file, PATHINFO_EXTENSION)]);
                }
            });
        }
    }
}
