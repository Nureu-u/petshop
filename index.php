<?php
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';

/** route => [Controller, method] */
$routes = [
    'home'                   => ['PetController', 'index'],

    // Tài khoản
    'auth/login'             => ['AuthController', 'login'],
    'auth/register'          => ['AuthController', 'register'],
    'auth/logout'            => ['AuthController', 'logout'],
    'auth/profile'           => ['AuthController', 'profile'],
    'auth/forgot'            => ['AuthController', 'forgot'],
    'auth/reset'             => ['AuthController', 'reset'],

    // Công khai
    'pets'                   => ['PetController', 'index'],
    'pets/show'              => ['PetController', 'show'],
    'products'               => ['ProductController', 'index'],
    'products/show'          => ['ProductController', 'show'],

    // Quản trị thú cưng
    'admin/pets'             => ['PetController', 'adminIndex'],
    'admin/pets/form'        => ['PetController', 'form'],
    'admin/pets/status'      => ['PetController', 'setStatus'],

    // Quản trị sản phẩm
    'admin/products'         => ['ProductController', 'adminIndex'],
    'admin/products/form'    => ['ProductController', 'form'],
    'admin/products/status'  => ['ProductController', 'setStatus'],

    // Quản trị danh mục (type=pet|product)
    'admin/categories'        => ['CategoryController', 'index'],
    'admin/categories/save'   => ['CategoryController', 'save'],
    'admin/categories/toggle' => ['CategoryController', 'toggle'],
];

$route = isset($_GET['r']) && is_string($_GET['r']) ? $_GET['r'] : 'home';

if (!isset($routes[$route])) {
    abort(404, 'Trang không tồn tại.');
}

try {
    [$class, $method] = $routes[$route];
    (new $class())->$method();
} catch (Throwable $e) {
    error_log((string)$e);
    http_response_code(500);
    if (config('debug')) {
        echo '<pre>' . e((string) $e) . '</pre>';
    } else {
        echo 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.';
    }
}
