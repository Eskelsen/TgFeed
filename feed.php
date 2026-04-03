<?php

define('FEED_LAPSE', 60);

include __DIR__ . '/config.php';
include __DIR__ . '/env.php';
include __DIR__ . '/functions.php';

$s = isset($argv[1]) ? $argv[1] : 0;

$feed_lapse = isset($argv[2]) ? $argv[2] : FEED_LAPSE;

$urls[1] = 'https://feeds.bbci.co.uk/news/world/rss.xml';
$urls[2] = 'https://feeds.folha.uol.com.br/mundo/rss091.xml';

$url = $urls[$s] ?? $urls[1];

echo 'Running source ' . $s . ' with a ' . $feed_lapse '-second time-lapse' . PHP_EOL; 

$xmlString = file_get_contents($url);

if ($xmlString === false) {
    die('Erro ao buscar RSS');
}

$xml = simplexml_load_string($xmlString);

$coreKeywords = [
    'iran', 'irã', 'tehran', 'teerã',
    'irgc', 'revolutionary guard',
    'israel', 'gaza', 'hamas', 'hezbollah',
    'usa', 'eua', 'middle east', 'oriente médio'
];

$actionKeywords = [
    'war', 'guerra', 'conflict', 'conflito',
    'attack', 'ataque', 'strike',
    'missile', 'drone', 'rocket',
    'military', 'militar',
    'retaliation', 'retaliação',
    'ceasefire', 'cessar-fogo',
    'nuclear', 'sanctions', 'sanções'
];

$items = [];

foreach ($xml->channel->item as $item) {

    $title = (string) $item->title;
    $description = strip_tags((string) $item->description);

    $fullText = $title . ' ' . $description;

    $coreMatches = findMatches($fullText, $coreKeywords);
    $actionMatches = findMatches($fullText, $actionKeywords);

    $totalMatches = count($coreMatches) + count($actionMatches);

    if (count($coreMatches) < 1 || count($actionMatches) < 1 || $totalMatches < 2) {
        continue;
    }

    $when = date('Y-m-d H:i:s', strtotime($item->pubDate));

    $lapse = time() - $feed_lapse;

    if (strtotime($item->pubDate) >= $lapse) {
        $msg = $title . "\n\n" . $description . "\n\n" . 'Link: ' . (string) $item->link;
        $ok = tgmSendMsg(TG_CHAT, $msg, TG_TOKEN);
        file_put_contents('log.txt',json_encode($ok) . "\n", FILE_APPEND);
    }

    // $items[] = [
    //     'title'       => $title,
    //     'link'        => (string) $item->link,
    //     'description' => $description,
    //     'pubDate'     => date('Y-m-d H:i:s', strtotime($item->pubDate)),
    //     'matches'     => $totalMatches,
    //     'core'        => $coreMatches,
    //     'action'      => $actionMatches
    // ];
}

// echo '<pre>';
// print_r($items);
