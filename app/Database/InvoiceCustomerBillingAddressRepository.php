<?php
declare(strict_types=1);
namespace App\Database;

use Doctrine\ORM\EntityRepository;

use App\Database\InvoiceCustomerBillingAddress;

/**
 * @extends EntityRepository<InvoiceCustomerBillingAddress>
 */
class InvoiceCustomerBillingAddressRepository extends EntityRepository{
    //put your code here
}
