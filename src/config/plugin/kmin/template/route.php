<?php

use Webman\Route;
use support\Request;
use Kmin\Template;

if (config('plugin.kmin.template.app.component.enable')) {
    Route::any('/kmin/[{path:.+}]', function (
        Request $request,
        string $path
    ) {
        $path = '/' . $path;
        if (str_starts_with($path, '/component/')) {
            $template = substr($path, 10);
            $componentDir = config('plugin.kmin.template.app.component.dir');
            $cache = "";
            foreach ($componentDir as $key => $dir) {
                $file = str_replace('/', DIRECTORY_SEPARATOR, $dir .
                    substr($template, strlen($key == "/" ? "" : $key)) . '.php');
                if (file_exists($file)) {
                    $template = substr($template, strlen($key));
                    $viewPath = $dir;
                    $cache = $key == "/" ? "" : $key;
                    break;
                }
            }
            $options = [
                'view_path' => $viewPath,
                'cache_path' => runtime_path() . '/component/' . $cache,
                'view_suffix' => 'php',
                'is_code' => true
            ];
            $views = new Template($options);
            $vars = [];
            if (isset($request->_view_vars)) {
                $vars = (array)$request->_view_vars;
            }
            $content = $views->fetch($template, $vars);
        } else {
            return response('404 Not Found', 404, []);
        }
        return response($content, 200, ['Content-Type' => 'application/javascript']);
    });
}
