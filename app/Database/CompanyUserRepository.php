<?php 
declare(strict_types=1);
namespace App\Database;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

use App\Helpers\UUIDGenerator;

use App\Database\CompanyUser;

/**
 * @extends EntityRepository<CompanyUser>
 */
final class CompanyUserRepository extends EntityRepository
{    
    CONST FIND_PAGINATED_LIMIT = 2;
    
    public function getPaginatedLimit():int{
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
	$qb = $this->createQueryBuilder('cu')
	    ->where('cu.name LIKE :searchslug')
	    ->setParameter('searchslug', '%'.$searchSlug.'%')
	    ->select('cu')
	    ->orderBy('cu.created', 'DESC')
	    ->setFirstResult(($page - 1) * self::FIND_PAGINATED_LIMIT)
	    ->setMaxResults(self::FIND_PAGINATED_LIMIT);
	
	return new Paginator($qb, true);
    }
}
