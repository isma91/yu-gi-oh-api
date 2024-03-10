<?php

namespace App\Service;

use App\Entity\User as UserEntity;
use App\Service\Tool\Abstract\AbstractORM;
use App\Service\Tool\User\Auth as UserAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\FilterCollection;
use Exception;
use JsonException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class CustomGeneric
{
    private ParameterBagInterface $param;
    private EntityManagerInterface $em;
    private FilterCollection $emFilters;
    private SerializerInterface $serializer;
    private UserAuthService $userAuthService;
    private SluggerInterface $slugger;

    private const SOFTDELETEABLE = "softdeleteable";

    /**
     * @param EntityManagerInterface $em
     * @param ParameterBagInterface $param
     * @param SerializerInterface $serializer
     * @param SluggerInterface $slugger
     * @param UserAuthService $userAuthService
     */
    public function __construct(
        EntityManagerInterface $em,
        ParameterBagInterface $param,
        SerializerInterface $serializer,
        SluggerInterface $slugger,
        UserAuthService $userAuthService
    )
    {
        $this->em = $em;
        $this->param = $param;
        $this->serializer = $serializer;
        $this->emFilters = $em->getFilters();
        $this->userAuthService = $userAuthService;
        $this->slugger = $slugger;
    }

    /**
     * @return void
     */
    public function enableSoftDeleteable(): void
    {
        $this->emFilters->enable($this::SOFTDELETEABLE);
    }

    /**
     * @return void
     */
    public function disableSoftDeleteable(): void
    {
        $this->emFilters->disable($this::SOFTDELETEABLE);
    }

    /**
     * @return array[
     * "error" => string,
     * "errorDebug" => string
     * ]
     */
    public function getEmptyReturnResponse(): array
    {
        return ["error" => "", "errorDebug" => ""];
    }

    /**
     * @param string $jwt
     * @return UserEntity|null
     */
    public function checkJwt(string $jwt): ?UserEntity
    {
        //@todo: add new parameters after we have a minimum good backend
        return $this->userAuthService->checkJWT($jwt);
    }

    /**
     * @param object[] $entities
     * @param string[] $groups
     * @return array[array]
     * @throws JsonException
     */
    public function getInfoSerialize(array $entities, array $groups): array
    {
        $array = [];
        foreach ($entities as $entity) {
            $data = $this->serializer->serialize($entity, "json", ["groups" => $groups]);
            $array[] = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        }
        return $array;
    }

    /**
     * @param string $name
     * @return string
     */
    public function slugify(string $name): string
    {
        return $this->slugger->slug($name)->lower()->toString();
    }

    /**
     * @param AbstractORM $ORMService
     * @param string $returnFieldName
     * @param array $groupNameArray
     * @param string $entityHumanName
     * @param int|string|null $idOrUuid
     * @param bool $isUuid
     * @return array
     */
    public function getAllOrInfo(
        AbstractORM $ORMService,
        string $returnFieldName,
        array $groupNameArray,
        string $entityHumanName,
        int|string|null $idOrUuid = NULL,
        bool $isUuid = FALSE,
    ): array
    {
        $response = [...$this->getEmptyReturnResponse(), $returnFieldName => []];
        $isGetAll = ($idOrUuid === NULL);
        try {
            $findUniqueEntityFunName = ($isUuid === TRUE) ? "findByUuid" : "findById";
            $entity = ($isGetAll === TRUE) ? $ORMService->findAll() : $ORMService->$findUniqueEntityFunName($idOrUuid);
            $infoSerialize = [];
            if ($isGetAll === TRUE) {
                $infoSerialize = $this->getInfoSerialize($entity, $groupNameArray);
            } elseif ($entity !== NULL) {
                $infoSerialize = $this->getInfoSerialize([$entity], $groupNameArray)[0];
            }
            $response[$returnFieldName] = $infoSerialize;
        } catch (JsonException|Exception $e) {
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = sprintf(
                "Error while getting %s%s%s.",
                $isGetAll === TRUE ? "all " : "",
                $entityHumanName,
                $isGetAll === TRUE ? "" : " info"
            );
        }
        return $response;
    }
}