<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Custom dump of var for Log purpose
 */
final class VarDump
{
    /**
     * Add a number of space to the string
     * @param int $num
     * @return string
     */
    public function addSpace(int $num): string
    {
        return str_repeat(" ", $num);
    }

    public function null(int $space = 0): string
    {
        return  $this->addSpace($space) . "NULL\n";
    }

    public function bool($value, int $space = 0): string
    {
        $bool = ($value === TRUE) ? "true" : "false";
        return  $this->addSpace($space) . "bool(" . $bool . ")" . "\n";
    }

    public function string($value, int $space = 0): string
    {
        return  $this->addSpace($space) . 'string(' . strlen($value) . ') "' . $value . '"' . "\n";
    }

    public function int($value, int $space = 0): string
    {
        return  $this->addSpace($space) . "int(" . $value . ")\n";
    }

    public function float($value, int $space = 0): string
    {
        return  $this->addSpace($space) . "float(" . $value . ")\n";
    }

    /**
     * Display array key and value, use it recursively for array in array
     * @param $value
     * @param int $space
     * @param string $text
     * @return string
     */
    public function array($value, int $space = 0, string $text = ""): string
    {
        if (empty($value)) {
            return $this->addSpace($space) . "array(0) {}\n";
        }
        $text .= $this->addSpace($space) . "array(" . count($value) . ") {\n";
        $space += 2;
        foreach ($value as $value_key => $value_value) {
            if (is_int($value_key)) {
                $text .= $this->addSpace($space) . "[" . $value_key . "] => ";
            } else {
                $text .= $this->addSpace($space) . "'" . $value_key . "' => ";
            }
            $newSpace = $space;
            if (is_array($value_value) === false) {
                $newSpace = 0;
            }
            $text .= $this->varDump($value_value, $newSpace);
        }
        $text .= $this->addSpace($space - 2) . "}\n";
        return $text;
    }

    /**
     * Display UploadedFile information as array
     * @param UploadedFile $file
     * @return array
     */
    public function parseInfoFromUploadedFile(UploadedFile $file): array
    {
        $filePathName = $file->getPathname();
        $isExist = file_exists($filePathName);
        $fileSize = $isExist === true  ? $file->getSize() : "file_not_found";
        return [
            "name" => $file->getClientOriginalName(),
            "size" => $fileSize,
            "type" => $file->getClientMimeType(),
            "tmp_name" => $filePathName,
            "error" => $file->getError()
        ];
    }

    /**
     * Display all parameter from Request, include file and JWT to blame
     * @param Request $request
     * @return array
     */
    public function getRequestParameters(Request $request): array
    {
        $headers = $request->headers;
        $jwt = "";
        if ($headers->has('Authorization') === true) {
            $jwt = str_replace("Bearer ", '', $headers->get('Authorization'));
        }
        $parameters = $request->request->all();
        $fileRequest = $request->files->all();
        foreach ($fileRequest as $fieldName => $fileArray) {
            foreach ($fileArray as $file) {
                $fileInfo = $this->parseInfoFromUploadedFile($file);
                $parameters[$fieldName][] = $fileInfo;
            }
        }
        $parameters["JWT"] = $jwt;
        return $parameters;
    }

    /**
     * Display basic information for object
     * @param $value
     * @param int $space
     * @return string
     */
    public function object($value, int $space = 0): string
    {
        if ($value instanceof \DateTime) {
            $strDate = $value->format("Y-m-d H:i:s");
            return $this->addSpace($space) . "DateTime(" . $strDate . ")\n";
        }

        if ($value instanceof Request) {
            return  $this->addSpace($space) . "Request : \n". $this->varDump($this->getRequestParameters($value)) . "\n";
        }

        if ($value instanceof UploadedFile) {
            return $this->addSpace($space) . "UploadedFile : \n" . $this->varDump($this->parseInfoFromUploadedFile($value)) . "\n";
        }

        return $this->addSpace($space) . "Object : " . get_class($value) . "\n";
    }

    /**
     * Use other function from value type
     * @param $value
     * @param int $space
     * @param string $text
     * @return string
     */
    public function varDump($value, int $space = 0, string $text = ""): string
    {
        if (is_array($value) === true) {
            $text .= $this->array($value, $space, $text);
        } elseif (is_bool($value) === true) {
            $text .= $this->bool($value, $space);
        } elseif (is_float($value) === true) {
            $text .= $this->float($value, $space);
        } elseif (is_int($value) === true) {
            $text .= $this->int($value, $space);
        } elseif (is_null($value) === true) {
            $text .= $this->null($space);
        } elseif (is_object($value) === true) {
            $text .= "";
        } elseif (is_string($value) === true) {
            $text .= $this->string($value, $space);
        }
        return $text;
    }
}