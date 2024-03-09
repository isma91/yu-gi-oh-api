<?php

namespace App\Service\Tool\PropertyType;


use App\Repository\PropertyTypeRepository;
use App\Service\Tool\Abstract\AbstractORM;
use App\Service\Tool\ORMSlugName;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ORM extends AbstractORM
{
    protected ORMSlugName $ORMSlugName;

    public function __construct(EntityManagerInterface $em, PropertyTypeRepository $repository, SluggerInterface $slugger)
    {
        $this->ORMSlugName = new ORMSlugName($repository, $slugger);
        parent::__construct($em, $repository);
    }
}