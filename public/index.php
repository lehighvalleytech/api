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


respond('GET', '/meetup/[:date]', function (_Request $request, _Response $response) use ($parseCard) {
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
    $data = json_decode($client->send()->getBody(), true);
    
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
    $data = json_decode($client->send()->getBody(), true);

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
                            $meetup['links']['meetup.com'] = $attachment['url'];
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
    
    //transform dates
    array_walk_recursive($meetup, function(&$item, $key){
        if('date' == $key AND $item instanceof DateTime){
            $item = $item->format('r');
        }
    });

    $response->json($meetup);
    return;
});

dispatch();