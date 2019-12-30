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
            ->field('deleted')->equals(false)
            ->getQuery()
            ->execute();
    }

    public function getAll()
    {
        return $this->createQueryBuilder()
            ->field('deleted')->equals(false)
            ->getQuery()
            ->execute();
    }

    public function search(string $query)
    {
        $qb = $this->createQueryBuilder()
            ->field('deleted')->equals(false)
            ->text($query);
        return $qb->getQuery()->execute();
    }
}
