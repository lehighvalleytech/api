<?php
namespace Meetup;

class Venue
{
    protected $data;
    
    public function __construct($data)
    {
        $this->data = $data;
    }
    
    public function getName()
    {
        return $this->data['name'];
    }

    public function getStreet()
    {
        return $this->data['address_1'];
    }
    
    public function getState()
    {
        return $this->data['state'];
    }
    
    public function getCity()
    {
        return $this->data['city'];
    }
    
    public function getZip()
    {
        return $this->data['zip'];
    }

    public function getCountry()
    {
        return $this->data['country'];
    }
    
    public function getLocation()
    {
        return new Location($this->data['lat'], $this->data['lon']);
    }
    
    public function jsonSerialize()
    {
        return array(
            'name' => $this->getName(),
            'street' => $this->getStreet(),
            'state' => $this->getState(),
            'city' => $this->getCity(),
            'zip' => $this->getZip(),
            'country' => $this->getCountry(),
            'location' => $this->getLocation()->jsonSerialize() 
        );
    }
}