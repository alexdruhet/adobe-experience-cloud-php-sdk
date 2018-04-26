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

$data = [];

try {

    /**
     * Load and prepare config
     */
    $config = Yaml::parseFile($appRoot.'/app/config/config.yml');
    if (isset($config['adobe']['campaign']['private_key'])) {
        $config['adobe']['campaign']['private_key'] = $appRoot.'/'.$config['adobe']['campaign']['private_key'];
    }

    /**
     * Getting access token
     */
    $accessToken = new AccessToken($config['adobe']['campaign']);
    $accessToken->flush();
    $data['AccessToken'] = $accessToken->get();

    /**
     * CampaignStandard client example
     */
    $campaignClient = new CampaignStandard($config['adobe']['campaign']);
    $campaignClient->flush();
    $data['CampaignStandard.getProfileMetadata'] = $campaignClient->getProfileMetadata();
    //$data['CampaignStandard.getResource.postalAddress'] = $campaignClient->getResource('postalAddress');
    $data['CampaignStandard.getProfiles'] = $campaignClient->getProfiles();
    $data['CampaignStandard.getProfiles.email'] = $campaignClient->getProfiles(10, 'email');
    $data['CampaignStandard.getProfiles.email.next10'] = $campaignClient->getNext($data['CampaignStandard.getProfiles.email']);

//$data['CampaignStandard.profiles.extended'] = $campaignClient->getProfilesExtended();
//$data['CampaignStandard.profiles.extended.email'] = $campaignClient->getProfilesExtended(10, 'email');
//$data['CampaignStandard.profiles.extended.email.next10'] = $campaignClient->getNext($data['CampaignStandard.profiles.extended.email']);

    $data['CampaignStandard.getProfileByEmail'] = $campaignClient->getProfileByEmail(end($data['CampaignStandard.getProfiles.email.next10']->content));

    $data['CampaignStandard.getProfileByEmail.before'] = $campaignClient->getProfileByEmail('alex.druhet@gmail.com');
    $data['CampaignStandard.updateProfile.processing'] = $campaignClient->updateProfile(
        $data['CampaignStandard.getProfileByEmail.before']->content[0]->PKey,
        ['preferredLanguage' => 'fr_fr']
    );
    $data['CampaignStandard.getProfileByEmail.after'] = $campaignClient->getProfileByEmail('alex.druhet@gmail.com');

    $data['CampaignStandard.getSubscriptionsByProfile'] = $campaignClient->getSubscriptionsByProfile($data['CampaignStandard.getProfileByEmail.before']);
    $data['CampaignStandard.getServices'] = $campaignClient->getServices();

    $data['CampaignStandard.addSubscriptions.fail'] = $campaignClient->addSubscription(
        $data['CampaignStandard.getProfileByEmail.before'],
        $data['CampaignStandard.getServices']->content[0]
    );

    if (isset($data['CampaignStandard.getSubscriptionsByProfile']->content[0]) && is_object($data['CampaignStandard.getSubscriptionsByProfile']->content[0])) {
        $data['CampaignStandard.deleteSubscription'] = $campaignClient->deleteSubscription($data['CampaignStandard.getSubscriptionsByProfile']->content[0]);
    }

    $data['CampaignStandard.addSubscriptions.success'] = $campaignClient->addSubscription(
        $data['CampaignStandard.getProfileByEmail.before'],
        $data['CampaignStandard.getServices']->content[0]
    );

    if (isset($data['CampaignStandard.updateProfile.after']->content[0]->businessId)) {
        $data['CampaignStandard.profile.extended'] = $campaignClient->getProfileExtended($data['CampaignStandard.getProfileByEmail.after']->content[0]->businessId);
    }
} catch (Exception $e) {
}

?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Adobe Experience Cloud PHP SDK examples</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #fafafa;
            line-height: 1.5;
            color: #222;
        }

        h1 {
            margin: 0;
            padding: 1rem 1.5rem;
            font-size: 24px;
            line-height: 1.2;
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

        .error {
            padding: 1rem 1.5rem;
            background: #ff2600;
            color: #fff;
        }

        .error strong {
            color: #222;
            font-size: 9rem;
            float: left;
            display: block;
            padding: 0 1.5rem 0 0;
            margin-bottom: -1rem;
            line-height: 1;
        }

        .error h1 {
            padding: 0 0 1rem 0;
            float: left;
            max-width: 70%;
            overflow: auto;
        }

        .error pre {
            clear: both;
            max-width: 100%;
            overflow: auto;
            color: #efefef;
        }

    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/styles/rainbow.min.css">
</head>
<body>
<?php foreach ($data as $key => $value) : ?>
    <section>
        <h1 class="toggler"><?php print $key ?></h1>
        <div class="togglable">
            <pre><code><?php print var_export($value, true); ?></code></pre>
        </div>
    </section>
<?php endforeach; ?>
<?php if (isset($e)) : ?>
    <section class="error">
        <strong><?php print $e->getCode(); ?></strong>
        <h1><?php print $e->getMessage(); ?></h1>
        <pre><?php print $e->getTraceAsString(); ?></pre>
    </section>
<?php endif; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/randomcolor/0.5.2/randomColor.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/highlight.min.js"></script>
<script>
    (function (document, randomColor) {
        "use strict";
        let togglables = document.querySelectorAll(".togglable"),
            i          = 0;

        function init(togglable, i) {
            let toggler     = togglable.parentElement.querySelector(".toggler"),
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

        hljs.initHighlightingOnLoad();

    }(document, randomColor));
</script>
</body>
</html>