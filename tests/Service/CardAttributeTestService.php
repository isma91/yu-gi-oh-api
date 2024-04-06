<?php

namespace App\Tests\Service;

use App\Service\CardAttribute;
use App\Service\Tool\CardAttribute\ORM;
use Doctrine\ORM\EntityNotFoundException;

class CardAttributeTestService extends AbstractTestService
{
    private CardAttribute $service;

    public function setUp(): void
    {
        $this->service = self::getService(CardAttribute::class);
        parent::setUp();
    }

    public function testCardAttributeGetORMService():void
    {
        $this->assertInstanceOf(ORM::class, $this->service->getORMService());
    }

    /**
     * @return void
     * @throws EntityNotFoundException
     */
    public function testCardAttributeGetAll(): void
    {
        self::getAll($this->service, "cardAttribute");
    }
}
