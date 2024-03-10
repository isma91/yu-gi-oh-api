<?php

namespace App\DataFixtures;

use App\Entity\Category as CategoryEntity;
use App\Entity\SubCategory as SubCategoryEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class CategoryMonsterWithSubCategory extends Fixture implements FixtureGroupInterface
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager): void
    {
        $arrayName = [
            "XYZ", "Synchro", "Fusion", "Ritual", "Link"
        ];
        $currentDate =  new \DateTime();
        $category = new CategoryEntity();
        $category->setName("Monster")
            ->setSlugName("monster")
            ->setCreatedAt($currentDate)
            ->setUpdatedAt($currentDate);
        foreach ($arrayName as $name) {
            $slugName = $this->slugger->slug($name)->lower()->toString();
            $subCategory = new SubCategoryEntity();
            $subCategory->setName($name)
                ->setSlugName($slugName)
                ->setCreatedAt($currentDate)
                ->setUpdatedAt($currentDate);
            $category->addSubCategory($subCategory);
            $manager->persist($subCategory);
        }
        $manager->persist($category);
        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ["categoryMonsterWithSubCategory"];
    }
}
