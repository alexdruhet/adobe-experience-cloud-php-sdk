<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 16/04/2018
 * Time: 18:09
 */

namespace Pixadelic\Adobe\Exception;

/**
 * Class ClientException
 *
 */
class ClientException extends \Exception
{
    private $data = '';

    /**
     * ClientException constructor.
     *
     * @param string $message
     * @param int    $code
     * @param string $data
     */
    public function __construct($message, $code, $data = '')
    {
        $this->data = $data;
        parent::__construct($message, $code);
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }
}
