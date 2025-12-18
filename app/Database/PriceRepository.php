<?php 
declare(strict_types=1);
namespace App\Database;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

use App\Helpers\UUIDGenerator;

use App\Database\Price;

/**
 * @extends EntityRepository<Price>
 */
final class PriceRepository extends EntityRepository
{   
    
    CONST FIND_PAGINATED_LIMIT = 2;
    
    public function getFindPaginatedLimit():int{
	return self::FIND_PAGINATED_LIMIT;
    }
    
    
    /**
     * 
     * 
     * @param int $page
     * @param int $limit
     * 
     * @return Paginator
     */
    public function findPaginated(int $page, string $searchSlug): Paginator{
	$qb = $this->createQueryBuilder('p')
	    ->join('p.product', 'prod')
	    ->where('prod.catalogueCode LIKE :searchslug')
	    ->setParameter('searchslug', '%' . $searchSlug . '%')
	    ->orderBy('p.created', 'DESC')
	    ->setFirstResult(($page - 1) * self::FIND_PAGINATED_LIMIT)
	    ->setMaxResults(self::FIND_PAGINATED_LIMIT);
	
	return new Paginator($qb, true);
    }
    
    public function savePrice(Price $price): void{
	$this->getEntityManager()->persist($price);
    }
}
