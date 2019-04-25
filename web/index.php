<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 06/04/2018
 * Time: 16:14
 */

// @codingStandardsIgnoreStart

$appRoot = __DIR__.'/..';
ini_set('error_log', $appRoot.'/var/log/php_error.log');
require $appRoot.'/vendor/autoload.php';
require $appRoot.'/web/utils.php';

use Pixadelic\Adobe\Api\AccessToken;
use Pixadelic\Adobe\Client\CampaignStandard;
use Symfony\Component\Yaml\Yaml;

/**
 * Load and prepare config
 */
$data = [];
$testEmail = null;
$testServiceName = null;
$testService = null;
$testProfile = null;
$newProfileTestEmail = null;
$campaignClient = null;
$testEventName = null;
$testEventPayload = [];
$testInstagram = '@test';
$testWorkflow = null;


if (isset($_GET['prod'])) {
    $config = Yaml::parseFile($appRoot.'/app/config/config_prod.yml');
    $env = 'production';
} else {
    $config = Yaml::parseFile($appRoot.'/app/config/config.yml');
    $env = 'staging';
}
if (isset($config['adobe']['campaign']['private_key'])) {
    $config['adobe']['campaign']['private_key'] = $appRoot.'/'.$config['adobe']['campaign']['private_key'];
}
if (isset($config['adobe']['campaign']['reconciliation_workflow_id'])) {
    $testWorkflow = $config['adobe']['campaign']['reconciliation_workflow_id'];
}
if (isset($config['parameters']['test_email'])) {
    $testEmail = $config['parameters']['test_email'];
}
if (isset($config['parameters']['test_instagram'])) {
    $testInstagram = $config['parameters']['test_instagram'];
}
if (isset($config['parameters']['new_profile_test_email'])) {
    $newProfileTestEmail = $config['parameters']['new_profile_test_email'];
}
if (isset($config['parameters']['test_service_name'])) {
    $testServiceName = $config['parameters']['test_service_name'];
}
if (isset($config['parameters']['test_event_name'])) {
    $testEventName = $config['parameters']['test_event_name'];
    $testEventPayload = [
        'email' => $newProfileTestEmail,
        'ctx' => [
            'senderemail' => $testEmail,
            "AC_language" => "default",
            "html" => '<span class="test">html test</span>',
            "searchlink" => "https://booking.iflya380.com/en?adultCount=2&class=0&departuredate=2018-08-10&destination=KWE&origin=TLS&returndate=2018-08-17&submit=1",
            "price" => "2003 €",
            "returndate" => "2018-08-17",
            "departuredate" => "2018-08-10",
            "destination" => "KWE",
            "origin" => "TLS",
            "sendername" => "John Doe",
        ],
    ];
}

/**
 * Getting access token test
 */
$accessToken = new AccessToken($config['adobe']['campaign']);
$accessToken->flush();
Utils::execute($accessToken, 'get');

/**
 * CampaignStandard client example
 */
$campaignClient = new CampaignStandard($config['adobe']['campaign']);
$campaignClient->flush();
$prefix = get_class($campaignClient).'->';

/**
 * Metadata and resource tests
 */
Utils::execute($campaignClient, 'getProfileMetadata');
Utils::execute($campaignClient, 'getProfileResources');
Utils::execute($campaignClient, 'getServiceResources');

/**
 * Events and transactional messages tests
 */
if ($testEventName) {
    Utils::execute($campaignClient, 'getEventMetadata', [$testEventName]);
    Utils::execute($campaignClient, 'sendEvent', [$testEventName, $testEventPayload]);
    if (isset($data[$prefix.'sendEvent']['success']) && isset($data[$prefix.'sendEvent']['success']['PKey'])) {
        $evtPKey = $data[$prefix.'sendEvent']['success']['PKey'];
        $evtStatus = null;
        $iMax = 5;
        $i = 0;
        while (!in_array($evtStatus, ['processed', 'ignored', 'deliveryFailed', 'tooOld'])) {
            getEvtStatus();
            if ($i++ === $iMax) {
                break;
            }
        }
        getEvtStatus();
    }
}

/**
 * Profiles list tests
 */
Utils::execute($campaignClient, 'getProfiles');
if (isset($data[$prefix.'getProfiles']['success'])) {
    Utils::execute($campaignClient, 'getNext', [$data[$prefix.'getProfiles']['success']]);
}
Utils::execute($campaignClient, 'getProfiles', [10, 'email']);
if (isset($data[$prefix.'getProfiles_alt']['success'])) {
    Utils::execute($campaignClient, 'getNext', [$data[$prefix.'getProfiles_alt']['success']]);
}

/**
 * Profile manipulation tests
 */
