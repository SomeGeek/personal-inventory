<?php

namespace App\Service;

use App\Document\InventoryItem;
use Doctrine\ODM\MongoDB\DocumentManager;


class InventoryItemService
{
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
        $this->inventoryRepo = $this->dm->getRepository(InventoryItem::class);
    }

    public function searchInventoryItems(string $query): iterable
    {
        return $this->inventoryRepo->search($query)->toArray();
    }

    public function getRandomInventoryItemByTag(string $category, string $tag)
    {
        return $this->inventoryRepo->findOneByCategoryAndTag($category, $tag);
    }
}
