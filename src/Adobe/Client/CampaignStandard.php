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
     * @param int  $start
     * @param null $limit
     */
    public function getProfiles($start = 0, $limit = null)
    {
    }

    /**
     * @param string $email
     */
    public function getProfileByEmail($email)
    {
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
