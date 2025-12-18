<?php
declare(strict_types=1);
namespace App\Database;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

use App\Helpers\UUIDGenerator;

use App\Database\InvoiceItem;

/**
 * @extends EntityRepository<InvoiceItem>
 */
class InvoiceItemRepository extends EntityRepository{
    CONST FIND_BY_SLUG_MAX_RESULTS = 10;
    
    /**
     * 
     * 
     * @param int $page
     * @param int $limit
     * 
     * @return Paginator
     */
    public function findPaginated(int $page, int $limit = 10): Paginator
    {
	$qb = $this->createQueryBuilder('ii')
	    ->select('ii')
	    ->orderBy('ii.created', 'ASC')
	    ->setFirstResult(($page - 1) * $limit)
	    ->setMaxResults($limit);

	return new Paginator($qb, true);
    }
}
