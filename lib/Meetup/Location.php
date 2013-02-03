<?php
namespace Meetup;
class Location
{
    protected $lat;
    protected $lng;
    
    public function __construct($lat, $lng)
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }
    
    public function getLat()
    {
        return (float) $this->lat;
    }
    
    public function getLng()
    {
        return(float) $this->lng;
    }
   
    public function jsonSerialize()
    {
        return array(
            'lat' => $this->getLat(),
            'lng' => $this->getLng()
        );
    }
}