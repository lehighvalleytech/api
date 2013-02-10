<?php
require_once '../vendor/autoload.php';

//markdown parser
$markdown = new \dflydev\markdown\MarkdownParser();

$findCover = function($card){
    $cover = null;
    foreach($card['attachments'] as $attachment){
        if(isset($card['idAttachmentCover']) AND $card['idAttachmentCover'] == $attachment['id']){
            $cover = array(
                'url' => $attachment['url']
            );
        }
    }    
    return $cover;
};

$parseCard = function($card) use ($markdown, $findCover){
    $parsed = array(
        'title' => $card['name'],
        'markdown' => $card['desc'],
        'html' => $markdown->transform($card['desc']),
        'pos' => $card['pos']
    );
    
    if(isset($card['due'])){
        $parsed['date'] = new DateTime($card['due']);
    }    
    
    if($image = $findCover($card)){
        $parsed['image'] = $findCover($card);                   
    }
    
    return $parsed;
};

//special root redirects, allows some fun custom domains
respond('GET', '/', function(_Request $request, _Response $response){
    switch($_SERVER['HTTP_HOST']){ //abstract for testing    
        case 'next.devfriday.com':
            //find the next dev friday
            try{
                $meetup = new \Meetup\Service(getenv('MEETUP_KEY'), getenv('MEETUP_GROUP'));
                //TODO: this would be a good thing to cache
                $event = $meetup->getNextMatch(new \Meetup\Range(new DateTime()), new \Meetup\Match\Simple('Developer Friday'));
                if(!$event){
                    throw new Exception('the world has ended, as the next dev friday could not be found');
                }
                $url = $event->getUrl();
            } catch (Exception $e){
                //TODO: should probably log the error
                $url = 'http://meetup.lehighvalleytech.org/events/61071712/'; //pick the first dev friday in case of zombies
            }
            
            $response->redirect($url);
            break;
        default:
            //should we do this? sure, why not.
            $response->redirect('http://lehighvalleytech.org');
            break;
    }
});

