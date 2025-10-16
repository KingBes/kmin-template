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
