<?php

namespace App\Controller;

use App\Controller\Interface\CheckParameterInterface;
use DateTime;

class CustomCheckParameter implements CheckParameterInterface
{
    private const PASSWORD_LENGTH = 8;

    /**
     * @param array $parameter
     * @param array $parameterWaited
     * @return array[
     *  "error" => string,
     *  "parameter" => array[mixed]
     *  ]
     */
    public function checkParameter(array $parameter, array $parameterWaited): array
    {
        $error = "";
        $newParameters = [];
        foreach ($parameterWaited as $field => $valueType) {
            $isOptional = FALSE;
            if (str_ends_with($field, "_OPT") === TRUE) {
                $field = substr($field, 0, -4);
                $isOptional = TRUE;
            }
            if (isset($parameter[$field]) === FALSE) {
                if ($isOptional === FALSE) {
                    $error = sprintf("Field '%s' is missing", $field);
                    break;
                }

                if (in_array($valueType, ["bool", "boolean"]) === TRUE) {
                    $newParameters[$field] = FALSE;
                } else {
                    $newParameters[$field] = NULL;
                }
                continue;
            }
            if (empty($parameter[$field]) && $parameter[$field] !== "0" && $parameter[$field] !== 0){
                if (in_array($valueType, ["bool", "boolean"])) {
                    $newParameters[$field] = FALSE;
                    continue;
                }

                if ($isOptional === TRUE) {
                    $newParameters[$field] = NULL;
                    continue;
                }

                $error = sprintf("Field '%s' is empty", $field);
                break;
            }
            [
                "error" => $errorCheckValue,
                "value" => $newValue
            ] = $this->_checkValue($field, $parameter[$field], $valueType, $isOptional);
            if ($errorCheckValue !== "") {
                $error = $errorCheckValue;
                break;
            }

            $newParameters[$field] = $newValue;
        }
        return ["error" => $error, "parameter" => $newParameters];
    }

