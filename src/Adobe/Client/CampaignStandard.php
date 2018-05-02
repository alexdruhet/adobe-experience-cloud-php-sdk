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
        return $this->getMetadata($this->majorEndpoints[0]);
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
        if ($field && \property_exists($metadata->content, $field)) {
            $url .= "/{$field}";
        }

        return $this->get($url, ['_lineCount' => $limit]);
    }

//    public function getProfilesExtended($limit = 10, $field = null)
//    {
//        $this->setExtended();
//        $content = $this->getProfiles($limit, $field);
//        $this->unsetExtended();
//
//        return $content;
//    }

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
        return $this->get("{$this->majorEndpoints[0]}/byEmail", ['email' => $email]);
    }

    /**
     * @param string $businessId
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getProfileExtended($businessId)
    {
        $this->setExtended();
        $content = $this->get("{$this->majorEndpoints[0]}/{$businessId}");
        $this->unsetExtended();

        return $content;
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
            throw new ClientException('To create a profile, an email is mandatory', 400);
        }

        // Then we check if the email si valid
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ClientException(sprintf('The given email %s is invalid', $data['email']), 400);
        }

        // So we can ensure the tld exists
        $tld = substr(strrchr($data['email'], "@"), 1);
        if (!checkdnsrr($tld, 'MX')) {
            throw new ClientException(sprintf('The domain of the given email %s is invalid', $data['email']), 400);
        }

        // Then we lookup if a profile already exists for this email
        $profile = $this->getProfileByEmail($data['email']);
        if (count($profile->content)) {
            throw new ClientException(sprintf('A profile already exists for %s', $data['email']), 409);
        }

        // We now add orgUnit data
        $data['orgUnit'] = $this->orgUnit;

        // If ok we proceed with the extended API
        $this->setExtended();
        $content = $this->setExtended()->post($this->majorEndpoints[0], $data);
        $this->unsetExtended();

        return $content;
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
    public function getServices()
    {
        return $this->get($this->majorEndpoints[1]);
    }


    /**
     * @param \stdClass $profile
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getSubscriptionsByProfile(\stdClass $profile)
    {
        return $this->get($profile->content[0]->subscriptions->href);
    }

    /**
     * @param \stdClass $profile
     * @param \stdClass $service
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function addSubscription(\stdClass $profile, \stdClass $service)
    {
        // First we check if the profile already subscribe to the service
        $subscriptions = $this->getSubscriptionsByProfile($profile);
        foreach ($subscriptions->content as $subscription) {
            if ($subscription->service->name === $service->name) {
                // TODO: find a better return
                return (object) ['code' => 409, 'message' => 'This profile has already subscribe to the service'];
            }
        }

        return $this->post("{$this->majorEndpoints[0]}/{$profile->content[0]->PKey}/subscriptions", ['service' => ['PKey' => $service->PKey]]);
    }

    /**
     * @param \stdClass $subscription
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteSubscription(\stdClass $subscription)
    {
        if (!isset($subscription->href)) {
            throw new ClientException('Invalid subscription submitted', 400);
        }

        return $this->delete($subscription->href);
    }

    /**
     * Endpoints declaration
     */
    protected function setEndpoints()
    {
        $this->endpoints = ['campaign/profileAndServices'];
    }

    /**
     * Major endpoints declaration
     */
    protected function setMajorEndpoints()
    {
        $this->majorEndpoints = ['profile', 'service'];
    }
}
