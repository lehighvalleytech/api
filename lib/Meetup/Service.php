<?php
namespace Meetup;

class Service
{
    const API = 'https://api.meetup.com';
    const API_EVENTS = '/2/events';
    
    protected $httpClient;
    protected $key;
    protected $group;
    protected $api;
    
    public function __construct($key, $group, $api = self::API)
    {
        $this->key = $key;
        $this->group = $group;
        $this->api = $api;
    }
    
    public function getEvents(Range $range, $endpoint = self::API_EVENTS)
    {
        $client = $this->getHttpClient();
        $time = $range->getStart()->getTimestamp() * 1000 . ',';
        if($range->hasEnd()){
            $time .= $range->getEnd()->getTimestamp() * 1000;
        }
                             
        
        $client->getRequest()->setUri($this->api . $endpoint)
                             ->getQuery()->set('key', $this->key)
                                         ->set('group_id', $this->group)
                                         ->set('time', $time)
                                         ->set('fields', 'featured');
        $response = $client->send();
        
        if($response->getStatusCode() != 200){
            throw new \UnexpectedValueException('bad response from api');
        }

        $data = \Zend\Json\Decoder::decode($response->getBody(), true);

        if(!isset($data['results'])){
            throw new \UnexpectedValueException('missing results property');
        }
        
        $events = array();
        foreach($data['results'] as $result){
            $events[] = new Event($result);
        }
        
        return $events;
    }
    
    public function getNextFeatured(Range $range)
    {
        $events = $this->getEvents($range);
        foreach($events as $event)
        {
            if($event->isFeatured()){
                return $event;
            }
        }
    }
    
    public function getNextMatch(Range $range, Match\MatchInterface $match)
    {
        $events = $this->getEvents($range);
        //look for match        
    }
    
    public function getEvent($id)
    {
        
    }
    
    public function setHttpClient(\Zend\Http\Client $client)
    {
        $client->setOptions(array('sslverifypeer' => false));
        $this->httpClient = $client;
    }
    
    /**
     * @return \Zend\Http\Client
     */
    public function getHttpClient()
    {
        if(empty($this->httpClient)){
            $this->setHttpClient(new \Zend\Http\Client());
        }
        
        return $this->httpClient;
    }
}