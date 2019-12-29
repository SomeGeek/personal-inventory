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
}
