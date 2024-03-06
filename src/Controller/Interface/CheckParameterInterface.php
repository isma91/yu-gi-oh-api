<?php

namespace App\Controller\Interface;

interface CheckParameterInterface
{
    /**
     * Check if all the asked parameters from the Request is here
     * @param array $parameter
     * @param array $parameterWaited
     * @return array[
     * "error" => string,
     * "parameter" => array[mixed]
     * ]
     */
    public function checkParameter(array $parameter, array $parameterWaited): array;
}