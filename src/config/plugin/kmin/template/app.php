<?php
return [
    'enable' => true,
    'component' => [
        'enable' => true, // 是否开启组件
        'route' => [ // 组件路由目录
            "/component" => app_path('component'), // “/component” => 表示路由: /component/xxx
            // "/mm" => app_path('mm'), // “/mm” => 表示路由: /mm/xxx
        ]
    ],
];
