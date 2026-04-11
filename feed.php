<?php

if (empty($argv[1])) {
    exit('Nada para ver aqui.' . PHP_EOL);
}

define('WEB', __DIR__ . '/');
define('FEED_LAPSE', 60*60*6);

if (!is_file(WEB . 'sources.csv') OR !is_file(WEB . 'keywords.csv')) {
    echo 'Voce deve configurar os arquivos de fontes (sources.csv) e palavras-chaves (keywords.csv) de acordo com os arquivos lock' . PHP_EOL;
    echo 'Para cada fonte, configure um cron' . PHP_EOL;
    echo 'Exemplo para a fonte 1: * * * * * /usr/bin/php /caminho-para/tg-feed/feed.php 1 > /dev/null 2>&1' . PHP_EOL;
    echo 'Exemplo para a fonte 2: * * * * * /usr/bin/php /caminho-para/tg-feed/feed.php 2 > /dev/null 2>&1' . PHP_EOL;
    echo 'E assim por diante' . PHP_EOL;
    return;
}

include WEB . 'env.php';
include WEB . 'config.php';
include WEB . 'functions.php';
include WEB . 'FeedParser.php';
include WEB . 'Json.php';
include WEB . 'DeepL.php';

$s = isset($argv[1]) ? $argv[1] : 0;

$feed_lapse = isset($argv[2]) ? $argv[2] : FEED_LAPSE;

$sources = getData(WEB . 'sources.csv');

$source = $sources[$s] ?? $sources[1];

if (empty($source[1])) {
    exit('Falha no carregamento da fonte');
}

[$label,$url] = $source;

$log = 'Running source ' . $label . ' with a ' . $feed_lapse . '-second time-lapse';

sleep($s);

echo $log . PHP_EOL;

$feed = FeedParser::parse($url);

$keywords = getData(WEB . 'keywords.csv');

$items = [];
$links = Json::read(WEB . 'links.json');

foreach ($feed as $item) {

    $feedTitle = (string) $item['feed'];
    $title = (string) $item['title'];

    $matches = findMatches($title, $keywords);

    if (count($matches) < 2 || in_array($item['link'],$links)) {
	echo 'Nada para lançar. Matches: ' . count($matches) . ' :: ' . json_encode($matches) .PHP_EOL;
        continue;
    }

    $lapse = time() - $feed_lapse;

    if (strtotime($item['pubDate']) >= $lapse) {
        $links[] =  (string) $item['link'];
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
	    $msg = $flags . '<b>'. $label . '</b>: ' . $title;
        $ok = tgmSendMsg(TG_CHAT, $msg, TG_TOKEN);
        file_put_contents(WEB . 'log.txt',json_encode($ok) . "\n", FILE_APPEND);
    }
}

Json::write(WEB . 'links.json',$links);
