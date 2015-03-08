<?php
require_once '../vendor/autoload.php';

$client = new GuzzleHttp\Client(['base_url' => 'https://www.eventbriteapi.com']);

//get the attendees
$request = $client->createRequest('GET', '/v3/events/5749663398/attendees/');
$request->addHeader('Authorization', 'Bearer ' . getenv('EVENTBRITE_KEY'));
$request->getQuery()->set('status', 'attending');

$response = $client->send($request);

if($response->getStatusCode() != 200){
    return;
}

$data = $response->json();
$attendees = $data['attendees'];
//cycle through pages
$page = 1;
while($page < $data['pagination']['page_count']){
    $request->getQuery()->set('page', ++$page);
    $response = $client->send($request);
    $data = $response->json();
    $attendees = array_merge($attendees, $data['attendees']);
}

//create price list
$prices = [];
foreach($attendees as $ticket){
    $cost = md5(serialize($ticket['costs']));
    if(!isset($prices[$cost])){
        $prices[$cost] = [
            'costs' => $ticket['costs'],
            'tickets' => []
        ];
    }
    $prices[$cost]['tickets'][] = $ticket;
}

foreach($prices as $price => $data){
    switch($data['costs']['gross']['value']){
        case '1500':
            $prices[$price]['label'] = 'Berks Student';
            $prices[$price]['type'] = 'ticket';
            break;
        case '2500':
            $prices[$price]['label'] = 'Student';
            $prices[$price]['type'] = 'ticket';
            break;
        case '3500':
            $prices[$price]['label'] = 'Hacker';
            $prices[$price]['type'] = 'ticket';
            break;
        case '4500':
            $prices[$price]['label'] = 'Late';
            $prices[$price]['type'] = 'ticket';
            break;

        case '50995':
            $prices[$price]['label'] = 'Platform';
            $prices[$price]['type'] = 'sponsor';
            break;
        case '100995':
            $prices[$price]['label'] = 'Promoter';
            $prices[$price]['type'] = 'sponsor';
            break;

        case '49500':
            $prices[$price]['label'] = 'KU Block';
            $prices[$price]['type'] = 'block';
            $prices[$price]['count'] = 0;
            break;

        default:
            $prices[$price]['label'] = 'Unknown';
            $prices[$price]['type'] = 'ticket';
    }
}

//calc the prices
$rows = [
    'sponsor' => [],
    'ticket'  => [],
];
foreach($prices as $price => $data){
//var_dump($data['costs']);
    $row = [
        'label' => $data['label'],
        'gross' => $data['costs']['gross']['display'],
        'fee' => $data['costs']['eventbrite_fee']['display'],
        //'paypal' => ceil($data['costs']['gross']['value']*.029)+30,
        'sold' => count($data['tickets'])
    ];

    if('block' == $data['type']){
        $row['count'] = $data['count'];
    } elseif('sponsor' == $data['type']) {
        $row['count'] = 0;
    } else {
        $row['count'] = $row['sold'];
    }

    $rows[$data['type']][] = $row;
}

//output csv
header('Content-Type: application/csv');
$fh = fopen('php://output', 'w');
foreach($rows as $type => $data){
    foreach($data as $row){
        fputcsv($fh, array_merge([$type], $row));
    }
}

//var_dump($rows);