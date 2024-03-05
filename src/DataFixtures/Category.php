<?php

namespace App\DataFixtures;

use App\Entity\Category as CategoryEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class Category extends Fixture implements FixtureGroupInterface
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager): void
    {
        $arrayName = [
            "Monster" => FALSE,
            "Token" => FALSE,
            "Spell" => TRUE,
            "Trap" => TRUE
        ];
        $currentDate =  new \DateTime();
        foreach ($arrayName as $name => $acceptedSubCategory) {
            $slugName = $this->slugger->slug($name)->lower()->toString();
            $category = new CategoryEntity();
            $category->setName($name)
                ->setSlugName($slugName)
                ->setAcceptSubCategory($acceptedSubCategory)
                ->setCreatedAt($currentDate)
                ->setUpdatedAt($currentDate);
            $manager->persist($category);
        }
        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ["category"];
    }
}
