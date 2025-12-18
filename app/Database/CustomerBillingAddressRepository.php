<?php 
declare(strict_types=1);

namespace App\Database;

use App\Database\CustomerBillingAddress;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends EntityRepository<CustomerBillingAddress>
 */
final class CustomerBillingAddressRepository extends EntityRepository
{    
    
}