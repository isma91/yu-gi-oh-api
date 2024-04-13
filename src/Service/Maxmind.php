<?php

namespace App\Service;

use Exception;
use GeoIp2\Database\Reader as GeoIp2DBReader;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Record\City as GeoIp2CityRecord;
use GeoIp2\Record\Country as GeoIp2CountryRecord;
use GeoIp2\Record\Continent as GeoIp2ContinentRecord;
use GeoIp2\Record\Subdivision as GeoIp2SubdivisionRecord;
use GuzzleHttp\Client as GuzzleClient;
use MaxMind\Db\Reader\InvalidDatabaseException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Service\Logger as LoggerService;
use UnexpectedValueException;

class Maxmind
{
    private ParameterBagInterface $param;
    private LoggerService $loggerService;
    private GuzzleClient $guzzleClient;
    private const MAXMIND_ASN_TYPE = "ASN";
    private const MAXMIND_CITY_TYPE = "CITY";
    private const MAXMIND_COUNTRY_TYPE = "COUNTRY";

    public function __construct(
        ParameterBagInterface $param,
        LoggerService $loggerService
    )
    {
        $this->param = $param;
        $this->loggerService = $loggerService;
        $this->loggerService->setIsCron(FALSE)->setLevel(LoggerService::ERROR);
        $this->guzzleClient = new GuzzleClient([
            "allow_redirect" => TRUE,
            "http_errors" => TRUE,
        ]);
    }

    /**
     * Do a reverse geocoding from a free API
     * @param float $lat
     * @param float $long
     * @return string|null
     */
    public function getReverseGeocoding(float $lat, float $long): ?string
    {
        $result = NULL;
        $geocodeApiKey = $this->param->get("GEOCODE_MAPS_CO_API_KEY");
        if (empty($geocodeApiKey) === TRUE) {
            return NULL;
        }
        $url = sprintf(
            "https://geocode.maps.co/reverse?lat=%s&lon=%s&api_key=%s",
            $lat,
            $long,
            $geocodeApiKey
        );
        try {
            /**
             * 1 Request/Second for the free API
             * @see https://geocode.maps.co/plans/
             */
            sleep(1);
            $request = $this->guzzleClient->get($url);
            $httpCode = $request->getStatusCode();
            if ($httpCode === 200) {
                $requestContent = json_decode($request->getBody()->getContents(), TRUE, 512, JSON_THROW_ON_ERROR);
                if (empty($requestContent["display_name"]) === FALSE) {
                    $result = $requestContent["display_name"];
                } elseif (empty($requestContent["address"]) === FALSE) {
                    $result = "";
                    $addressKey = array_keys($requestContent["address"]);
                    foreach ($addressKey as $key) {
                        if (str_contains($key, "ISO") === FALSE) {
                            $result .= $requestContent["address"][$key] . ", ";
                        }
                    }
                } else {
                    return NULL;
                }
            }
        } catch (\GuzzleHttp\Exception\GuzzleException|Exception $e) {
            $this->loggerService->setLevel(LoggerService::WARNING)
                ->setException($e);
        }
        return $result;
    }

    /**
     * @param string $type
     * @return GeoIp2DBReader
     * @throws InvalidDatabaseException
     * @throws UnexpectedValueException
     */
    private function _getReader(string $type): GeoIp2DBReader
    {
        $type = strtoupper($type);
        $acceptedType = ["ASN", "CITY", "COUNTRY"];
        if (in_array($type, $acceptedType, TRUE) === FALSE) {
            throw new UnexpectedValueException(
                sprintf(
                    "type '%s' not valid, accepted one => '%s'",
                    $type,
                    implode(", ", $acceptedType)
                )
            );
        }
        $maxmindDir = $this->param->get("MAXMIND_DIR");
        $maxmindFilename = $this->param->get(sprintf("MAXMIND_%s_FILENAME", $type));
        return new GeoIp2DBReader($maxmindDir . "/" . $maxmindFilename);
    }

    /**
     * @param GeoIp2CityRecord|GeoIp2ContinentRecord|GeoIp2CountryRecord|GeoIp2SubdivisionRecord $record
     * @return array
     */
    private function _cleanClassicRecord(
        GeoIp2CountryRecord|
        GeoIp2CityRecord|
        GeoIp2SubdivisionRecord|
        GeoIp2ContinentRecord $record
    ): array
    {
        $array = $record->jsonSerialize();
        unset($array["names"]);
        $array["name"] = $record->name;
        return $array;
    }

