<?php

namespace App\Service;

use App\Document\Tag;
use App\Service\ImageStorage;
use Doctrine\ODM\MongoDB\DocumentManager;


class TagService
{

    public function __construct(DocumentManager $dm, ImageStorage $images)
    {
        $this->dm = $dm;
        $this->tagRepo = $this->dm->getRepository(Tag::class);
        $this->images = $images;
    }

    /**
     * Get "top" 5 tags by category
     *
     * @param string $category One of Tag::CATEGORY_*
     */
    public function getTopTags(string $category): iterable
    {
        $collection = $this->tagRepo->findAllByCategory($category)->toArray();
        // TODO: Filter and sort for top 5 tags
        return $collection;
    }
}
