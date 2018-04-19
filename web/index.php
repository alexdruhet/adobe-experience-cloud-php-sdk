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
use Pixadelic\Adobe\Client\CampaignStandard;
use Symfony\Component\Yaml\Yaml;

/**
 * Load and prepare config
 */
$config = Yaml::parseFile($appRoot.'/app/config/config.yml');
if (isset($config['adobe']['campaign']['credentials']['private_key'])) {
    $config['adobe']['campaign']['credentials']['private_key'] = $appRoot.'/'.$config['adobe']['campaign']['credentials']['private_key'];
}

$data = [];

/**
 * Getting access token
 */
$accessToken = new AccessToken($config['adobe']['campaign']['credentials']);
$data['AccessToken'] = $accessToken->get();

/**
 * CampaignStandard client example
 */
$campaignClient = new CampaignStandard($config['adobe']['campaign']['credentials']);
$data['CampaignStandard.profileMetadata'] = $campaignClient->getProfileMetadata();
$data['CampaignStandard.profiles'] = $campaignClient->getProfiles();
$data['CampaignStandard.profiles.email'] = $campaignClient->getProfiles(10, 'email');
$data['CampaignStandard.profiles.email.next10'] = $campaignClient->getNext($data['CampaignStandard.profiles.email']);
$data['CampaignStandard.profileByEmail'] = $campaignClient->getProfileByEmail(end($data['CampaignStandard.profiles.email.next10']->content));
$data['CampaignStandard.updateProfile.before'] = $campaignClient->getProfileByEmail('alex.druhet@gmail.com');
$data['CampaignStandard.updateProfile.processing'] = $campaignClient->updateProfile($data['CampaignStandard.updateProfile.before']->content[0]->PKey, ['preferredLanguage' => 'fr_fr']);
$data['CampaignStandard.updateProfile.after'] = $campaignClient->getProfileByEmail('alex.druhet@gmail.com');
//$data['CampaignStandard.getSubscriptionsByProfile'] = $campaignClient->getSubscriptionsByProfile($data['CampaignStandard.updateProfile.before']->content[0]->subscriptions->href);
//$data['CampaignStandard.getResource.postalAddress'] = $campaignClient->getResource('postalAddress');


?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Adobe Experience Cloud PHP SDK examples</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #fafafa;
        }

        h1 {
            margin: 0;
            padding: 1rem 1.5rem;
            font-size: 24px;
            line-height: 1.1;
        }

        pre {
            margin: 0;
        }

        .toggler {
            cursor: pointer;
        }

        .togglable {
            display: none;
            box-sizing: border-box;
            padding: 0 1.5rem 1rem;
        }

        .togglable-opened {
            display: block;
        }
    </style>
</head>
<body>
<?php foreach ($data as $key => $value) : ?>
    <section>
        <h1 class="toggler"><?php print $key ?></h1>
        <div class="togglable">
            <?php var_dump($value); ?>
        </div>
    </section>
<?php endforeach; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/randomcolor/0.5.2/randomColor.min.js"></script>
<script>
    (function (document, randomColor) {
        "use strict";
        var togglables = document.querySelectorAll(".togglable"),
            i          = 0;

        function init(togglable, i) {
            var toggler     = togglable.parentElement.querySelector(".toggler"),
                togglerHtml = toggler.innerHTML,
                color       = randomColor({
                    luminosity: "light",
                    format: "hsla"
                });

            toggler.style.backgroundColor   = color;
            togglable.style.backgroundColor = color;
            toggler.innerHTML += " +";

            toggler.addEventListener("click", function () {
                if (togglable.classList.contains("togglable-opened")) {
                    togglable.classList.remove("togglable-opened");
                    toggler.innerHTML = togglerHtml + " +";
                } else {
                    togglable.classList.add("togglable-opened");
                    toggler.innerHTML = togglerHtml + " -";
                }
            }, false);
        }

        if (togglables.length) {
            for (i = 0; i < togglables.length; i += 1) {
                init(togglables[i], i);
            }
        }
    }(document, randomColor));
</script>
</body>
</html>