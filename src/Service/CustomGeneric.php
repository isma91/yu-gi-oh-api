<?php

namespace App\Service;

use App\Entity\User as UserEntity;
use App\Service\Tool\User\Auth as UserAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\FilterCollection;
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
     * @throws \JsonException
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
}