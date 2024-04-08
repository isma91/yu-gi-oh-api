<?php

namespace App\Tests\Service;

use App\Repository\SetRepository;
use App\Service\Set;
use App\Service\Tool\Set\ORM;
use Doctrine\ORM\EntityNotFoundException;

class SetServiceTest extends AbstractTestService
{
    private Set $service;

    public function setUp(): void
    {
        $this->service = self::getService(Set::class);
        parent::setUp();
    }

    public function testSetGetORMService():void
    {
        $this->assertInstanceOf(ORM::class, $this->service->getORMService());
    }

    /**
     * @return void
     * @throws EntityNotFoundException
     */
    public function testSetGetInfo(): void
    {
        $setRepository = self::getService(SetRepository::class);
        $sets = $setRepository->findBy(["code" => "RA01"]);
        if (empty($sets) === TRUE) {
            throw new EntityNotFoundException("Set with coe 'RA01' not found, maybe you forgot to run the Import before testing ??");
        }
        [
            "error" => $error,
            "set" => $setInfo
        ] = $this->service->getInfo($sets[0]);
        $this->assertEmpty($error);
        $this->assertSame($sets[0]->getId(), $setInfo["id"]);
    }
}
