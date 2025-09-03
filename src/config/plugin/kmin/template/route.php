<?php

use Webman\Route;

Route::any('/kmin/kmin.min.js', function () {
    $file = file_get_contents(
        base_path() . DIRECTORY_SEPARATOR .
            "vendor" . DIRECTORY_SEPARATOR .
            "kmin" . DIRECTORY_SEPARATOR .
            "template" . DIRECTORY_SEPARATOR .
            "src" . DIRECTORY_SEPARATOR .
            "kmin.js" . DIRECTORY_SEPARATOR .
            "kmin.min.js"
    );
    return response($file, 200, ['Content-Type' => 'application/javascript']);
});
