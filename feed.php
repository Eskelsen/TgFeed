<?php

if (empty($argv[1])) {
    exit('Nothing to see here.' . PHP_EOL);
}

define('WEB', __DIR__ . '/');
define('FEED_LAPSE', 60*60*6);

include __DIR__ . '/env.php';
include __DIR__ . '/config.php';
include __DIR__ . '/functions.php';
include __DIR__ . '/FeedParser.php';
include __DIR__ . '/Json.php';
include __DIR__ . '/DeepL.php';

$s = isset($argv[1]) ? $argv[1] : 0;

$feed_lapse = isset($argv[2]) ? $argv[2] : FEED_LAPSE;

$urls[1] = 'https://feeds.bbci.co.uk/news/world/rss.xml';
$urls[2] = 'https://feeds.folha.uol.com.br/mundo/rss091.xml';
$urls[3] = 'https://rss.dw.com/rdf/rss-en-all';
$urls[4] = 'https://www.voanews.com/api/epiqq';
$urls[5] = 'https://pt.globalvoices.org/feed/';
$urls[6] = 'https://www.lemonde.fr/en/middle-east/rss_full.xml';
$urls[7] = 'https://www.theguardian.com/world/rss';
$urls[8] = 'http://rss.cnn.com/rss/edition_world.rss';

$sources[1] = 'BBC Internacional';
$sources[2] = 'Folha de São Paulo';
$sources[3] = 'Deutsch Welle';
$sources[4] = 'Voz da América';
$sources[5] = 'Global Voices';
$sources[6] = 'Le Monde';
$sources[7] = 'The Guardian';
$sources[8] = 'CNN';

$url = $urls[$s] ?? $urls[1];
$source = $sources[$s] ?? 'Fonte desconhecida';

$log = 'Running source ' . $source . ' with a ' . $feed_lapse . '-second time-lapse';

sleep($s);

echo $log . PHP_EOL;

$feed = FeedParser::parse($url);

$keywords = [
    'iran', 'irã', 'tehran', 'teerã',
    'irgc', 'revolutionary guard',
    'israel', 'gaza', 'hamas', 'hezbollah',
    'usa', 'eua', 'middle east', 'oriente médio',
    'war', 'guerra', 'conflict', 'conflito',
    'attack', 'ataque', 'strike',
    'missile', 'drone', 'rocket',
    'military', 'militar',
    'retaliation', 'retaliação',
    'ceasefire', 'cessar-fogo','agreement','acordo','negociações','negotiation',
    'nuclear', 'sanctions', 'sanções',
    'khamenei','ormuz'
];

$items = [];
$links = Json::read(WEB . 'links.json');

foreach ($feed as $item) {

    $feedTitle = (string) $item['feed'];
    $title = (string) $item['title'];
    // $description = strip_tags((string) $item['description']);

    $fullText = $title . ' ' . $description;

    // $matches = findMatches($fullText, $keywords);
    $matches = findMatches($title, $keywords);

    if (count($matches) < 2 || in_array($item['link'],$links)) {
        continue;
    }

    $lapse = time() - $feed_lapse;

    if (strtotime($item['pubDate']) >= $lapse) {
        $links[] =  (string) $item['link'];
        # $msg = str_replace(':','-',$feedTitle) . ': ' . $title . "\n\n" . $description . "\n\n" . 'Link: ' . (string) $item['link'];
        if ($s!=2) {
            if ($translate = DeepL::translate(DEEPL_KEY,$title)) {
                $chars = Json::read(WEB . 'chars.json');
                $len = mb_strlen($title);
                $chars['len'] = empty($chars['len']) ? $len : $len + $chars['len'];
                Json::write(WEB . 'chars.json',$chars);
                $title = $translate;
            }
        }
        $flags = appendFlagsFromCountries($title);
	    $msg = $flags . '<b>'. $source . '</b>: ' . $title;
        $ok = tgmSendMsg(TG_CHAT, $msg, TG_TOKEN);
        file_put_contents(WEB . 'log.txt',json_encode($ok) . "\n", FILE_APPEND);
    }
}

Json::write(WEB . 'links.json',$links);
