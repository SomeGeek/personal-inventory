<?php

namespace App\Repository;

use App\Document\Tag;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;

class TagRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function findAllByCategory(string $category)
    {
        return $this->createQueryBuilder()
            ->field('category')->equals($category)
            ->sort(array(
                'count' => 'desc',
                'name'  => 'desc',
            ))
            ->getQuery()
            ->execute();
    }

    public function findOneByName(string $category, string $tagName) : ?Tag
    {
        $item = $this->createQueryBuilder()
            ->field('category')->equals($category)
            ->field('name')->equals($tagName)
            ->getQuery()
            ->getSingleResult();
        return $item;
    }
}
