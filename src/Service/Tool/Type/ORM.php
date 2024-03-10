<?php

namespace App\Service\Tool\Type;


use App\Repository\TypeRepository;
use App\Service\Tool\Abstract\AbstractORM;
use App\Service\Tool\ORMSlugName;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ORM extends AbstractORM
{
    protected ORMSlugName $ORMSlugName;

    public function __construct(EntityManagerInterface $em, TypeRepository $repository, SluggerInterface $slugger)
    {
        $this->ORMSlugName = new ORMSlugName($repository, $slugger);
        parent::__construct($em, $repository);
    }
}