<?php 
declare(strict_types=1);
namespace App\Database;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

use App\Helpers\UUIDGenerator;

use App\Database\PriceList;

/**
 * @extends EntityRepository<PriceList>
 */
final class PriceListRepository extends EntityRepository
{    
    CONST FIND_PAGINATED_LIMIT = 2;
    
    public function getPaginatedLimit():int{
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
    public function findPaginated(int $page, string $searchSlug): Paginator{
	$qb = $this->createQueryBuilder('p')
	    ->where('p.name LIKE :searchslug')
	    ->setParameter('searchslug', '%'.$searchSlug.'%')
	    ->select('p')
	    ->orderBy('p.created', 'DESC')
	    ->setFirstResult(($page - 1) * self::FIND_PAGINATED_LIMIT)
	    ->setMaxResults(self::FIND_PAGINATED_LIMIT);
	
	return new Paginator($qb, true);
    }
    
    public function getDefaultPriceList():?PriceList{
	return $this->findOneBy(['isDefault' => true]);
    }    
}
