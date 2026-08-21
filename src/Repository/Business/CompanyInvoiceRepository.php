<?php

namespace App\Repository\Business;

use App\Entity\Business\CompanyInvoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompanyInvoice>
 *
 * @method CompanyInvoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method CompanyInvoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method CompanyInvoice[]    findAll()
 * @method CompanyInvoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyInvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanyInvoice::class);
    }
}