//lvtech meetup TODO: meetup or monthly maybe better names, just avoiding 
//confusion with meetup.com
respond('GET', '/lvtech/[:date]', function (_Request $request, _Response $response) use ($parseCard) {
    //try to find date
    try{
        if(strlen($request->date) != 6){
            throw new InvalidArgumentException('invalid date given');
        }
        
        $date = new DateTime();
        $date->setDate(substr($request->date, 0, 4), substr($request->date, 4), 1);
    } catch (Exception $e) {
        $response->code(404);
        return;
    }

    //find this puppy in trello
    $trello = new \LVTech\Services\Trello\Client(array('key' => getenv('TRELLO_KEY'), 'token' => getenv('TRELLO_TOKEN')));
    
    //for now, just getting an authed HTTP client from the client
    $client = $trello->getClient();
    $boardId = getenv('TRELLO_BOARD');
    $client->setUri($trello->getBaseUrl() . '/boards/'.$boardId.'/lists?filter=all');

    //TODO: should do some error checking
    $data = \Zend\Json\Decoder::decode($client->send()->getBody(), true);
    
    //search for the meetup
    $listId = null;
    foreach($data as $list){
        if(strpos($list['name'], $date->format('F Y')) === 0){ //found it
            //TODO: should totally cache this
            $listId = $list['id'];
        }
    }
    
    //get the list (if we found it)
    if(empty($listId)){
        $response->code(404);
        return;        
    }

    $client->setUri($trello->getBaseUrl() . '/lists/'.$listId.'/cards?attachments=true');

    //TODO: should do some error checking
    $data = \Zend\Json\Decoder::decode($client->send()->getBody(), true);

    //assemble a response
    $meetup = array(
        'links' => array(),
        'presenters' => array(),
        'content' => array()
    );
    
    foreach($data as $card){
        //check for labels - that's pretty much the flag for action
        foreach($card['labels'] as $label){
            switch($label['name']){
                case 'Callout': 
                    //some standard fields
                    $meetup = array_merge($meetup, $parseCard($card));
                    unset($meetup['pos']);
                          
                    //associated links
                    foreach($card['attachments'] as $attachment){
                        if(strpos($attachment['name'], 'meetup.lehighvalleytech.org') !== false){
                            $meetup['links']['meetup'] = $attachment['url'];
                            //TODO: cover image should be checked for
                        }
                    }
                    break;
                case 'Presenter':
                    $presenter = $parseCard($card);
                    $meetup['presenters'][] = $presenter;
                    break;
                case 'Venue':
                    $venue = $parseCard($card);
                    $meetup['venue'] = $venue;
                    break;
                case 'Content':
                    $content = $parseCard($card);
                    $meetup['content'][] = $content;
                    break;
                case 'After':
                    $after = $parseCard($card);
                    $meetup['after'] = $after;
                    break;
            }
        }
    }
    
    //add in meetup details
    if(isset($meetup['links']['meetup'])){
        //look for meetup id
        $parts = explode('/', trim($meetup['links']['meetup'], '/'));
        $meetupId = end($parts);
        //basic meetup info
        $client = new \Zend\Http\Client();
        $client->setOptions(array('sslverifypeer' => false));
        $client->getRequest()->setUri('https://api.meetup.com/2/event/' . $meetupId);
        $client->getRequest()->getQuery()->set('key', getenv('MEETUP_KEY'));
        
        //TODO: error checking might be a good idea
        $data = \Zend\Json\Decoder::decode($client->send()->getBody(), true);
        
        $meetup['count']['meetup.com'] = $meetup['count']['total'] = $data['headcount']?$data['headcount']:$data['yes_rsvp_count'];

        //meetup comments
        $client->getRequest()->setUri('https://api.meetup.com/2/event_comments/')
                             ->getQuery()->set('event_id', $meetupId)
                                         ->set('fields', 'member_photo');
        
        //TODO: error checking might be a good idea
        $data = \Zend\Json\Decoder::decode($client->send()->getBody(), true);
        $meetup['comments'] = array();
        foreach($data['results'] as $comment){
            $comment = array(
                'date' => new DateTime('@'.$comment['time']/1000),
                'text' => $comment['comment'],
                'member' => array(
                    'image' => array('url' => $comment['member_photo']['photo_link']),
                	'name' => $comment['member_name'])
            );
            $meetup['comments'][] = $comment;
        }
        
        //meetup photos
        $client->getRequest()->setUri('https://api.meetup.com/2/photos/')
                             ->getQuery()->set('event_id', $meetupId);
        
        //TODO: error checking might be a good idea
        $data = \Zend\Json\Decoder::decode($client->send()->getBody(), true);

        $meetup['photos'] = array();
        foreach($data['results'] as $photo){
            $photo = array(
                'date' => new DateTime('@'.$photo['created']/1000),
                'image' => array('url' => $photo['highres_link']),
                'member' => array(
                    'image' => array('url' => $photo['member_photo']['photo_link']),
                	'name' => $photo['member']['name'])
            );
            $meetup['photos'][] = $photo;
        }
        
        //meetup rsvps
        $client->getRequest()->setUri('https://api.meetup.com/2/rsvps/')
                             ->getQuery()->set('event_id', $meetupId)
                                         ->set('fields', 'other_services');
        
        //TODO: error checking might be a good idea
        $data = \Zend\Json\Decoder::decode($client->send()->getBody(), true);        
        
        $meetup['members'] = array();
        foreach($data['results'] as $rsvp){
            if('yes' != $rsvp['response']){
                continue;
            }
            
            $member = array(
                'date' => new DateTime('@'.$rsvp['created']/1000),
                'text' => isset($rsvp['comments'])?$rsvp['comments']:'',
                'count' => $rsvp['guests']+1,
                'member' => array(
                    'image' => array('url' => $rsvp['member_photo']['photo_link']),
                	'name' => $rsvp['member']['name'])
            );
            
            $meetup['members'][] = $member;
        }
    }

    
    //transform dates
    array_walk_recursive($meetup, function(&$item, $key){
        if('date' == $key AND $item instanceof DateTime){
            $item = $item->format('r');
        }
    });
    
    
    $response->json(array('lvtech' => $meetup));
    return;
});

dispatch();