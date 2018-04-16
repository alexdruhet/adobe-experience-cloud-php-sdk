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
use Pixadelic\Adobe\Client\Campaign;
use Symfony\Component\Yaml\Yaml;

$config = Yaml::parseFile($appRoot.'/app/config/config.yml');
if (isset ($config['adobe']['campaign']['credentials']['private_key'])) {
    $config['adobe']['campaign']['credentials']['private_key'] = $appRoot.'/'.$config['adobe']['campaign']['credentials']['private_key'];
}

/**
 * Getting access token
 */
//$accessToken = new AccessToken($config['adobe']['campaign']['credentials']);
//echo var_dump($accessToken->get(true));


/**
 * Campaign client example
 */
$campaignClient = new Campaign($config['adobe']['campaign']['credentials']);
$profileMetadata = $campaignClient->getMetadata('profile');
var_dump($profileMetadata);