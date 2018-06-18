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
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getProfileMetadata()
    {
        $this->currentEndpointIndex = 0;

        return $this->setExtended()->getMetadata($this->majorEndpoints[0]);
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

        return $this->setExtended()->get("{$this->majorEndpoints[0]}/{$pKey}", [$this->orgUnitParam => $this->orgUnit]);
    }

    /**
     * @param string $email
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getProfileByEmail($email)
    {
        $this->currentEndpointIndex = 0;

        return $this->setExtended()->get("{$this->majorEndpoints[0]}/byEmail", ['email' => $email, $this->orgUnitParam => $this->orgUnit]);
    }

    /**
     * @param array $data
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function createProfile(array $data)
    {
        // First we check if an email is given
        if (!isset($data['email'])) {
            throw new ClientException('To create a profile, giving an email is mandatory', 400);
        }

        $this->validateEmail($data['email']);

        // Then we lookup if a profile already exists for this email
        $profile = $this->getProfileByEmail($data['email']);
        if (count($profile['content'])) {
            throw new ClientException(sprintf('A profile already exists for %s', $data['email']), 409);
        }

        // We now add orgUnit data
        $data[$this->orgUnitParam] = $this->orgUnit;

        // We validate our payload
        $this->validateResources($data);

        // If ok we proceed with the extended API
        $this->currentEndpointIndex = 0;

        return $this->setExtended()->post($this->majorEndpoints[0], $data);
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
        $this->currentEndpointIndex = 0;
        $url = $this->majorEndpoints[0];

        return $this->patch("{$url}/{$pKey}", $payload);
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

        return $this->post($eventId, $payload);
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

        return $this->get("{$eventId}/{$eventPKey}");
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

        return $this->getMetadata($eventId);
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
        $metadata = $this->getEventMetadata($eventId);
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
        return $this->get("workflow/execution/{$id}");
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

        $this->currentEndpointIndex = 0;

        return $this->post("workflow/execution/{$id}/commands", ['method' => $command]);
    }

    /**
     * Endpoints declaration
     */
    protected function setEndpoints()
    {
        $this->endpoints = ['campaign/profileAndServices', 'campaign/privacy/privacyTool', "campaign/mc{$this->tenantBase}"];
    }

    /**
     * Major endpoints declaration
     */
    protected function setMajorEndpoints()
    {
        $this->majorEndpoints = ['profile', 'service'];
    }
}
