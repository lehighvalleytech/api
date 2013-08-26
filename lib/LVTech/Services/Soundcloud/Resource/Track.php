<?php
namespace LVTech\Services\Soundcloud\Resource;
use LVTech\Services\Soundcloud\AbstractResource;

/**
 * Wrapper for a SoundCloud Track
 * @author Tim Lytle <tim@timlytle.net>
 */
class Track extends AbstractResource
{
    /**
     * @return DateTime
     */
    public function getReleaseDate()
    {
        if(!isset($this->data['release_day'])){
            return new \DateTime($this->data['created_at']);
        }

        return new \DateTime(implode('-', array(
            $this->data['release_year'],
            $this->data['release_month'],
            $this->data['release_day']
        )), new \DateTimeZone('America/New_York'));
    }

    public function getPlayLink()
    {
        return 'http://feeds.soundcloud.com/stream/' . $this->get('id') . '.mp3';
    }
}