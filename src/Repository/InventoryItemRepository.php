<?php

namespace App\Repository;

use App\Document\InventoryItem;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;

class InventoryItemRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InventoryItem::class);
    }

    public function findByCategoryAndTag(string $category, string $tag)
    {
        return $this->createQueryBuilder()
            ->field($category)->equals($tag)
            ->getQuery()
            ->execute();
    }

    public function findOneRandomByCategoryAndTag(string $category, string $tag)
    {
        return $this->createQueryBuilder()
            ->field($category)->equals($tag)
            ->limit(1)
            ->getQuery()
            ->getSingleResult();
    }

    public function search(string $query)
    {
        $qb = $this->createQueryBuilder()->text($query);
        return $qb->getQuery()->execute();

        // $keywords = explode(' ', $query);
        // $qb->addOr($qb->expr()->field('name')->equals($keywords));
        // $qb->addOr($qb->expr()->field('manufacturer')->equals($keywords));
        // $qb->addOr($qb->expr()->field('name')->equals($keywords));
        // $qb->addOr($qb->expr()->field('notes')->equals($keywords));

        // return $qb->getQuery()->execute();

        #->getQuery()->getResult();
        //$qb->field(null)->equals($expr->getQuery());
    }
}
