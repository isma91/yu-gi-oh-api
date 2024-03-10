<?php

namespace App\DataFixtures;

use App\Entity\Property;
use App\Entity\PropertyType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class PropertyWithType extends Fixture implements FixtureGroupInterface
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager): void
    {
        $propertyTypeArrayName = [
            "Level",
            "Rank",
            "Link Rating"
        ];
        $nbLimit = 13;
        $currentDate =  new \DateTime();
        $propertyTypeEntityArray = [];
        foreach ($propertyTypeArrayName as $name) {
            $slugName = $this->slugger->slug($name)->lower()->toString();
            $propertyType = new PropertyType();
            $propertyType->setName($name)
                ->setSlugName($slugName)
                ->setCreatedAt($currentDate)
                ->setUpdatedAt($currentDate);
            $manager->persist($propertyType);
            $propertyTypeEntityArray[] = $propertyType;
        }
        foreach ($propertyTypeEntityArray as $propertyTypeEntity) {
            for ($i = 0; $i < $nbLimit; $i++) {
                $propertyEntity = new Property();
                $propertyEntity->setName($i)
                    ->setSlugName($i)
                    ->setCreatedAt($currentDate)
                    ->setUpdatedAt($currentDate);
                $propertyTypeEntity->addProperty($propertyEntity);
                $manager->persist($propertyEntity);
                $manager->persist($propertyTypeEntity);
            }
        }
        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ["propertyWithType"];
    }
}
