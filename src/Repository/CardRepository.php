<?php

namespace App\Repository;

use App\Entity\Card;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 *
 * @method Card|null find($id, $lockMode = null, $lockVersion = null)
 * @method Card|null findOneBy(array $criteria, array $orderBy = null)
 * @method Card[]    findAll()
 * @method Card[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    /**
     * @param QueryBuilder $qb
     * @param array $filter
     * @return QueryBuilder
     */
    public function returnQueryBuilderSearchFilter(QueryBuilder $qb, array $filter): QueryBuilder
    {
        if (isset($filter["slugName"]) === TRUE) {
            $qb->andWhere("card.slugName LIKE :cardSlugName OR card.slugDescription LIKE :cardSlugName")
                ->setParameter("cardSlugName", '%' . $filter["slugName"] . '%');
        }
        if (isset($filter["isPendulum"]) === TRUE) {
            $qb->andWhere("card.isPendulum = :isPendulum")
                ->setParameter("isPendulum", $filter["isPendulum"]);
        }
        if (isset($filter["isEffect"]) === TRUE) {
            $qb->andWhere("card.isEffect = :isEffect")
                ->setParameter("isEffect", $filter["isEffect"]);
        }
        if (isset($filter["archetype"]) === TRUE) {
            $qb->andWhere("card.archetype IN (:archetypeArray)")
                ->setParameter("archetypeArray", $filter["archetype"]);
        }
        if (isset($filter["attribute"]) === TRUE) {
            $qb->andWhere("card.attribute IN (:attributeArray)")
                ->setParameter("attributeArray", $filter["attribute"]);
        }
        if (isset($filter["category"]) === TRUE) {
            $qb->andWhere("card.category = :category")
                ->setParameter("category", $filter["category"]);
        }
        if (isset($filter["subCategory"]) === TRUE) {
            $qb->andWhere("card.subCategory = :subCategory")
                ->setParameter("subCategory", $filter["subCategory"]);
        }
        if (isset($filter["property"]) === TRUE) {
            $qb->andWhere("card.property IN (:propertyArray)")
                ->setParameter("propertyArray", $filter["property"]);
        }
        if (isset($filter["subProperty"]) === TRUE) {
            $qb->innerJoin("card.subProperties", "subProperties")
                ->andWhere("subProperties.id IN (:subPropertyArray)")
                ->setParameter("subPropertyArray", $filter["subProperty"]);
        }
        if (isset($filter["subType"]) === TRUE) {
            $qb->innerJoin("card.subTypes", "subTypes")
                ->andWhere("subTypes.id IN (:subTypeArray)")
                ->setParameter("subTypeArray", $filter["subType"]);
        }
        if (isset($filter["type"]) === TRUE) {
            $qb->andWhere("card.type IN (:typeArray)")
                ->setParameter("typeArray", $filter["type"]);
        }
        return $qb;
    }

    /**
     * @param array $filter
     * @param int $offset
     * @param int $limit
     * @return Card[]
     */
    public function findFromSearchFilter(array $filter, int $offset, int $limit): array
    {
        $qb = $this->createQueryBuilder("card");
        $qb = $this->returnQueryBuilderSearchFilter($qb, $filter);
        return $qb->setMaxResults($limit)
            ->setFirstResult($offset * $limit)
            ->orderBy("card.slugName", "ASC")
            ->getQuery()->getResult();
    }

    /**
     * @param array $filter
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countFromSearchFilter(array $filter): int
    {
        $qb = $this->createQueryBuilder("card")
            ->select("COUNT(card.id)");
        $qb = $this->returnQueryBuilderSearchFilter($qb, $filter);
        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return Card[] Returns an array of Card objects
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

    //    public function findOneBySomeField($value): ?Card
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
