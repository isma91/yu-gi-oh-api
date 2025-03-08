<?php
namespace App\Repository;

use Doctrine\ORM\QueryBuilder;

final class FindAllContainingCardRepository
{
    public function findAllContainingCard(
        QueryBuilder $queryBuilder,
        string $tableNameAlias,
        int $cardId,
        string $cardTableAlias = 'c',
        string $cardFieldName = 'cards'
    ): array
    {
        $innerJoin = sprintf('%s.%s', $tableNameAlias, $cardFieldName);
        $where = sprintf('%s.id = :cardId', $cardTableAlias);
        return $queryBuilder
            ->innerJoin($innerJoin, $cardTableAlias)
            ->where($where)
            ->setParameter('cardId', $cardId)
            ->getQuery()
            ->getResult();
    }
}