Utils::execute($campaignClient, 'getProfileByEmail', [$testEmail]);
if (isset($data[$prefix.'getProfileByEmail']['success'])) {
    $testProfile = $data[$prefix.'getProfileByEmail']['success']['content'][0];
} else {
    Utils::execute(
        $campaignClient,
        'createProfile',
        [
            [
                'preferredLanguage' => 'fr_fr',
                'email' => $testEmail,
                'Acquisition' => 'duplication_test1',
                'firstName' => 'Joe',
                'AppUser' => 'android',
                'lastName' => 'Gibbs',
            ],
        ]
    );
    if (isset($data[$prefix.'createProfile']['success'])) {
        $testProfile = $data[$prefix.'createProfile']['success'][0];
    }
}
Utils::execute($campaignClient, 'getProfileByEmail', [$testEmail]);
if (isset($data[$prefix.'getProfileByEmail_alt']['success'])) {
    $testProfile = $data[$prefix.'getProfileByEmail_alt']['success']['content'][0];
} else {
    Utils::execute(
        $campaignClient,
        'createProfile',
        [
            [
                'email' => $testEmail,
                'firstName' => 'Coxsone',
                'lastName' => 'Dodd',
                'AppUser' => 'android',
                'Acquisition' => 'duplication_test2',
                'preferredLanguage' => 'en_us',
            ],
        ]
    );
    if (isset($data[$prefix.'createProfile']['success'])) {
        $testProfile = $data[$prefix.'createProfile']['success'][0];
    }
}
Utils::execute($campaignClient, 'getProfileByEmail', ['test@iflya380.com']);
if ($testProfile && isset($testProfile['PKey'])) {
    Utils::execute(
        $campaignClient,
        'updateProfile',
        [
            $testProfile['PKey'],
            [
                'birthDate' => '1971-06-10',
                'preferredLanguage' => 'fr_frt',
                'Acquisition' => 'self granted 1',
                'InstagramUsername' => $testInstagram,
                'AppUser' => 'unknown',
                'LcahMember' => false,
                'badfield' => 'badvalue',
            ],
        ]
    );
    Utils::execute(
        $campaignClient,
        'updateProfile',
        [
            $testProfile['PKey'],
            [
                'birthDate' => '1972-08-02',
                'preferredLanguage' => 'fr_fr',
                'Acquisition' => 'self granted 2',
                'InstagramUsername' => $testInstagram,
                'AppUser' => 'toto',
                'LcahMember' => false,
            ],
        ]
    );
    Utils::execute(
        $campaignClient,
        'updateProfile',
        [
            $testProfile['PKey'],
            [
                'birthDate' => '1975-10-10',
                'preferredLanguage' => 'fr_fr',
                'Acquisition' => 'self granted 3',
                'InstagramUsername' => $testInstagram,
                'AppUser' => 'ios',
                'LcahMember' => 0,
            ],
        ]
    );
    Utils::execute(
        $campaignClient,
        'updateProfile',
        [
            $testProfile['PKey'],
            [
                'birthDate' => '1976-11-10',
                'preferredLanguage' => 'fr_fr',
                'Acquisition' => 'self granted 4',
                'InstagramUsername' => $testInstagram,
                'AppUser' => 'android',
                'LcahMember' => 1,
            ],
        ]
    );
    Utils::execute(
        $campaignClient,
        'updateProfile',
        [
            $testProfile['PKey'],
            [
                'birthDate' => '1977-09-10',
                'preferredLanguage' => 'fr_fr',
                'Acquisition' => 'self granted 5',
                'InstagramUsername' => $testInstagram,
                'AppUser' => 'unknown',
                'LcahMember' => 0,
            ],
        ]
    );


    Utils::execute($campaignClient, 'updateProfile', [$testProfile['PKey'], ['foo' => 'bar']]);

    /**
     * Service tests
     */
    Utils::execute($campaignClient, 'getServices');
    $testService = null;
    foreach ($data[$prefix.'getServices']['success']['content'] as $service) {
        if ($service['name'] === $testServiceName) {
            $testService = $service;
            break;
        }
    }
    if (is_array($testService)) {
        Utils::execute($campaignClient, 'addSubscription', [$testProfile, $testService]);
        Utils::execute($campaignClient, 'addSubscription', [$testProfile, $testService]);
        Utils::execute($campaignClient, 'getSubscriptionsByProfile', [$testProfile]);
        $testSubscription = null;
        foreach ($data[$prefix.'getSubscriptionsByProfile']['success']['content'] as $subscription) {
            if ($subscription['serviceName'] === $testServiceName) {
                $testSubscription = $subscription;
                break;
            }
        }
        Utils::execute($campaignClient, 'deleteSubscription', [$testSubscription]);
    }
    Utils::execute($campaignClient, 'getProfile', [$testProfile['PKey']]);
}
Utils::execute($campaignClient, 'createProfile', [['foo' => 'bar']]);
Utils::execute($campaignClient, 'createProfile', [['email' => 'foo@bar']]);
Utils::execute($campaignClient, 'createProfile', [['email' => 'foo@wwwwwwwwwww.xyz']]);
Utils::execute($campaignClient, 'createProfile', [['email' => $testEmail]]);
Utils::execute($campaignClient, 'createProfile', [['email' => $newProfileTestEmail]]);
Utils::execute($campaignClient, 'getProfileByEmail', [$newProfileTestEmail]);
if (isset($data[$prefix.'getProfileByEmail_alt_alt_alt']['success']['content'][0])) {
    $newProfileTest = $data[$prefix.'getProfileByEmail_alt_alt_alt']['success']['content'][0];
    Utils::execute($campaignClient, 'updateProfile', [$newProfileTest['PKey'], ['email' => $newProfileTestEmail, 'AppUser' => 'ios']]);
}
$newProfileTest = $campaignClient->getProfileByEmail($newProfileTestEmail);
if ($newProfileTest && $testService) {
    Utils::execute($campaignClient, 'addSubscription', [$newProfileTest['content'][0], $testService]);
    Utils::execute($campaignClient, 'getSubscriptionsByProfile', [$newProfileTest['content'][0]]);
    $newProfileSubscription = null;
    foreach ($data[$prefix.'getSubscriptionsByProfile_alt']['success']['content'] as $subscription) {
        if ($subscription['serviceName'] === $testServiceName) {
            $newProfileSubscription = $subscription;
            break;
        }
    }
    Utils::execute($campaignClient, 'deleteSubscription', [$newProfileSubscription]);
    Utils::execute($campaignClient, 'deleteProfile', [$newProfileTest['content'][0]['PKey']]);
}

