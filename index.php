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

    // TV5 - Khu quan tri
    'admin/dashboard'             => ['AdminDashboardController', 'index'],
    'admin/login'                 => ['AdminAuthController', 'login'],
    'admin/logout'                => ['AdminAuthController', 'logout'],
    'admin/lang'                  => ['AdminCommonController', 'lang'],
    'admin/profile'               => ['AdminProfileController', 'index'],
    'admin/settings'              => ['AdminSettingsController', 'index'],
    'admin/manage/pets'              => ['AdminPetController', 'index'],
    'admin/manage/pets/form'         => ['AdminPetController', 'form'],
    'admin/manage/pets/status'       => ['AdminPetController', 'status'],
    'admin/manage/products'          => ['AdminProductController', 'index'],
    'admin/manage/products/form'     => ['AdminProductController', 'form'],
    'admin/manage/products/status'   => ['AdminProductController', 'status'],
    'admin/manage/categories'        => ['AdminCategoryController', 'index'],
    'admin/manage/categories/save'   => ['AdminCategoryController', 'save'],
    'admin/manage/categories/toggle' => ['AdminCategoryController', 'toggle'],

    'admin/search'                => ['AdminSearchController', 'index'],
    'admin/orders/export'         => ['AdminOrderController', 'export'],

    'admin/orders'                => ['AdminOrderController', 'index'],
    'admin/orders/show'           => ['AdminOrderController', 'show'],
    'admin/orders/status'         => ['AdminOrderController', 'status'],
    'admin/orders/invoice'        => ['AdminOrderController', 'invoice'],

    'admin/users'                 => ['AdminUserController', 'index'],
    'admin/users/show'            => ['AdminUserController', 'show'],
    'admin/users/status'          => ['AdminUserController', 'status'],

    'admin/coupons'               => ['AdminCouponController', 'index'],
    'admin/coupons/form'          => ['AdminCouponController', 'form'],
    'admin/coupons/toggle'        => ['AdminCouponController', 'toggle'],

    'admin/reviews'               => ['AdminReviewController', 'index'],
    'admin/reviews/status'        => ['AdminReviewController', 'status'],

    'admin/posts'                 => ['AdminPostController', 'index'],
    'admin/posts/form'            => ['AdminPostController', 'form'],
    'admin/posts/status'          => ['AdminPostController', 'status'],
    'admin/posts/categories'      => ['AdminPostController', 'categories'],
    'admin/posts/categories/save' => ['AdminPostController', 'categorySave'],
    'admin/posts/categories/delete' => ['AdminPostController', 'categoryDelete'],

    // TV5 - Chatbot tu van (khach hoi, admin quan ly)
    'chatbot/ask'                 => ['ChatbotController', 'ask'],
    'chatbot/reset'               => ['ChatbotController', 'reset'],
    'admin/chatbot'               => ['AdminChatbotController', 'index'],
    'admin/chatbot/faq'           => ['AdminChatbotController', 'faqSave'],
    'admin/chatbot/faq/toggle'    => ['AdminChatbotController', 'faqToggle'],
    'admin/chatbot/reindex'       => ['AdminChatbotController', 'reindex'],
    'admin/chatbot/test'          => ['AdminChatbotController', 'test'],
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