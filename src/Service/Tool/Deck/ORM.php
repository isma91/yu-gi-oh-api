<?php

namespace App\Service\Tool\Deck;


use App\Entity\Deck;
use App\Entity\User;
use App\Repository\DeckRepository;
use App\Service\Tool\Abstract\AbstractORM;
use App\Service\Tool\ORMSearch;
use App\Service\Tool\ORMSlugName;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ORM extends AbstractORM
{
    protected ORMSlugName $ORMSlugName;
    protected ORMSearch $ORMSearch;

    public function __construct(
        EntityManagerInterface $em,
        DeckRepository $repository,
        SluggerInterface $slugger
    )
    {
        $this->ORMSearch = new ORMSearch($repository);
        $this->ORMSlugName = new ORMSlugName($repository, $slugger);
        parent::__construct($em, $repository);
    }

    /**
     * @return ORMSearch
     */
    public function getORMSearch(): ORMSearch
    {
        return $this->ORMSearch;
    }
}