<?php

namespace App\Repository;

use App\Entity\CardCardCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CardCardCollection>
 *
 * @method CardCardCollection|null find($id, $lockMode = null, $lockVersion = null)
 * @method CardCardCollection|null findOneBy(array $criteria, array $orderBy = null)
 * @method CardCardCollection[]    findAll()
 * @method CardCardCollection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CardCardCollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CardCardCollection::class);
    }

    //    /**
    //     * @return CardCardCollection[] Returns an array of CardCardCollection objects
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

    //    public function findOneBySomeField($value): ?CardCardCollection
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
