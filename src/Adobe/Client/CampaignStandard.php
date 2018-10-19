<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 09/04/2018
 * Time: 15:46
 */

namespace Pixadelic\Adobe\Client;

use Pixadelic\Adobe\Exception\ClientException;

/**
 * Class CampaignStandard
 */
class CampaignStandard extends AbstractBase
{

    /**
     * Store fully populated metadata
     * with nested links metadatas
     *
     * @var array $profileMetadata
     */
    protected $profileMetadata = [];

    /**
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getProfileMetadata()
    {
        if (!count($this->profileMetadata)) {
            $this->currentEndpointIndex = 0;
            $customResources = [];

            if (count($this->orgUnitResources)) {
                foreach ($this->orgUnitResources as $orgUnitResource) {
                    $customResources[$orgUnitResource] = $this->setExtended()->getMetadata($orgUnitResource);
                }
            }

            $profileMetadata = $this->setExtended()->getMetadata($this->majorEndpoints[0]);

            // We restrict the profile metadata
            // to the specified custom resources
            foreach ($profileMetadata['content'] as $key => $value) {
                if (preg_match('/^cus/', $key) && isset($value['resTarget'])) {
                    if (!isset($customResources[$value['resTarget']])) {
                        unset($profileMetadata['content'][$key]);
                    } else {
                        $profileMetadata['content'][$key] = $customResources[$value['resTarget']];
                    }
                }
            }
            $this->profileMetadata = $profileMetadata;
        }

        return $this->profileMetadata;
    }

    /**
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getProfileResources()
    {
        $this->currentEndpointIndex = 0;

        return $this->setExtended()->getResources($this->majorEndpoints[0]);
    }

    /**
     * @param int  $limit
     * @param null $field
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getProfiles($limit = 10, $field = null)
    {
        $metadata = $this->getProfileMetadata();
        $url = $this->majorEndpoints[0];
        if ($field && isset($metadata['content'][$field])) {
            $url .= "/{$field}";
        }

        $this->currentEndpointIndex = 0;

        return $this->setExtended()->get($url, ['_lineCount' => $limit, $this->orgUnitParam => $this->orgUnit]);
    }

    /**
     * @param string $pKey
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getProfile($pKey)
    {
        $this->currentEndpointIndex = 0;

        $profile = $this->setExtended()->get("{$this->majorEndpoints[0]}/{$pKey}", [$this->orgUnitParam => $this->orgUnit]);

        if ($profile && isset($profile['content'][0])) {
            // Load custom resources data
            foreach ($profile['content'][0] as $key => $value) {
                if (preg_match('/^cus/', $key)) {
                    if (isset($profile['content'][0][$key]['href'])) {
                        $profile['content'][0][$key] = $this->get($profile['content'][0][$key]['href']);
                    }
                }
            }
        }

        return $profile;
    }

    /**
     * @param string $email
     * @param bool   $throwException
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getProfileByEmail($email, $throwException = true)
    {
        $this->validateEmail($email);

        $this->currentEndpointIndex = 0;

        //$profile = $this->setExtended()->get("{$this->majorEndpoints[0]}/byEmail", ['email' => $email, $this->orgUnitParam => $this->orgUnit]);
        // Since the org unit is buggy and reconciliated afterwards,
        // we need to check the profile existence without it.
        $profile = $this->setExtended()->get("{$this->majorEndpoints[0]}/byEmail", ['email' => $email]);

        if ($profile && isset($profile['content'][0])) {
            // Load custom resources data
            foreach ($profile['content'][0] as $key => $value) {
                if (preg_match('/^cus/', $key)) {
                    if (isset($profile['content'][0][$key]['href'])) {
                        $profile['content'][0][$key] = $this->get($profile['content'][0][$key]['href']);
                    }
                }
            }
        } elseif ($throwException) {
            throw new ClientException(\sprintf('Profile not found for %s', $email), 404);
        }

        return $profile;
    }

    /**
     * @param array $payload
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function createProfile(array $payload)
    {
        // First we check if an email is given
        if (!isset($payload['email'])) {
            throw new ClientException('To create a profile, giving an email is mandatory', 400);
        }

        $this->validateEmail($payload['email']);

        // Then we lookup if a profile already exists for this email
        $profile = $this->getProfileByEmail($payload['email'], false);
        if (count($profile['content'])) {
            throw new ClientException(sprintf('A profile already exists for %s', $payload['email']), 409);
        }

        // We now add orgUnit data
        $payload[$this->orgUnitParam] = $this->orgUnit;

        // If ok we proceed with the extended API
        $this->currentEndpointIndex = 0;

        $response = $this->setExtended()->post($this->majorEndpoints[0], $payload, $this->getProfileMetadata());
        if ($response && $this->reconciliationWorkflowID) {
            $workflowActivity = $this->getWorkflowActivity($this->reconciliationWorkflowID);
            $state = $workflowActivity['state'];
            if ('stopped' === $state) {
                $this->startWorkflow($this->reconciliationWorkflowID);
            }
            //else {
            // @TODO: add task to an hypothetical queue in order to be batch processed
            //}
        }

        return $response;
    }

    /**
     * @param string $pKey
     * @param array  $payload
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function updateProfile($pKey, array $payload)
    {
        // Check the PKey
        if (!$pKey) {
            throw new ClientException('To update a profile, giving its primary key is mandatory', 400);
        }

        if (!isset($payload['email'])) {
            $profile = $this->getProfile($pKey);
            $payload['email'] = $profile['email'];
        }

        $this->currentEndpointIndex = 0;
        $url = $this->majorEndpoints[0];

        return $this->patch("{$url}/{$pKey}", $payload, $this->getProfileMetadata());
    }

    /**
     * @param string $pKey
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteProfile($pKey)
    {
        $this->currentEndpointIndex = 0;
        $url = $this->majorEndpoints[0];

        return $this->delete("{$url}/{$pKey}");
    }

    /**
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getServiceMetadata()
    {
        $this->currentEndpointIndex = 0;

        return $this->setExtended()->getMetadata($this->majorEndpoints[1]);
    }

    /**
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getServiceResources()
    {
        $this->currentEndpointIndex = 0;

        return $this->setExtended()->getResources($this->majorEndpoints[1]);
    }

    /**
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getServices()
    {
        $this->currentEndpointIndex = 0;

        return $this->get($this->majorEndpoints[1], [$this->orgUnitParam => $this->orgUnit]);
    }


    /**
     * @param array $profile
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getSubscriptionsByProfile(array $profile)
    {
        $this->currentEndpointIndex = 0;

        return $this->get($profile['subscriptions']['href']);
    }

    /**
     * @param array $profile
     * @param array $service
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function addSubscription(array $profile, array $service)
    {
        // First we check if the profile already subscribe to the service
        $subscriptions = $this->getSubscriptionsByProfile($profile);
        foreach ($subscriptions['content'] as $subscription) {
            if ($subscription['service']['name'] === $service['name']) {
                throw new ClientException('The profile has already subscribed to the service', 409);
            }
        }

        $this->currentEndpointIndex = 0;

        return $this->post("{$this->majorEndpoints[0]}/{$profile['PKey']}/subscriptions", ['service' => ['PKey' => $service['PKey']]]);
    }

    /**
     * @param array $subscription
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteSubscription(array $subscription)
    {
        if (!isset($subscription['href'])) {
            throw new ClientException('Invalid subscription submitted', 400);
        }

        $this->currentEndpointIndex = 0;

        return $this->delete($subscription['href']);
    }

    /**
     * @param string $eventId
     * @param array  $payload
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function sendEvent($eventId, array $payload)
    {
        $this->validateEventResources($eventId, $payload);
        $this->currentEndpointIndex = 2;

        return $this->unsetExtended()->post($eventId, $payload);
    }

    /**
     * @param string $eventId
     * @param string $eventPKey
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getEvent($eventId, $eventPKey)
    {
        $this->currentEndpointIndex = 2;

        return $this->unsetExtended()->get("{$eventId}/{$eventPKey}");
    }

    /**
     * @param string $eventId
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getEventMetadata($eventId)
    {
        $this->currentEndpointIndex = 2;

        return $this->unsetExtended()->getMetadata($eventId);
    }

    /**
     * @param string $eventId
     * @param array  $payload
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function validateEventResources($eventId, array $payload)
    {
        $metadata = $this->unsetExtended()->getEventMetadata($eventId);
        if (!isset($metadata['content']['ctx'])) {
            throw new ClientException(sprintf('Invalid $eventId submitted: %s', $eventId), 400);
        }
        if (isset($payload['email'])) {
            $this->validateEmail($payload['email']);
        }
        $this->validateResources($payload, $metadata);
        $this->validateResources($payload['ctx'], $metadata['content']['ctx']);
    }

    /**
     * @param string $id
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function startWorkflow($id)
    {
        return $this->executeWorkflowCommand($id, 'start');
    }

    /**
     * @param string $id
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function pauseWorkflow($id)
    {
        return $this->executeWorkflowCommand($id, 'pause');
    }

    /**
     * @param string $id
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function resumeWorkflow($id)
    {
        return $this->executeWorkflowCommand($id, 'resume');
    }

    /**
     * @param string $id
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function stopWorkflow($id)
    {
        return $this->executeWorkflowCommand($id, 'stop');
    }

    /**
     * @param string $id
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getWorkflowActivity($id)
    {
        $this->currentEndpointIndex = 3;

        return $this->unsetExtended()->get($id);
    }

//    /**
//     * @param $payload
//     *
//     * @return mixed
//     * @throws \GuzzleHttp\Exception\GuzzleException
//     * @throws \Pixadelic\Adobe\Exception\ClientException
//     * @throws \Psr\SimpleCache\InvalidArgumentException
//     */
//    public function sendGDPRrequest($payload)
//    {
//        $this->currentEndpointIndex = 1;
//        $content = $this->post('', $payload);
//        $this->currentEndpointIndex = 0;
//
//        return $content;
//    }
//
//    public function getGDPRrequest()
//    {
//        $this->currentEndpointIndex = 1;
//        $this->currentEndpointIndex = 0;
//    }
//
//    public function getGDPRData()
//    {
//        $this->currentEndpointIndex = 1;
//        $this->currentEndpointIndex = 0;
//    }

    /**
     * @param string $id
     * @param string $command
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function executeWorkflowCommand($id, $command)
    {
        $commands = ['start', 'pause', 'resume', 'stop'];
        if (!\in_array(\strtolower($command), $commands)) {
            throw new ClientException(sprintf('Invalid command submitted: %s', $command), 400);
        }

        $this->currentEndpointIndex = 3;

        return $this->unsetExtended()->post("{$id}/commands", ['method' => $command]);
    }

    /**
     * Endpoints declaration
     */
    protected function setEndpoints()
    {
        $this->endpoints = ['campaign/profileAndServices', 'campaign/privacy/privacyTool', "campaign/mc{$this->tenantBase}", 'campaign/workflow/execution'];
    }

    /**
     * Major endpoints declaration
     */
    protected function setMajorEndpoints()
    {
        $this->majorEndpoints = ['profile', 'service'];
    }
}
