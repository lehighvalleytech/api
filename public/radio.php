<?php
use Phlyty\App;
use Zend\Mvc\Router\Http\Segment as SegmentRoute;

/**
 * @author Tim Lytle <tim@timlytle.net>
 */
require_once '../vendor/autoload.php';

$app = new App();

/**
 * Podcast Feeds
 */

$route = SegmentRoute::factory(array(
    'route' => '/feed/:name[.:format]',
    'constraints' => array(
        'name'   => '(lvtech|developers)',
        'format' => '(rss|atom|json)',
    ),
    'defaults' => array(
        'format' => 'atom',
    ),
));

$app->get($route, function (App $app) {
    $name   = $app->params()->getParam('name');
    $format = $app->params()->getParam('format');

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

})->name('feed');

/**
 * Live Stream / Status
 */
$route = SegmentRoute::factory(array(
    'route' => '/live[.:format]',
    'constraints' => array(
        'format' => '(json|)',
    ),
    'defaults' => array(
        'format' => '',
    ),
));

$app->get($route, function (App $app) {
    $format = $app->params()->getParam('format');
    $stream = new LVTech\Radio\Stream();

    //request for stream status
    if('json' == $format){
        echo json_encode(array('online' => $stream->isOnline()));
        return;
    }

    //request for live stream, try to give the device what it wants
    $agent = $app->request()->getHeaders()->get('User-Agent');

    if(strpos($agent->getFieldValue(), 'Android')){
        $app->redirect($stream->getMP3());
    } else {
        $app->redirect($stream->getM3u());
    }

})->name('live');

/**
 * Main Site
 */
$app->get('/', function (App $app) {
   $app->redirect('http://soundcloud.com/lvtech');
});

$app->run();
