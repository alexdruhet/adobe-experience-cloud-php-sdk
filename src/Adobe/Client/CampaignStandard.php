<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 09/04/2018
 * Time: 15:46
 */

namespace Pixadelic\Adobe\Client;

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

        return $this->patch("{$url}/${pKey}", $payload);
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
        // TODO
        return $this->get($profile->content[0]->subscriptions->href);
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