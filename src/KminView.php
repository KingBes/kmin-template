<?php

declare(strict_types=1);

namespace Kmin;

use Webman\View;
use Kmin\Template;
use function app_path;
use function array_merge;
use function base_path;
use function config;
use function is_array;
use function request;
use function runtime_path;

class KminView implements View
{
    /**
     * Assign.
     * @param string|array $name
     * @param mixed $value
     */
    public static function assign($name, $value = null)
    {
        $request = request();
        $request->_view_vars = array_merge(
            (array) $request->_view_vars,
            is_array($name) ? $name : [$name => $value]
        );
    }

    /**
     * Render.
     * @param string $template
     * @param array $vars
     * @param string|null $app
     * @param string|null $plugin
     * @return false|string
     */
    public static function render(
        string $template,
        array $vars,
        string|null $app = null,
        string|null $plugin = null
    ): string {
        $request = request();
        $plugin = $plugin === null ? ($request->plugin ?? '') : $plugin;
        $app = $app === null ? ($request->app ?? '') : $app;
        $configPrefix = $plugin ? "plugin.$plugin." : '';
        $baseViewPath = $plugin ? base_path() . "/plugin/$plugin/app" : app_path();
        if ($template[0] === '/') {
            if (strpos($template, '/view/') !== false) {
                [$viewPath, $template] = explode('/view/', $template, 2);
                $viewPath = base_path("$viewPath/view/");
            } else {
                $viewPath = base_path() . dirname($template) . '/';
                $template = basename($template);
            }
        } else {
            $viewPath = $app === '' ? "$baseViewPath/view/" : "$baseViewPath/$app/view/";
        }
        $defaultOptions = [
            'view_path' => $viewPath,
            'cache_path' => runtime_path() . '/views/'
        ];
        $options = array_merge($defaultOptions, config("{$configPrefix}view.options", []));
        $mainFile = $viewPath . 'main.' . ($options['view_suffix'] ?? 'html');
        if (!file_exists($mainFile)) {
            file_put_contents($mainFile, <<<HTML
<!DOCTYPE html>
<html lang="zh-cn">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>kmin.js</title>
        <script src="/kmin/kmin.min.js"></script>
    </head>
    <body></body>
</html>
HTML);
        }
        $views = new Template($options);
        if (isset($request->_view_vars)) {
            $vars = array_merge((array)$request->_view_vars, $vars);
        }
        return $views->fetch($template, $vars);
    }

    /**
     * Component.
     * @param string $template
     * @param array $vars
     * @param string|null $app
     * @param string|null $plugin
     * @return false|string
     */
    public static function component(
        string $template,
        array $vars,
        string|null $app = null,
        string|null $plugin = null
    ): string {
        $request = request();
        $plugin = $plugin === null ? ($request->plugin ?? '') : $plugin;
        $app = $app === null ? ($request->app ?? '') : $app;
        $configPrefix = $plugin ? "plugin.$plugin." : '';
        $baseViewPath = $plugin ? base_path() . "/plugin/$plugin/app" : app_path();
        if ($template[0] === '/') {
            if (strpos($template, '/component/') !== false) {
                [$viewPath, $template] = explode('/component/', $template, 2);
                $viewPath = base_path("$viewPath/component/");
            } else {
                $viewPath = base_path() . dirname($template) . '/';
                $template = basename($template);
            }
        } else {
            $viewPath = $app === '' ? "$baseViewPath/component/" : "$baseViewPath/$app/component/";
        }
        $defaultOptions = [
            'view_path' => $viewPath,
            'cache_path' => runtime_path() . '/components/'
        ];
        $options = array_merge($defaultOptions, config("{$configPrefix}view.options", []));
        $views = new Template($options);
        if (isset($request->_view_vars)) {
            $vars = array_merge((array)$request->_view_vars, $vars);
        }
        return $views->fetch($template, $vars);
    }
}
