<?php
namespace Meetup;
use InvalidArgumentException;
use DateTime;
use DateInterval;

class Range
{
    protected $start;
    protected $end;
    
    public function __construct($start, $end = null)
    {
        //only got an interval - default to now
        if(is_null($end) AND $start instanceof DateInterval){
            $end = $start;
            $start = new DateTime();
        }
        
        //at this point, start should be a date
        if(!($start instanceof DateTime)){
            throw new InvalidArgumentException('expected start to be datetime');
        }
        
        //check if end is an interval or date
        if($end instanceof DateInterval){
            $interval = $end;
            $end = clone $start;
            $end->add($interval);
        }
        
        //end should now be a date or empty
        if(!is_null($end) AND !($end instanceof DateTime)){
            throw new InvalidArgumentException('expected end to be datetime or null');
        }
        
        $this->start = $start;
        $this->end   = $end;
    }
    
    public function getStart()
    {
        return $this->start;
    }
    
    public function getEnd()
    {
        return $this->end;
    }
    
    public function hasEnd()
    {
        return !is_null($this->end);
    }
}