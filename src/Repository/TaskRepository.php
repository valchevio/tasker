<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Task>
 *
 * @method Task|null find($id, $lockMode = null, $lockVersion = null)
 * @method Task|null findOneBy(array $criteria, array $orderBy = null)
 * @method Task[]    findAll()
 * @method Task[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function findActiveTask($id): mixed
    {
        $binaryId = Uuid::fromString($id)->toBinary();

        return $this->createQueryBuilder('p')
            ->andWhere('p.deletedAt IS NULL')    
            ->andWhere('p.id = :id')
            ->setParameter('id', $binaryId)
            ->getQuery()
            ->getOneOrNullResult();        
    }

    public function findActiveTasks(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.deletedAt IS NULL')
            ->getQuery()
            ->getResult();        
    }
}
