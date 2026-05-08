<?php

namespace App\Repository;

use App\Entity\ExaminationSessionParameterResults;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExaminationSessionParameterResults>
 */
class ExaminationSessionParameterResultsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExaminationSessionParameterResults::class);
    }
}
