<?php
namespace LVTech\Radio;
use LVTech\Services\Soundcloud\Resource\Playlist;
use LVTech\Services\Soundcloud\Resource\Track;
use Zend\Feed\Writer\Feed as FeedWriter;
use Zend\Feed\Writer\Entry as Entry;
/**
 * Generate a podcast feed from a soundcloud playlist.
 * @author Tim Lytle <tim@timlytle.net>
 */
abstract class Feed //implements \JsonSerializable
{
    protected $type = 'atom';

    /**
     * @var FeedWriter
     */
    protected $feed;

    /**
     * @var Playlist
     */
    protected $playlist;

    protected $author = array(
        'email' => 'radio@lehighvalleytech.org',
        'name'  => 'LVTech Radio',
        'uri'   => 'http://radio.lehighvalleytech.org/'
    );

    protected $categories = array(
        'Technology' => array('Tech News', 'Gadgets')
    );

    public function __construct(Playlist $playlist, $format = null)
    {
        if(!is_null($format)){
            $this->type = $format;
        }

        $this->playlist = $playlist;
    }

    abstract protected function customFeed(FeedWriter $feed, Playlist $playlist);
    abstract protected function customTrack(Entry $entry, Track $track);

    protected function setupFeed(FeedWriter $feed, Playlist $playlist)
    {
        $feed->addAuthor($this->author);
        $feed->setItunesExplicit('no')
             ->setItunesCategories($this->categories);

        $this->customFeed($feed, $playlist);
    }

    protected function setupTrack(Entry $entry, Track $track)
    {
        $entry->setTitle($track->get('title'))
            ->setLink($track->get('permalink_url'))
            ->setEnclosure(array('type' => 'audio/mpeg', 'uri' => $track->getPlayLink(), 'length' => $track->get('original_content_size')))
            ->setDescription($track->get('description'))
            ->setDateCreated($track->getReleaseDate())
            ->setDateModified($track->getReleaseDate()) //TODO: find a way to bump if audio file changes
            ->addAuthor($this->author) //TODO: add authors based on hosts/guests
            ->setItunesExplicit('no');


        $this->customTrack($entry, $track);
    }

    /**
     * @return FeedWriter
     */
    public function getFeedWriter()
    {

        if(!empty($this->feed)){
            return $this->feed;
        }

        $this->feed = new FeedWriter();
        $this->setupFeed($this->feed, $this->playlist);

        //sort tracks by date (TODO: do this in the playlist?)
        $tracks = $this->playlist->getTracks();
        usort($tracks, function(Track $a, Track $b){
            return($a->getReleaseDate() < $b->getReleaseDate());
        });

        foreach($tracks as $track){
            $entry = $this->feed->createEntry();
            $this->setupTrack($entry, $track);
            $this->feed->addEntry($entry);
        }

        return $this->feed;
    }

    public function getFeed()
    {
        //TODO: implement caching
        return $this->getFeedWriter()->export($this->type);
    }

    public function __toString()
    {
        return $this->getFeed();
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        $feed = $this->getFeedWriter();
        $json = array();
        $json['title'] = $feed->getDescription();
        $json['tracks'] = array();
        foreach($feed as $entry){ /*@var $entry Entry */
            $json['tracks'][] = array(
                'title' => $entry->getTitle(),
                'description' => $entry->getDescription(),
                'date' => $entry->getDateCreated(),
                'url' => $entry->getLink()
            );
        }

        return $json;
    }


}