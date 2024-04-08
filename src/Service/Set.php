<?php

namespace App\Service;

use App\Service\Tool\Set\ORM as SetORMService;
use App\Entity\Set as SetEntity;
use Exception;

class Set
{
    private CustomGeneric $customGenericService;

    private SetORMService $setORMService;

    public function __construct(CustomGeneric $customGenericService, SetORMService $setORMService)
    {
        $this->customGenericService = $customGenericService;
        $this->setORMService = $setORMService;
    }

    /**
     * @return SetORMService
     */
    public function getORMService(): SetORMService
    {
        return $this->setORMService;
    }

    /**
     * @param SetEntity $set
     * @return array[
     *  "error" => string,
     *  "errorDebug" => string,
     * "set" => array[mixed]
     * ]
     */
    public function getInfo(SetEntity $set): array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse(), "set" => []];
        try {
            $setSerialize = $this->customGenericService->getInfoSerialize([$set], ["set_info"])[0];
            $cardSets = $set->getCardSets();
            foreach ($cardSets as $key => $cardSet) {
                $cardSerialize = $this->customGenericService->getInfoSerialize([$cardSet->getCard()], ["card_info"])[0];
                $cardSerializePicture = [];
                $cardSerializePictures = $cardSerialize["pictures"];
                if (empty($cardSerializePictures) === FALSE) {
                    $cardSerializePicture = $cardSerializePictures[0];
                }
                unset($cardSerialize["pictures"]);
                $cardSerialize["picture"] = $cardSerializePicture;
                $setSerialize["cardSets"][$key]["card"] = $cardSerialize;
            }
            $response["set"] = $setSerialize;
            $this->customGenericService->addInfoLogFromDebugBacktrace();
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while getting Set info.";
        }
        return $response;
    }
}