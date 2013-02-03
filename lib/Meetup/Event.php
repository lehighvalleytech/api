<?php
namespace Meetup;
class Event
{
    protected $data;
    
    public function __construct($data)
    {
        $this->data = $data;
    }
    
    public function getId()
    {
        return $this->data['id'];
    }
    
    public function isFeatured()
    {
        return $this->data['featured'];
    }
    
    public function getTitle()
    {
        return $this->data['name'];
    }
    
    public function getDescription()
    {
        return $this->data['description'];
    }
    
    public function getTime()
    {
        return new \DateTime('@' . floor($this->data['time']/1000));
    }
    
    public function getVenue()
    {
        return new Venue($this->data['venue']);
    }
    
    public function getNotes()
    {
        return $this->data['how_to_find_us'];
    }
    
    public function getUrl()
    {
        return $this->data['event_url'];
    }
    
    public function jsonSerialize()
    {
        return array(
            'id' => $this->getId(),
            'url' => $this->getUrl(),
        	'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'notes' => $this->getNotes(),
            'timestamp' => $this->getTime()->getTimestamp(),
            'time' => $this->getTime()->format('r'),
            'venue' => $this->getVenue()->jsonSerialize()
        );
    }
}