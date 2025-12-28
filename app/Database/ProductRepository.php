<?php 
declare(strict_types=1);
namespace App\Database;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

use App\Helpers\UUIDGenerator;

use App\Database\Product;

/**
 * @extends EntityRepository<Product>
 */
final class ProductRepository extends EntityRepository
{    
    CONST FIND_BY_SLUG_MAX_RESULTS = 5;
    CONST FIND_PAGINATED_LIMIT = 2;
    
    public function getPaginationLimit():int{
	return self::FIND_PAGINATED_LIMIT;
    }
    
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
	$qb = $this->createQueryBuilder('p')
	    ->where('p.catalogueCode LIKE :searchslug OR p.name LIKE :searchslug')
	    ->setParameter('searchslug', '%'.$searchSlug.'%')
	    ->select('p')
	    ->orderBy('p.created', 'DESC')
	    ->setFirstResult(($page - 1) * self::FIND_PAGINATED_LIMIT)
	    ->setMaxResults(self::FIND_PAGINATED_LIMIT);
	
	return new Paginator($qb, true);
    }
    
    public function findMultipleBySlug(string $slug):array{
	$products = $this->createQueryBuilder('p')
		->select('p')
		->where('p.catalogueCode LIKE :slug')
		->setParameter('slug', '%'.$slug.'%')
		->orderBy('p.catalogueCode','ASC')
		->setMaxResults(self::FIND_BY_SLUG_MAX_RESULTS)
		->getQuery()
		->getResult();
	
	return $products;
    }
    
    public function findByInternalIDsAndCatalogueCodes(array $internalIDs, array $catalogueCodes):?array{
	
	$qb = $this->createQueryBuilder('p')
		->where('p.internalID = :internalIDs')
		->orWhere('p.catalogueCode = :catalogueCodes')
		->setParameter('internalIDs', $internalIDs)
		->setParameter('catalogueCodes', $catalogueCodes)
		->distinct();
	
	return $qb->getQuery()->getResult();
    }
}
