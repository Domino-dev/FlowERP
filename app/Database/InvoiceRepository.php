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
    public function findPaginated(int $page, string $searchSlug): Paginator
    {
	$qb = $this->createQueryBuilder('i')
	    ->where('i.number LIKE :searchslug')
	    ->setParameter('searchslug', '%'.$searchSlug.'%')
	    ->select('i')
	    ->orderBy('i.created', 'DESC')
	    ->setFirstResult(($page - 1) * self::FIND_PAGINATED_LIMIT)
	    ->setMaxResults(self::FIND_PAGINATED_LIMIT);
	
	return new Paginator($qb, true);
    }
}
