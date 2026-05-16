<?php

namespace App\Repository;

use App\Entity\UltrasoundTypeOrgan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UltrasoundTypeOrgan>
 */
class UltrasoundTypeOrganRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UltrasoundTypeOrgan::class);
    }
}
