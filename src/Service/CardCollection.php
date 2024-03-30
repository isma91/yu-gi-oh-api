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
     * @param int $id
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     * "user" => null|UserEntity,
     * "collection" => null|CardCollectionEntity,
     * ]
     */
    public function checkUserAndCollection(string $jwt, int $id): array
    {
        $response = [
            ...$this->customGenericService->getEmptyReturnResponse(),
            "user" => NULL,
            "collection" => NULL
        ];
        $user = $this->customGenericService->customGenericCheckJwt($jwt);
        if ($user === NULL) {
            $response["error"] = "No user found.";
            return $response;
        }
        $response["user"] = $user;
        $collection = $this->cardCollectionORMService->findById($id);
        if ($collection === NULL) {
            $response["error"] = "Collection not found.";
            return $response;
        }
        $collectionUser = $collection->getUser();
        if ($collectionUser === NULL) {
            $this->customGenericService->addErrorMessageLog(
                sprintf(
                    "No User found for Collection id => %d", $collection->getId()
                )
            );
            $response["error"] = "Collection not available.";
            return $response;
        }
        $response["collection"] = $collection;
        return $response;
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
     * @param string $jwt
     * @param int $id
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     * "collection" => array[mixed]
     */
    public function getInfo(string $jwt, int $id):array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse(), "collection" => []];
        try {
            [
                "error" => $errorCheckUserCollection,
                "errorDebug" => $errorDebugCheckUserCollection,
                "user" => $user,
                "collection" => $cardCollection,
            ] = $this->checkUserAndCollection($jwt, $id);
            if (empty($errorCheckUserCollection) === FALSE) {
                $response["error"] = $errorCheckUserCollection;
                $response["errorDebug"] = $errorDebugCheckUserCollection;
                return $response;
            }
            $collectionUser = $cardCollection->getUser();
            $isAdmin = $this->customGenericService->checkIfUserIsAdmin($user);
            if (
                $isAdmin === FALSE &&
                $cardCollection->isIsPublic() === FALSE &&
                $collectionUser->getId() !== $user->getId()
            ) {
                $response["error"] = "Collection not available.";
                return $response;
            }
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
     * @param string $jwt
     * @param int $id
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     */
    public function deleteFromId(string $jwt, int $id):array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse()];
        try {
            [
                "error" => $errorCheckUserCollection,
                "errorDebug" => $errorDebugCheckUserCollection,
                "user" => $user,
                "collection" => $cardCollection,
            ] = $this->checkUserAndCollection($jwt, $id);
            if (empty($errorCheckUserCollection) === FALSE) {
                $response["error"] = $errorCheckUserCollection;
                $response["errorDebug"] = $errorDebugCheckUserCollection;
                return $response;
            }
            $collectionUser = $cardCollection->getUser();
            $isAdmin = $this->customGenericService->checkIfUserIsAdmin($user);
            if ($isAdmin === FALSE && $collectionUser->getId() !== $user->getId()) {
                $response["error"] = "Collection not available.";
                return $response;
            }
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
     * @param string $jwt
     * @param int $id
     * @param int $public
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     * "collection" => array[mixed]
     */
    public function updatePublic(string $jwt, int $id, int $public):array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse(), "collection" => []];
        try {
            [
                "error" => $errorCheckUserCollection,
                "errorDebug" => $errorDebugCheckUserCollection,
                "user" => $user,
                "collection" => $cardCollection,
            ] = $this->checkUserAndCollection($jwt, $id);
            if (empty($errorCheckUserCollection) === FALSE) {
                $response["error"] = $errorCheckUserCollection;
                $response["errorDebug"] = $errorDebugCheckUserCollection;
                return $response;
            }
            $collectionUser = $cardCollection->getUser();
            $isAdmin = $this->customGenericService->checkIfUserIsAdmin($user);
            if (
                $isAdmin === FALSE &&
                $cardCollection->isIsPublic() === FALSE &&
                $collectionUser->getId() !== $user->getId()
            ) {
                $response["error"] = "Collection not available.";
                return $response;
            }
            $publicValue = $public === 1;
            $cardCollection->setIsPublic($publicValue);
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
            $response["error"] = "Error while getting Collection.";
        }
        return $response;
    }

    /**
     * @param string $jwt
     * @param int $id
     * @param array $parameter
     * @param Card $cardService
     * @param Country $countryService
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     */
    public function update(
        string $jwt,
        int $id,
        array $parameter,
        CardService $cardService,
        CountryService $countryService
    ):array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse()];
        try {
            [
                "error" => $errorCheckUserCollection,
                "errorDebug" => $errorDebugCheckUserCollection,
                "user" => $user,
                "collection" => $cardCollection,
            ] = $this->checkUserAndCollection($jwt, $id);
            if (empty($errorCheckUserCollection) === FALSE) {
                $response["error"] = $errorCheckUserCollection;
                $response["errorDebug"] = $errorDebugCheckUserCollection;
                return $response;
            }
            $collectionUser = $cardCollection->getUser();
            $isAdmin = $this->customGenericService->checkIfUserIsAdmin($user);
            if (
                $isAdmin === FALSE &&
                $cardCollection->isIsPublic() === FALSE &&
                $collectionUser->getId() !== $user->getId()
            ) {
                $response["error"] = "Collection not available.";
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
            $cardCollection->setName($name)
                ->setSlugName($this->customGenericService->slugify($name))
                ->setIsPublic($isPublic)
                ->setUser($user);
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
            $user->addCardCollection($cardCollection);
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