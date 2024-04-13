<?php

namespace App\Service\Tool\UserTracking;

use App\Service\Maxmind as MaxmindService;
use App\Entity\UserTracking as UserTrackingEntity;
use JsonException;
use Symfony\Component\Uid\Uuid;

class Entity
{
    private MaxmindService $maxmindService;

    public function __construct(MaxmindService $maxmindService)
    {
        $this->maxmindService = $maxmindService;
    }

    /**
     * Create an array with some user info from server and with the help of Maxmind
     * @return string[]
     */
    private function _createUserTrackingInfo(): array
    {
        $ip1 = $_SERVER['REMOTE_ADDR'] ?? "";
        $ip2 = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? "";
        $ip3 = $_SERVER['HTTP_REMOTE_IP'] ?? "";
        if (($ip1 === $ip2) && ($ip2 === $ip3)) {
            $ip = $ip1;
        } else {
            $ip = sprintf("%s-%s-%s", $ip1, $ip2, $ip3);
        }
        $infoKey = [
            'REMOTE_ADDR',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_REMOTE_IP',
            'HTTP_USER_AGENT',
            'HTTP_ACCEPT_LANGUAGE',
            'HTTP_ACCEPT_ENCODING',
            'HTTP_REFERER',
            'HTTP_X_FORWARDED_PROTO',
        ];
        $info = [
            "ip" => $ip
        ];
        foreach ($infoKey as $key) {
            $value = "";
            if (empty($_SERVER[$key]) === FALSE)  {
                $value = $_SERVER[$key];
            }
            $info[$key] = $value;
        }
        foreach (["REMOTE_ADDR", "HTTP_X_FORWARDED_FOR", "HTTP_REMOTE_IP"] as $item) {
            $newItem = $item . "_GEOIP";
            $maxmindResultArray = $this->maxmindService->findAll($info[$item]);
            foreach ($maxmindResultArray as $key => $value) {
                $info[$newItem][$key] = $value;
            }
        }
        return $info;
    }

    /**
     * Create fingerprint to see if a user is the same
     * @param array $userTrackingInfo
     * @return string
     * @throws JsonException
     */
    private function _createFingerprint(array $userTrackingInfo): string
    {
        $infoFingerprint = [
            "ip" => $userTrackingInfo["ip"],
            "HTTP_USER_AGENT" => $userTrackingInfo["HTTP_USER_AGENT"],
            "HTTP_ACCEPT_LANGUAGE" => $userTrackingInfo['HTTP_ACCEPT_LANGUAGE'],
            "HTTP_ACCEPT_ENCODING" => $userTrackingInfo['HTTP_ACCEPT_ENCODING'],
        ];
        return hash('sha256', json_encode($infoFingerprint, JSON_THROW_ON_ERROR));
    }

    /**
     * @return UserTrackingEntity
     * @throws JsonException
     */
    public function createEntity(): UserTrackingEntity
    {
        $currentDate = new \DateTime();
        $userTracking = new UserTrackingEntity();
        $userTrackingInfo = $this->_createUserTrackingInfo();
        $route = NULL;
        if (empty($_SERVER["REQUEST_URI"]) === FALSE) {
            $route = $_SERVER["REQUEST_URI"];
        }
        return $userTracking->setUuid(Uuid::v7())
            ->setInfo($userTrackingInfo)
            ->setRoute($route)
            ->setFingerprint($this->_createFingerprint($userTrackingInfo))
            ->setCreatedAt($currentDate)
            ->setUpdatedAt($currentDate);
    }
}