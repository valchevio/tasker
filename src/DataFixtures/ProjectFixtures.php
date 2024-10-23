<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\Task;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProjectFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        for ($i = 0; $i < 100; $i++) {
            $project = new Project();
            $project->setTitle($faker->sentence(3));
            $project->setDescription($faker->paragraph());
            $project->setStatus($faker->randomElement(['Open', 'In Progress', 'Closed']));
            $project->setDuration($faker->numberBetween(30, 90));
            
            if ($faker->boolean(50)) {
                $project->setClient($faker->company);
            } else {
                $project->setCompany($faker->company);
            }

            if ($faker->boolean(50)) { 
                $project->setDeletedAt(new \DateTimeImmutable());
            }

            $manager->persist($project);

            for ($j = 0; $j < 10; $j++) {
                $task = new Task();
                $task->setName($faker->sentence(4));
                $task->setProject($project);

                if ($faker->boolean(30)) { 
                    $task->setDeletedAt(new \DateTimeImmutable());
                }

                $manager->persist($task);
            }
        }

        $manager->flush();
    }
}
