<?php
namespace LVTech\Radio\Feed;
use LVTech\Services\Soundcloud\Resource\Playlist;
use LVTech\Services\Soundcloud\Resource\Track;
use Zend\Feed\Writer\Feed as FeedWriter;
use Zend\Feed\Writer\Entry as Entry;

/**
 * @author Tim Lytle <tim@timlytle.net>
 */
class LVTech extends \LVTech\Radio\Feed
{
    /**
     * TODO: make this more abstract and build from config file
     * @param FeedWriter $feed
     * @param Playlist $playlist
     */
    protected function customFeed(FeedWriter $feed, Playlist $playlist)
    {
        $this->feed->setTitle('LVTech Radio')
                   ->setLink('http://radio.lehighvalleytech.org/')
                   ->setFeedLink('http://radio.lehighvalleytech.org/feed/lvtech', $this->type)
                   ->setDescription('The LVTech Radio Show - Live the 2nd and 4th Wednesdays at noon. Tech in the Lehigh Valley and beyond, podcast available shortly after show.')
                   ->setDateModified(new \DateTime());
    }

    protected function customTrack(Entry $entry, Track $track){}
}