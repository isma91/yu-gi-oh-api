<?php

namespace App\Service;

use App\Entity\CardCollection as CardCollectionEntity;
use App\Service\Card as CardService;
use App\Service\Country as CountryService;
use App\Service\Tool\CardCollection\ORM as CardCollectionORMService;
use App\Service\Tool\CardCollection\Entity as CardCollectionEntityService;
use Exception;

class CardCollection
{
    private CustomGeneric $customGenericService;

    private CardCollectionORMService $cardCollectionORMService;
    private CardCollectionEntityService $cardCollectionEntityService;

    public function __construct(
        CustomGeneric $customGenericService,
        CardCollectionORMService $cardCollectionORMService,
        CardCollectionEntityService $cardCollectionEntityService
    )
    {
        $this->customGenericService = $customGenericService;
        $this->cardCollectionORMService = $cardCollectionORMService;
        $this->cardCollectionEntityService = $cardCollectionEntityService;
    }

    /**
     * @return CardCollectionORMService
     */
    public function getORMService(): CardCollectionORMService
    {
        return $this->cardCollectionORMService;
    }

    /**
     * @param string $jwt
     * @param array $parameter
     * @param Card $cardService
     * @param Country $countryService
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     */
    public function create(
        string $jwt,
        array $parameter,
        CardService $cardService,
        CountryService $countryService
    ):array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse()];
        try {
            $user = $this->customGenericService->customGenericCheckJwt($jwt);
            if ($user === NULL) {
                $response["error"] = "No user found.";
                return $response;
            }
            [
                "name" => $name,
                "isPublic" => $isPublic,
                "artwork" => $artwork,
                "card-collection" => $cardCollectionArray
            ] = $parameter;
            $cardORMService = $cardService->getORMService();
            $countryORMService = $countryService->getORMService();
            $cardCollection = new CardCollectionEntity();
            $cardCollection->setName($name)
                ->setSlugName($this->customGenericService->slugify($name))
                ->setIsPublic($isPublic)
                ->setUser($user);
            $cardCollectionArray["artwork"] = $artwork;
            $cardCollection = $this->cardCollectionEntityService->createCardCardCollection(
                $cardCollection,
                $cardCollectionArray,
                $cardORMService,
                $countryORMService,
                $this->cardCollectionORMService
            );
            $user->addCardCollection($cardCollection);
            $this->cardCollectionORMService->persist($cardCollection);
            $this->cardCollectionORMService->flush();
            $this->customGenericService->addInfoLogFromDebugBacktrace();
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while creating Collection.";
        }
        return $response;
    }

    /**
     * @param CardCollectionEntity $cardCollection
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     * "collection" => array[mixed]
     */
    public function getInfo(CardCollectionEntity $cardCollection):array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse(), "collection" => []];
        try {
            $response["collection"] = $this->customGenericService
                ->getInfoSerialize(
                    [$cardCollection],
                    ["collection_info", "country_list", "search_card"]
                )[0];
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while getting Collection.";
        }
        return $response;
    }

    /**
     * @param CardCollectionEntity $cardCollection
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     */
    public function deleteFromId(CardCollectionEntity $cardCollection):array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse()];
        try {
            $cardCollection = $this->cardCollectionEntityService
                ->removeCardCardCollection(
                    $cardCollection,
                    $this->cardCollectionORMService
                );
            $this->cardCollectionORMService->remove($cardCollection);
            $this->cardCollectionORMService->flush();
            $this->customGenericService->addInfoLogFromDebugBacktrace();
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while deleting Collection.";
        }
        return $response;
    }

    /**
     * @param CardCollectionEntity $cardCollection
     * @param int $public
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     * "collection" => array[mixed]
     */
    public function updatePublic(CardCollectionEntity $cardCollection, int $public):array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse(), "collection" => []];
        try {
            $cardCollection->setIsPublic($public === 1);
            $this->cardCollectionORMService->persist($cardCollection);
            $this->cardCollectionORMService->flush();
            $response["collection"] = $this->customGenericService
                ->getInfoSerialize(
                    [$cardCollection],
                    ["collection_info", "country_list", "search_card"]
                )[0];
            $this->customGenericService->addInfoLogFromDebugBacktrace();
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while updating and getting Collection.";
        }
        return $response;
    }

    /**
     * @param CardCollectionEntity $cardCollection
     * @param array $parameter
     * @param Card $cardService
     * @param Country $countryService
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     */
    public function update(
        CardCollectionEntity $cardCollection,
        array $parameter,
        CardService $cardService,
        CountryService $countryService
    ):array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse()];
        try {
            [
                "name" => $name,
                "isPublic" => $isPublic,
                "artwork" => $artwork,
                "card-collection" => $cardCollectionArray
            ] = $parameter;
            $cardORMService = $cardService->getORMService();
            $countryORMService = $countryService->getORMService();
            $cardCollection->setName($name)
                ->setSlugName($this->customGenericService->slugify($name))
                ->setIsPublic($isPublic);
            $cardCollectionArray["artwork"] = $artwork;
            $cardCollection = $this->cardCollectionEntityService->removeCardCardCollection(
                $cardCollection,
                $this->cardCollectionORMService
            );
            $cardCollection = $this->cardCollectionEntityService->createCardCardCollection(
                $cardCollection,
                $cardCollectionArray,
                $cardORMService,
                $countryORMService,
                $this->cardCollectionORMService
            );
            $this->cardCollectionORMService->persist($cardCollection);
            $this->cardCollectionORMService->flush();
            $this->customGenericService->addInfoLogFromDebugBacktrace();
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while updating Collection.";
        }
        return $response;
    }
}