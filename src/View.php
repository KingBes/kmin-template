<?php

declare(strict_types=1);

namespace Kmin;

use Webman\View as WebmanView;
use Kmin\Template;
use function app_path;
use function array_merge;
use function base_path;
use function config;
use function is_array;
use function request;
use function runtime_path;

class View implements WebmanView
{
    /**
     * Assign.
     * @param string|array $name
     * @param mixed $value
     */
    public static function assign(string|array $name, $value = null)
    {
        $request = request();
        $request->_view_vars = array_merge(
            (array) $request->_view_vars,
            is_array($name) ? $name : [$name => $value]
        );
    }

    public static function render(
        string $template,
        array $vars,
        ?string $app = null,
        ?string $plugin = null
    ): string {
        $request = request();
        $plugin = $plugin === null ? ($request->plugin ?? '') : $plugin;
        $app = $app === null ? ($request->app ?? '') : $app;
        $configPrefix = $plugin ? "plugin.$plugin." : '';
        $viewSuffix = config("{$configPrefix}view.options.view_suffix", 'php');
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
            'cache_path' => runtime_path() . '/views/',
            'view_suffix' => $viewSuffix
        ];
        $options = array_merge($defaultOptions, config("{$configPrefix}view.options", []));
        $views = new Template($options);
        if (isset($request->_view_vars)) {
            $vars = array_merge((array)$request->_view_vars, $vars);
        }
        return $views->fetch($template, $vars);
    }
}
