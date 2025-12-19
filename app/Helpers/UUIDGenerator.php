<?php
declare(strict_types=1);
namespace App\Helpers;

use Ramsey\Uuid\Uuid;

/**
 * Description of InternalIDGenerator
 *
 */
class UUIDGenerator {
    public static function generateInternalID(){
	return Uuid::uuid4()->toString();
    }
}
