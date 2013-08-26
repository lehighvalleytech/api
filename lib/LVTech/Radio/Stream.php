<?php
namespace LVTech\Radio;
/**
 * @author Tim Lytle <tim@timlytle.net>
 */
class Stream
{
    protected $url;

    public function __construct($url = 'http://a2sw.bytecost.com:8000')
    {
        $this->url = $url;
    }

    public function isOnline($mount = 'lvtechradio')
    {
        $client = new \Zend\Http\Client();
        $client->setUri($this->url . '/?mount=/lvtechradio');
        $result = $client->send();
        return strpos($result->getBody(), 'Mount Point /lvtechradio');
    }

    public function getM3U($mount = 'lvtechradio')
    {
        return $this->url . '/' . $mount . '.m3u';
    }

    public function getMP3($mount = 'lvtechradio')
    {
        return $this->url . '/' . $mount;
    }

}