<?php

namespace App\Service;

use App\Document\InventoryItem;
use App\Document\Tag;
use Doctrine\ODM\MongoDB\DocumentManager;


class InventoryItemService
{
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
        $this->inventoryRepo = $this->dm->getRepository(InventoryItem::class);
        $this->tagRepo = $this->dm->getRepository(Tag::class);
    }

    public function searchInventoryItems(string $query): iterable
    {
        return $this->inventoryRepo->search($query)->toArray();
    }

    public function getRandomInventoryItemByTag(string $category, string $tag): ?InventoryItem
    {
        $result = $this->findByCategoryAndTag($category, $tag)->toArray();
        if ($result) {
            $index = mt_rand(0, (count($result) - 1));
            return $result[$index];
        } else {
            return null;
        }
    }

    public function getAll()
    {
        return $this->inventoryRepo->getAll()->toArray();
    }

    public function saveInventoryItem(InventoryItem $item, array $originalLocations = [], array $originalTypes = [], array $originalStates = []): string
    {
        if (!$item) {
            throw new \RuntimeException('Empty item can not be saved');
        }
        $item->setModifiedTime();
        $this->saveInventoryItemTags(Tag::CATEGORY_ITEM_TYPE, $originalTypes, $item->getTypes());
        $this->saveInventoryItemTags(Tag::CATEGORY_ITEM_LOCATION, $originalLocations, $item->getLocations());
        $this->saveInventoryItemTags(Tag::CATEGORY_ITEM_STATE, $originalStates, $item->getStates());
        $this->dm->persist($item);
        return (string) $item->getId();
    }

    protected function saveInventoryItemTags(string $category, array $originalTagStrings, array $updatedTagStrings)
    {
        foreach (array_diff($originalTagStrings, $updatedTagStrings) as $removed) {
            if ($tag = $this->tagRepo->findOneByName($category, $removed)) {
                $tag->decrementCount();
            }
        }
        foreach (array_diff($updatedTagStrings, $originalTagStrings) as $added) {
            $tag = $this->tagRepo->findOneByName($category, $added);
            if (!$tag) {
                $tag = new Tag();
                $tag->setName($added);
                $tag->setCategory($category);
            }
            $tag->incrementCount();
        }
    }

    public function deleteInventoryItem(InventoryItem $item)
    {
        if (!$item) {
            throw new \RuntimeException('Empty item can not be deleted');
        }
        $item->setDeleted(true);
        $this->saveInventoryItemTags(Tag::CATEGORY_ITEM_TYPE, $item->getTypes(), []);
        $this->saveInventoryItemTags(Tag::CATEGORY_ITEM_LOCATION, $item->getLocations(), []);
        $this->saveInventoryItemTags(Tag::CATEGORY_ITEM_STATE, $item->getStates(), []);
    }

    public function findByCategoryAndTag(string $category, string $tag): iterable
    {
        return $this->inventoryRepo->findByCategoryAndTag($category, $tag);
    }
}