/**
 * Worflow tests
 */
if ($testWorkflow) {
    Utils::execute($campaignClient, 'getWorkflowActivity', [$testWorkflow]);
    Utils::execute($campaignClient, 'startWorkflow', [$testWorkflow]);
    Utils::execute($campaignClient, 'getWorkflowActivity', [$testWorkflow]);
    Utils::execute($campaignClient, 'pauseWorkflow', [$testWorkflow]);
    Utils::execute($campaignClient, 'getWorkflowActivity', [$testWorkflow]);
    Utils::execute($campaignClient, 'resumeWorkflow', [$testWorkflow]);
    Utils::execute($campaignClient, 'getWorkflowActivity', [$testWorkflow]);
    Utils::execute($campaignClient, 'stopWorkflow', [$testWorkflow]);
    Utils::execute($campaignClient, 'getWorkflowActivity', [$testWorkflow]);
    Utils::execute($campaignClient, 'startWorkflow', [$testWorkflow]);
    Utils::execute($campaignClient, 'getWorkflowActivity', [$testWorkflow]);
}

// @codingStandardsIgnoreEnd

?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Adobe Experience Cloud PHP SDK test run [<?php print $env ?>]</title>
    <style>
        body {
            font-family: sans-serif;
            line-height: 1.5;
            /*background-color: #fafafa;*/
            /*color: #222;*/
            background-color: #3A3636;
            color: #d1d1d1;
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

        .success {
            margin-bottom: 1px;
        }

        .check {
            color: greenyellow;
        }

        .ballot {
            color: gold;
        }

        .error {
            padding: 1rem 1.5rem;
            margin-bottom: 1px;
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
            padding: 0 0 .5rem 0;
        }

        .error div {
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
<header><h1>Adobe Experience Cloud PHP SDK test run <span class="env"><?php print $env ?></span></h1></header>
<?php foreach ($data as $key => $value) : ?>
    <?php if (isset($data[$key]['success'])) : ?>
        <section class="success">
            <h1 class="toggler"><span class="check">✔</span> <?php print $key ?></h1>
            <div class="togglable">
                <pre><code><?php print var_export($data[$key]['success'], true); ?></code></pre>
            </div>
        </section>
    <?php endif; ?>
    <?php if (isset($data[$key]['error'])) : ?>
        <section class="error">
            <strong><?php print $data[$key]['error']->getCode(); ?></strong>
            <div><h1><span class="ballot">✘</span> <?php print $key ?></h1>
                <?php print $data[$key]['error']->getMessage(); ?>
                <?php if (method_exists($data[$key]['error'], 'getData')) : ?>
                    <pre><?php print_r($data[$key]['error']->getData()) ?></pre>
                <?php endif; ?></div>
            <pre><?php print $data[$key]['error']->getTraceAsString(); ?></pre>
        </section>
    <?php endif; ?>
<?php endforeach; ?>
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
                    luminosity: "dark",
                    format: "hsla",
                    hue: "green"
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
