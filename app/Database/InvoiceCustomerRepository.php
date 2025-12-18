<?php
declare(strict_types=1);
namespace App\Database;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

use App\Helpers\UUIDGenerator;

use App\Database\InvoiceCustomer;

/**
 * @extends EntityRepository<InvoiceCustomer>
 */
class InvoiceCustomerRepository extends EntityRepository{
    
}
