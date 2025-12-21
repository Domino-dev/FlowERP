<?php 
declare(strict_types=1);

namespace App\Database;

use App\Database\Customer;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends EntityRepository<Customer>
 */
final class CustomerRepository extends EntityRepository
{    
    
    CONST FIND_BY_SLUG_MAX_RESULTS = 5;
    CONST FIND_PAGINATED_LIMIT = 2;
    
    public function getPagiantionLimit():int{
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
	$qb = $this->createQueryBuilder('c')
	    ->where('c.name LIKE :searchslug OR c.email LIKE :searchslug OR c.vatNumber LIKE :searchslug')
	    ->setParameter('searchslug', '%'.$searchSlug.'%')
	    ->select('c')
	    ->orderBy('c.created', 'DESC')
	    ->setFirstResult(($page - 1) * self::FIND_PAGINATED_LIMIT)
	    ->setMaxResults(self::FIND_PAGINATED_LIMIT);
	
	return new Paginator($qb, true);
    }
    
    public function findByString(string $searchSlug){
	$customers = $this->createQueryBuilder('c')
		->where('c.email LIKE :searchslug OR c.vatNumber LIKE :searchslug')
		->setParameter('searchslug', '%'.$searchSlug.'%')
		->select('c.name,c.companyName')
		->setMaxResults(10)
		->getQuery()
		->getResult();
	
	return $customers;
    }
    
    public function findMultipleBySlug(string $slug, int $limit):array{
	$customers = $this->createQueryBuilder('c')
		->select('c')
		->where("c.isEnabled = true AND c.name LIKE :slug OR c.identificator LIKE :slug OR c.email LIKE :slug")
		->orwhere('c.companyName LIKE :slug')
		->setParameter('slug', '%'.$slug.'%')
		->orderBy("c.name",'ASC')
		->setMaxResults($limit)
		->getQuery()
		->getResult();
	
	return $customers;
    }
    
    public function findLastCustomerIdentificator():?string{
	$query = $this->createQueryBuilder('c');
	
	$customerIdentificatorRow = $query->where($query->expr()->like('c.identificator', ':prefix'))
		->setParameter('prefix', 'CUST%')
		->orderBy('c.identificator','DESC')
		->select('c.identificator')
		->setMaxResults(1)
		->getQuery()
		->getOneOrNullResult();
	
	return isset($customerIdentificatorRow['identificator']) ? $customerIdentificatorRow['identificator'] : null;
    }
}