<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 06/04/2018
 * Time: 16:14
 */

$appRoot = __DIR__.'/..';

ini_set('error_log', $appRoot.'/var/log/php_error.log');
require $appRoot.'/vendor/autoload.php';

use Pixadelic\Adobe\Api\AccessToken;
use Symfony\Component\Yaml\Yaml;

$config = Yaml::parseFile($appRoot.'/app/config/config.yml');
if (isset ($config['adobe']['campaign']['credentials']['private_key'])) {
    $config['adobe']['campaign']['credentials']['private_key'] = $appRoot.'/'.$config['adobe']['campaign']['credentials']['private_key'];
}
$accessToken = new AccessToken($config['adobe']['campaign']['credentials']);
echo xdebug_var_dump($accessToken->get(true));