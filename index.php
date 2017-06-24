<?php

/**
 * ECTouch E-Commerce Project
 *
 * @package  ECTouch
 * @author   carson <wanganlin@ecmoban.com>
 */

define('IN_ECTOUCH', true);


/*
|--------------------------------------------------------------------------
| Setting Debug Mode
| 提示：如果堂鼠提示您“网址错误”，可将该配置启用为 true 来显示错误异常。
| 注意：具体问题解决后一定要将该配置设置为 false 来通过日志收集异常。
|--------------------------------------------------------------------------
*/

 //define('APP_DEBUG', true);

/*
|--------------------------------------------------------------------------
| Load Application Configuration
|--------------------------------------------------------------------------
*/

require __DIR__ . '/bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
*/

require __DIR__ . '/bootstrap/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
*/

ECTouch::start();
