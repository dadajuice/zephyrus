<?php namespace Zephyrus\Utilities;

use Zephyrus\Security\SystemLog;

class Geolocation
{
    /**
     * Retrieve the latitude and longitude (as associative array data) of the
     * specified address using the geocode google api. Log error if network is
     * unreachable or if parsing fails. To verify if the location failed, check
     * the resulting latitude and longitude which will be null.
     *
     * @param string $address
     * @return array
     */
    public static function getPositionFromAddress($address) {
        $lat = null;
        $lng = null;
        try {
            $geocode = self::queryGoogleGeoCodeApi($address);
            if (isset($geocode->results[0]->geometry->location->lat)) {
                $lat = $geocode->results[0]->geometry->location->lat;
            }
            if (isset($geocode->results[0]->geometry->location->lng)) {
                $lng = $geocode->results[0]->geometry->location->lng;
            }

            if (is_null($lng) || is_null($lat)) {
                SystemLog::addError("Error while parsing JSON GeoCode : " . $geocode);
            }
        } catch (\Exception $e) {
            SystemLog::addError($e->getMessage());
        }
        return ['lat' => $lat, 'lng' => $lng];
    }

    /**
     * Does a CURL request to the google geocode api with the specified address
     * to lookup. Throws an exception is network is unreachable.
     *
     * @param string $address
     * @return Object
     * @throws \Exception
     */
    private static function queryGoogleGeoCodeApi($address)
    {
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        if (!$response) {
            throw new \Exception("Error while attempting HTTP query for Google GeoCode API : " . curl_error($curl) . '" - Code: ' . curl_errno($curl));
        }
        return json_decode($response);
    }
}