<?php
use Zend\Mvc\Router\Http\Segment as SegmentRoute;

/**
 * @author Tim Lytle <tim@timlytle.net>
 */
require_once '../vendor/autoload.php';

/**
 * Podcast Feeds
 */
respond('GET', '/feed/[lvtech|developers:name].[rss|atom|json:format]?', function(_Request $request, _Response $response){
    $name   = $request->name;
    $format = $request->format;
    if(empty($format)){
        $format = 'atom'; //default
    }

    //TODO: move to config
    switch($name){
        case 'lvtech':
            $playlist = '8260212';
            $class = '\LVTech\Radio\Feed\LVTech';
            break;
        case 'developers';
            $playlist = '3894559';
            $class = '\LVTech\Radio\Feed\Developers';
            break;
        default:
            $app->halt('invalid feed');
    }

    $soundcloud = new LVTech\Services\Soundcloud\Client(getenv('SOUNDCLOUD_KEY'));
    $playlist = $soundcloud->getPlaylist($playlist);

    switch($format){
        case 'rss':
        case 'atom':
            $feed = new $class($playlist, $format);
            echo $feed;
            break;
        case 'json':
            $feed = new $class($playlist); //TODO: odd usage to pull the rss data and push into json, feels wrong
            echo json_encode($feed->jsonSerialize());
            break;
    }
});

/**
 * Live Stream / Status
 */
respond('GET', '/live.[json|:format]?', function(_Request $request, _Response $response){
    $format = $request->format;
    $stream = new LVTech\Radio\Stream();

    //request for stream status
    if('json' == $format){
        echo json_encode(array('online' => $stream->isOnline()));
        return;
    }

    //request for live stream, try to give the device what it wants
    if(strpos($request->userAgent(), 'Android')){
        $response->redirect($stream->getMP3());
    } else {
        $response->redirect($stream->getM3u());
    }

});

/**
 * Main Site
 */
respond('GET', '/', function(_Request $request, _Response $response){
    $response->redirect('http://soundcloud.com/lvtech');
});

dispatch();