<?php

namespace App\Tests\Service;

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
    }
}
