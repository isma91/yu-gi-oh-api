<?php

namespace App\Tests\Service;

use App\Service\Type;
use App\Service\Tool\Type\ORM;
use Doctrine\ORM\EntityNotFoundException;

class TypeServiceTest extends AbstractTestService
{
    private Type $service;

    public function setUp(): void
    {
        $this->service = self::getService(Type::class);
        parent::setUp();
    }

    public function testTypeGetORMService():void
    {
        $this->assertInstanceOf(ORM::class, $this->service->getORMService());
    }

    /**
     * @return void
     * @throws EntityNotFoundException
     */
    public function testTypeGetAll(): void
    {
        self::getAll($this->service, "type");
    }
}
