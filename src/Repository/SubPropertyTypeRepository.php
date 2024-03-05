<?php

namespace App\Repository;

use App\Entity\SubPropertyType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubPropertyType>
 *
 * @method SubPropertyType|null find($id, $lockMode = null, $lockVersion = null)
 * @method SubPropertyType|null findOneBy(array $criteria, array $orderBy = null)
 * @method SubPropertyType[]    findAll()
 * @method SubPropertyType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubPropertyTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubPropertyType::class);
    }

    //    /**
    //     * @return SubPropertyType[] Returns an array of SubPropertyType objects
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

    //    public function findOneBySomeField($value): ?SubPropertyType
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
