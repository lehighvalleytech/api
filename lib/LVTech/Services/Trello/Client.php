<?php
namespace LVTech\Services\Trello;

class Client
{
    /**
     * @var \Zend\Http\Client
     */
    protected $client;
    
    protected $config = array(
        'base' => 'https://api.trello.com/1'
    );
    
    const API_BOARD = 'boards';
    
    public function __construct($client = null, $config = null)
    {
        if(is_null($config) AND is_array($client)){
            $config = $client;
            $client = null;
        }
        
        if(!is_null($client)){
            $this->setClient($client);
        }
        
        if(is_array($config)){
            $this->config = array_merge($this->config, $config);
        }
    }
    
    /**
     * @return \Zend\Http\Client()
     */
    public function getClient()
    {
        if(empty($this->client)){
            $this->setClient(new \Zend\Http\Client());
        }
        
        if(isset($this->config['key'])){
            $this->client->getRequest()->getQuery()->set('key', $this->config['key']);
        }
        
        if(isset($this->config['token'])){
            $this->client->getRequest()->getQuery()->set('token', $this->config['token']);
        }
        
        $this->client->setOptions(array('sslverifypeer' => false));
        
        return $this->client;
    }
    
    public function setClient(\Zend\Http\Client $client)
    {
        $this->client = $client;
    }
    
    public function save($entity)
    {
        
    }
    
    public function getBaseUrl()
    {
        return $this->config['base'];
    }
    
    public function getBoard($id, $endpoint = self::API_BOARD)
    {
        $client = $this->getClient();
        $client->getRequest()->setUri(implode('/', array($this->config['base'], $endpoint, $id)));

        $response = $client->send();
        var_dump($response->getBody());
    }
    
}