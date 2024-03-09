<?php

namespace App\Service\Tool;

interface FileInterface
{
    /**
     * Absolute path of the current entity upload folder default file
     * @return string
     */
    public function getDefaultFilePath(): string;

    /**
     * Find asked file from prefix and option
     * @param string $prefix string to match and find the PARAM_NAME and, the folder path
     * @param array $option array with multiple key who depend on from entity to entity who have at least uuid & name
     * @return string
     */
    public function getFilePath(string $prefix, array $option): string;
}