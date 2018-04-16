<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 09/04/2018
 * Time: 15:46
 */

namespace Pixadelic\Adobe\Client;


class Campaign extends AbstractClient
{
    protected function setNamespace()
    {
        $this->namespace = 'campaign/profileAndServices';
    }
}