<?php

namespace App\DataFixtures;

use App\Entity\SubProperty;
use App\Entity\SubPropertyType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class SubPropertyWithType extends Fixture implements FixtureGroupInterface
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager): void
    {
        $subPropertyTypeWithTypeArrayName = [
            "Link Arrow" => [
                "Top-Left",
                "Top",
                "Top-Right",
                "Left",
                "Right",
                "Bottom-Left",
                "Bottom",
                "Bottom-Right",
            ],
            "Pendulum Scale" => range(0, 13),
        ];
        $currentDate =  new \DateTime();
        foreach ($subPropertyTypeWithTypeArrayName as $subPropertyTypeName => $subPropertyArrayName) {
            $subPropertyTypeSlugName = $this->slugger->slug($subPropertyTypeName)->lower()->toString();
            $subPropertyType = new SubPropertyType();
            $subPropertyType->setName($subPropertyTypeName)
                ->setSlugName($subPropertyTypeSlugName)
                ->setCreatedAt($currentDate)
                ->setUpdatedAt($currentDate);
            foreach ($subPropertyArrayName as $subPropertyName) {
                $subPropertySlugName = $this->slugger->slug($subPropertyName)->lower()->toString();
                $subProperty = new SubProperty();
                $subProperty->setName($subPropertyName)
                    ->setSlugName($subPropertySlugName)
                    ->setCreatedAt($currentDate)
                    ->setUpdatedAt($currentDate)
                    ->setSubPropertyType($subPropertyType);
                $manager->persist($subProperty);
                $subPropertyType->addSubProperty($subProperty);
            }
            $manager->persist($subPropertyType);
        }
        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ["subPropertyWithType"];
    }
}
