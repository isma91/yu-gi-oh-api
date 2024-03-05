<?php

namespace App\Repository;

use App\Entity\SubType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubType>
 *
 * @method SubType|null find($id, $lockMode = null, $lockVersion = null)
 * @method SubType|null findOneBy(array $criteria, array $orderBy = null)
 * @method SubType[]    findAll()
 * @method SubType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubType::class);
    }

    //    /**
    //     * @return SubType[] Returns an array of SubType objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?SubType
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