    /**
     * @param string $ip
     * @return array
     */
    public function findAsn(string $ip): array
    {
        $result = [];
        if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
            return $result;
        }
        $type = $this::MAXMIND_ASN_TYPE;
        try {
            $reader = $this->_getReader($type);
            $asn = $reader->asn($ip);
            $result = $asn->jsonSerialize();
            $result["network"] = $asn->network;
        } catch(AddressNotFoundException $e) {
            $this->loggerService->setLevel(LoggerService::INFO)
                ->addLog(
                    sprintf(
                        "Ip '%s' not found for type '%s'", $ip, $type
                    )
                );
        }
        catch (InvalidDatabaseException|UnexpectedValueException|Exception $e) {
            $this->loggerService->setLevel(LoggerService::ERROR)->setException($e)
                ->addErrorExceptionOrTrace();
        }
        return $result;
    }

    /**
     * @param string $ip
     * @return array
     */
    public function findCountry(string $ip): array
    {
        $result = [];
        if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
            return $result;
        }
        $type = $this::MAXMIND_COUNTRY_TYPE;
        try {
            $reader = $this->_getReader($type);
            $country = $reader->country($ip);
            $continentSerialize = $this->_cleanClassicRecord($country->continent);
            $countrySerialize = $this->_cleanClassicRecord($country->country);
            $result = ["continent" => $continentSerialize, "country" => $countrySerialize];
        } catch(AddressNotFoundException $e) {
            $this->loggerService->setLevel(LoggerService::INFO)
                ->addLog(
                    sprintf(
                        "Ip '%s' not found for type '%s'", $ip, $type
                    )
                );
        }
        catch (InvalidDatabaseException|UnexpectedValueException|Exception $e) {
            $this->loggerService->setLevel(LoggerService::ERROR)->setException($e)
                ->addErrorExceptionOrTrace();
        }
        return $result;
    }

    /**
     * @param string $ip
     * @return array
     */
    public function findCity(string $ip): array
    {
        $result = [];
        if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
            return $result;
        }
        $type = $this::MAXMIND_CITY_TYPE;
        try {
            $reader = $this->_getReader($type);
            $city = $reader->city($ip);
            $continentSerialize = $this->_cleanClassicRecord($city->continent);
            $countrySerialize = $this->_cleanClassicRecord($city->country);
            $subdivisionSerialize = array_map([$this, "_cleanClassicRecord"], $city->subdivisions);
            $citySerialize = $this->_cleanClassicRecord($city->city);
            $postal = $city->postal->code;
            $locationSerialize = $city->location->jsonSerialize();
            $lat = $city->location->latitude;
            $long = $city->location->longitude;
            $address = NULL;
            if ($lat !== NULL && $long !== NULL) {
                $address = $this->getReverseGeocoding($lat, $long);
            }
            $result = [
                "continent" => $continentSerialize,
                "country" => $countrySerialize,
                "subdivisions" => $subdivisionSerialize,
                "city" => $citySerialize,
                "postal" => $postal,
                "location" => $locationSerialize,
                "address" => $address ?? "",
            ];
        } catch(AddressNotFoundException $e) {
            $this->loggerService->setLevel(LoggerService::INFO)
                ->addLog(
                    sprintf(
                        "Ip '%s' not found for type '%s'", $ip, $type
                    )
                );
        }
        catch (InvalidDatabaseException|UnexpectedValueException|Exception $e) {
            $this->loggerService->setLevel(LoggerService::ERROR)->setException($e)
                ->addErrorExceptionOrTrace();
        }
        return $result;
    }

    /**
     * ALias to do all find and return an array with all value
     * @param string $ip
     * @return array
     */
    public function findAll(string $ip): array
    {
        return [
            $this::MAXMIND_ASN_TYPE => $this->findAsn($ip),
            $this::MAXMIND_COUNTRY_TYPE => $this->findCountry($ip),
            $this::MAXMIND_CITY_TYPE => $this->findCity($ip),
        ];
    }

}