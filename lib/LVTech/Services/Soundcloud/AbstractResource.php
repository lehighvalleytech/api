<?php
namespace LVTech\Services\Soundcloud;
use Zend\Json\Json;
/**
 * @author Tim Lytle <tim@timlytle.net>
 */
abstract class AbstractResource
{
    /**
     * @var array
     */
    protected $data = array();

    public function __construct($data)
    {
        if(is_string($data)){
            $data = Json::decode($data, Json::TYPE_ARRAY);
        }

        $this->setData($data);
    }

    protected function setData($data)
    {
        $this->data = $data;
    }

    public function get($name)
    {
        if(!isset($this->data[$name])){
            return null;
        }

        $method = 'get' . ucfirst($name);
        if(method_exists($this, $method)){
            return $this->$method();
        }

        return $this->data[$name];
    }

    public function __call($method, $args)
    {
        if(strpos($method, 'get') === 0){
            $method = substr($method, 3);
            return $this->get($method);
        }

        throw new \BadMethodCallException('invalid method: ' . $method);
    }
}