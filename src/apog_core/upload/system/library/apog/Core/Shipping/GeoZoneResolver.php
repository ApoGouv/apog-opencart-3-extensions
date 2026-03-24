<?php
namespace Apog\Core\Shipping;

class GeoZoneResolver {

    private $db;

    public function __construct($registry) {
        $this->db = $registry->get('db');
    }

    public function getMatchingZones($address) {

        $query = $this->db->query("
            SELECT geo_zone_id 
            FROM " . DB_PREFIX . "zone_to_geo_zone 
            WHERE country_id = '" . (int)$address['country_id'] . "' 
            AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')
        ");

        return $query->num_rows ? $query->rows : [];
    }
}