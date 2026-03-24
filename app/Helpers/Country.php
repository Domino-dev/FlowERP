<?php
declare(strict_types=1);
namespace App\Helpers;


class Country {
    
    CONST COUNTRIES_PATH = __DIR__ . "/../Resources/countries.json";
    
    static function getCountries(?string $filterCountryISO = null){
	$countriesJSON = file_get_contents(self::COUNTRIES_PATH);
	
	$countries = json_decode($countriesJSON, true);
	
	if(!empty($filterCountryISO)){
	    foreach($countries as $countryISO => $country){
		if($filterCountryISO !== $countryISO){
		    unset($countries[$countryISO]);
		}
	    }
	}
	
	return $countries;
    }
    
    static function getCountryByISO(string $countryISO){
	$countries = self::getCountries();
	
	$filteredISO = array_filter(
		$countries, 
		fn($key) => $key === $countryISO,
		ARRAY_FILTER_USE_KEY
	);
	
	$countryNameArr = array_values($filteredISO);
	
	return isset($countryNameArr[0]) ? $countryNameArr[0] : null;
    }
    
}
