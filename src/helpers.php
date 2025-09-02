<?php

use Kmin\KminView;
use support\Response;

if (!function_exists('km_view')) {
    /**
     * kmin view response
     *
     * @param mixed $template 模板文件名
     * @param array $vars 模板变量
     * @param string|null $app 应用名称
     * @param string|null $plugin 插件名称
     * @return Response
     */
    function km_view(
        mixed $template = null,
        array $vars = [],
        ?string $app = null,
        ?string $plugin = null
    ): Response {
        return new Response(
            200,
            [],
            KminView::render(...template_inputs($template, $vars, $app, $plugin))
        );
    }
}


if (!function_exists('km_component')) {
    /**
     * kmin view response
     *
     * @param mixed $template 模板文件名
     * @param array $vars 模板变量
     * @param string|null $app 应用名称
     * @param string|null $plugin 插件名称
     * @return Response
     */
    function km_component(
        mixed $template = null,
        array $vars = [],
        ?string $app = null,
        ?string $plugin = null
    ): Response {
        return new Response(
            200,
            ['Content-Type' => 'text/javascript'],
            KminView::component(...template_inputs($template, $vars, $app, $plugin))
        );
    }
}