<?php

namespace App\Repository;

use App\Entity\CardCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CardCollection>
 *
 * @method CardCollection|null find($id, $lockMode = null, $lockVersion = null)
 * @method CardCollection|null findOneBy(array $criteria, array $orderBy = null)
 * @method CardCollection[]    findAll()
 * @method CardCollection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CardCollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CardCollection::class);
    }

    //    /**
    //     * @return CardCollection[] Returns an array of CardCollection objects
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

    //    public function findOneBySomeField($value): ?CardCollection
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
