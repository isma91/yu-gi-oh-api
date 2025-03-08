<?php

namespace App\Repository;

use App\Entity\CardMainDeck;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CardMainDeck>
 *
 * @method CardMainDeck|null find($id, $lockMode = null, $lockVersion = null)
 * @method CardMainDeck|null findOneBy(array $criteria, array $orderBy = null)
 * @method CardMainDeck[]    findAll()
 * @method CardMainDeck[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CardMainDeckRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly FindAllContainingCardRepository $findAllContainingCardRepository
    )
    {
        parent::__construct($registry, CardMainDeck::class);
    }

    /**
     * @param int $cardId
     * @return CardMainDeck[]
     */
    public function findAllContainingCard(int $cardId): array
    {
        $tableAliasName = 'cmd';
        $qb = $this->createQueryBuilder($tableAliasName);
        return $this->findAllContainingCardRepository
            ->findAllContainingCard($qb, $tableAliasName, $cardId);
    }

    //    /**
    //     * @return CardMainDeck[] Returns an array of CardMainDeck objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?CardMainDeck
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
