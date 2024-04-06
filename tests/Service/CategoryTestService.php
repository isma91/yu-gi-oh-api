<?php

namespace App\Tests\Service;

use App\Service\Category;
use App\Service\Tool\Category\ORM;
use Doctrine\ORM\EntityNotFoundException;

class CategoryTestService extends AbstractTestService
{
    private Category $service;

    public function setUp(): void
    {
        $this->service = self::getService(Category::class);
        parent::setUp();
    }

    public function testCategoryGetORMService():void
    {
        $this->assertInstanceOf(ORM::class, $this->service->getORMService());
    }

    /**
     * @return void
     * @throws EntityNotFoundException
     */
    public function testCategoryGetAll(): void
    {
        self::getAll($this->service, "category");
    }
}
