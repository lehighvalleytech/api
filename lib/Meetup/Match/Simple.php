<?php
namespace Meetup\Match;
class Simple implements MatchInterface
{
    protected $match;
    
    public function __construct($match)
    {
        $this->match = (string) $match;
    }
    
    public function match($query)
    {
        $query = (string) $query;
        return !(stripos($query, $this->match) === false);
    }
}