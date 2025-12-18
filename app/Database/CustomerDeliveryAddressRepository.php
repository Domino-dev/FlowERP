<?php 
declare(strict_types=1);

namespace App\Database;

use App\Database\CustomerDeliveryAddress;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends EntityRepository<CustomerDeliveryAddress>
 */
final class CustomerDeliveryAddressRepository extends EntityRepository
{    
    
}