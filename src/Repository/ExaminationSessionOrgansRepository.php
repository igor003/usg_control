<?php

namespace App\Repository;

use App\Entity\ExaminationSessionOrgans;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExaminationSessionOrgans>
 */
class ExaminationSessionOrgansRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExaminationSessionOrgans::class);
    }
}