    /**
     * Check if all values are in the good types
     * @param string $field
     * @param $value
     * @param string $valueType
     * @param bool $isOptional
     * @return array[
     * "error" => string,
     * "value" => mixed,
     * ]
     */
    private function _checkValue(string $field, $value, string $valueType, bool $isOptional): array
    {
        $error = "";
        $arrayType = FALSE;
        $dateFormat = "Y-m-d H:i:s";
        //is from field multiple like creation field
        $multiple = FALSE;
        if (str_starts_with($valueType, "multiple") === TRUE) {
            $multiple = TRUE;
            $valueType = substr($valueType, 9);
        }
        if (str_starts_with($valueType, "explode") === TRUE) {
            $arrayType = substr($valueType, 8);
            $valueType = "explode";
        }
        if (str_starts_with($valueType, "array") === TRUE) {
            $arrayType = substr($valueType, 6);
            $valueType = "array";
        }
        if (str_starts_with($valueType, "date") === TRUE) {
            $dateFormat = substr($valueType, 5);
            $dateFormat = ($dateFormat === FALSE) ? "Y-m-d H:i:s" : $dateFormat;
            $valueType = "date";
        }
        if ($isOptional === FALSE && in_array($value, [NULL, FALSE, ""], TRUE)) {
            $error = sprintf("Field '%s' can't be empty", $field);
        }
        switch ($valueType) {
            case "string":
                if (is_string($value) === FALSE) {
                    $error = sprintf("Field '%s' must be text", $field);
                }
                break;
            case "int":
            case "integer":
            case "number":
                $newValue = (int)$value;
                if ($value !== (string)$newValue) {
                    $error = sprintf("Field '%s' must be integer", $field);
                } else {
                    $value = $newValue;
                }
                break;
            case "float":
                //sometimes we get the value as float, like when we get address from Google api
                if (is_float($value) === TRUE) {
                    $value = (string)$value;
                }
                $newValue = (float)$value;
                $newNewValue = (string)$newValue;
                $strlenValue = strlen(substr(strrchr($value, "."), 1));
                $strlenNewValue = strlen(substr(strrchr($newValue, "."), 1));
                if ($value !== $newNewValue && $strlenValue === $strlenNewValue) {
                    $error = sprintf("Field '%s' Must be a float", $field);
                } elseif (in_array($field, ["latitude", "longitude"], TRUE) === TRUE) {
                    $value = $newValue;
                } else {
                    $value = (float)number_format($newValue, 2, ".", "");
                }
                break;
            case "email":
                if (filter_var($value, FILTER_VALIDATE_EMAIL) === FALSE) {
                    $error = sprintf("Field '%s' must be a valid email", $field);
                }
                break;
            case "password":
                if (strlen($value) < self::PASSWORD_LENGTH) {
                    $error = sprintf("The field '%s' must be %d character long", $field, self::PASSWORD_LENGTH);
                }
                break;
            case "tel":
                $filteredValue = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                $filteredValue = str_replace("-", "", $filteredValue);
                if (strlen($filteredValue) < 10 || strlen($filteredValue) > 14) {
                    $error = sprintf("Field '%s' must be a valid tel number", $field);
                } else {
                    $value = $filteredValue;
                }
                break;
            case "bool":
            case "boolean":
                $value = ($value === "true");
                break;
            case "explode":
            case "array":
                $toExplode = FALSE;
                if ($valueType === "explode") {
                    $toExplode = TRUE;
                }
                $valueArray = $value;
                if ($arrayType !== FALSE && $arrayType !== "") {
                    $valueArray = $this->_checkArray($arrayType, $value, $toExplode);
                }
                if (empty($valueArray)) {
                    if ($isOptional === FALSE) {
                        $error = sprintf("Field '%s' is empty", $field);
                    } else {
                        $value = NULL;
                    }
                } else {
                    $value = $valueArray;
                }
                break;
            case "date":
                $dateTime = $this->_checkDateFormat($value, $dateFormat);
                if ($dateTime === FALSE) {
                    $error = sprintf("Field '%s' is not in the waited date format", $field);
                } else {
                    $value = $dateTime;
                }
                break;
            case "url":
                if (filter_var($value, FILTER_VALIDATE_URL) === FALSE) {
                    $error = sprintf("Field '%s' not a valid link", $field);
                }
                break;
            case "uuid":
                if ($this->_checkUuid($value) === FALSE) {
                    $error = sprintf("Field '%s' must be an identifiant", $field);
                }
                break;
            default:
                break;
        }
        return ["error" => $error, "value" => $value];
    }

    /**
     * @param string $arrayType
     * @param $value
     * @param bool $toExplode
     * @return array
     */
    private function _checkArray(string $arrayType, $value, bool $toExplode = FALSE): array
    {
        $func = static function ($el) {
            return trim($el);
        };
        if (in_array($arrayType, ["int", "integer"])) {
            $func = static function ($el) {
                $newEl = (int)$el;
                if ($el === (string)$newEl) {
                    return $newEl;
                }
                return NULL;
            };
        } elseif($arrayType === "float") {
            $func = static function ($el) {
                $newEl = (float)$el;
                if ($el === (string)$newEl) {
                    return $newEl;
                }
                return NULL;
            };
        } elseif (in_array($arrayType, ["bool", "boolean"])) {
            $func = static function ($el) {
                return ($el === "TRUE");
            };
        }
        $array = $value;
        if ($toExplode) {
            $array = explode(",", $value);
        }
        $valueArray = array_map($func, $array);
        return $this->removeEmptyElementFromArray($valueArray);
    }

    /**
     * @param array $array
     * @return array
     */
    public function removeEmptyElementFromArray(array $array): array
    {
        $newArray = [];
        foreach ($array as $key => $item) {
            if ($item !== NULL && $item !== "") {
                $newArray[$key] = $item;
            }
        }
        return $newArray;
    }

    /**
     * @param string $date
     * @param string $format
     * @return DateTime|FALSE
     */
    private function _checkDateFormat(string $date, string $format = 'Y-m-d H:i:s'): DateTime|FALSE
    {
        return DateTime::createFromFormat($format, $date);
    }

    /**
     * @param string $uuid
     * @return bool
     */
    private function _checkUuid(string $uuid): bool
    {
        return !((preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1));
    }
}