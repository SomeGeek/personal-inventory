<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use App\Repository\TagRepository;


/**
 * @MongoDB\Document(repositoryClass=TagRepository::class)
 */
class Tag
{
    // Note these match item fields for convenience
    const CATEGORY_ITEM_LOCATION = 'locations';
    const CATEGORY_ITEM_TYPE = 'types';

    /**
     * @MongoDB\Id
     */
    protected $id;

    /** @MongoDB\Field(type="string") */
    protected $category = self::CATEGORY_ITEM_TYPE;

    /** @MongoDB\Field(type="string") */
    protected $name = '';

    /** @MongoDB\Field(type="int") */
    protected $count = 0;

    public function setCategory(string $category)
    {
        $this->category = $category;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function incrementCount()
    {
        $this->count++;
    }

    public function decrementCount()
    {
        $this->count--;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * Set modified time to now
     */
    public function setModifiedTime()
    {
        $this->modifiedTime = time();
    }
    public function getModifiedTime()
    {
        return new \DateTime('@' . $this->modifiedTime);
    }
}
