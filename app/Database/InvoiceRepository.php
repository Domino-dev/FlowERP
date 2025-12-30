<?php
declare(strict_types=1);
namespace App\Database;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

use App\Helpers\UUIDGenerator;

use App\Database\Invoice;

/**
 * @extends EntityRepository<Invoice>
 */
class InvoiceRepository extends EntityRepository{
    CONST FIND_PAGINATED_LIMIT = 2;
    /**
     * 
     * 
     * @param int $page
     * @param string $searchSlug
     * 
     * @return Paginator
     */
    public function findPaginated(int $page, string $searchSlug, array $statusCode = []): Paginator{
	$qb = $this->createQueryBuilder('i')
	    ->join('i.invoiceCustomer', 'invCust')
	    ->where('i.number LIKE :searchslug OR invCust.name LIKE :searchslug')
	    ->setParameter('searchslug', '%'.$searchSlug.'%');
	    
	if(!empty($statusCode)){
	    $qb->andWhere('i.status IN (:statuscode)')
		->setParameter('statuscode', $statusCode);
	}
	    
	$qb->orderBy('i.created', 'DESC')
	->setFirstResult(($page - 1) * self::FIND_PAGINATED_LIMIT)
	->setMaxResults(self::FIND_PAGINATED_LIMIT);
	
	return new Paginator($qb, true);
    }
}
