<?php

namespace App\Repository;

use App\Entity\SubProperty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubProperty>
 *
 * @method SubProperty|null find($id, $lockMode = null, $lockVersion = null)
 * @method SubProperty|null findOneBy(array $criteria, array $orderBy = null)
 * @method SubProperty[]    findAll()
 * @method SubProperty[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubPropertyRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly FindAllContainingCardRepository $findAllContainingCardRepository
    )
    {
        parent::__construct($registry, SubProperty::class);
    }

    /**
     * @param int $cardId
     * @return SubProperty[]
     */
    public function findAllContainingCard(int $cardId): array
    {
        $tableAliasName = 'sp';
        $qb = $this->createQueryBuilder($tableAliasName);
        return $this->findAllContainingCardRepository
            ->findAllContainingCard($qb, $tableAliasName, $cardId);
    }

    //    /**
    //     * @return SubProperty[] Returns an array of SubProperty objects
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

    //    public function findOneBySomeField($value): ?SubProperty
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
