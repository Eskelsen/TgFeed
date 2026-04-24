<?php

class FeedParser
{
    public static function parse(string $url, ?string $source = null): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,

            CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120 Safari/537.36',

            CURLOPT_HTTPHEADER => [
                'Accept: application/rss+xml,application/xml;q=0.9,text/xml;q=0.8,*/*;q=0.7',
                'Accept-Language: en-US,en;q=0.9',
            ],
        ]);

        $data = curl_exec($ch);

        if ($data === false) {
            file_put_contents(__DIR__ . '/log.txt','Falha no cURL: ' . $url . "\n" . curl_error($ch) . "\n", FILE_APPEND);
            return [];
        }

        $info = curl_getinfo($ch);
        curl_close($ch);

        if (stripos($data, '<rss') === false && stripos($data, '<feed') === false) {
            file_put_contents(__DIR__ . '/log.txt', 'Não é um RSS: ' . $url . ' [' . ($info['content_type'] ?? '') . "]\n",FILE_APPEND);
            return [];
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);

        if (!$xml) {
            file_put_contents(__DIR__ . '/log.txt', 'Falha ao parsear XML: ' . $url . "\n", FILE_APPEND);
            return [];
        }

        $feedTitle = self::getFeedTitle($xml);
        $sourceName = $source ?? self::detectSource($url, $feedTitle);

        $items = [];
        $feedItems = self::getFeedItems($xml);

        foreach ($feedItems as $item) {

            $title = self::cleanText($item->title ?? '');
            $description = self::cleanText($item->description ?? $item->summary ?? '');

            $link = (string) ($item->link ?? '');
            if (!$link && isset($item->link['href'])) {
                $link = (string) $item->link['href'];
            }

            $rawDate = self::extractDate($item);
            $date = $rawDate ? date('Y-m-d H:i:s', strtotime($rawDate)) : null;

            $items[] = [
                'source'      => $sourceName,
                'feed'        => $feedTitle,
                'title'       => $title,
                'description' => $description,
                'link'        => $link,
                'pubDate'     => $date,
                'rawDate'     => $rawDate,
            ];
        }

        return $items;
    }

    private static function getFeedItems($xml)
    {
        if (isset($xml->channel->item)) {
            return $xml->channel->item; # RSS
        }

        if (isset($xml->item)) {
            return $xml->item; # RDF
        }

        if (isset($xml->entry)) {
            return $xml->entry; # Atom
        }

        return [];
    }

    private static function extractDate($item)
    {
        if (!empty($item->pubDate)) return (string) $item->pubDate;
        if (!empty($item->date)) return (string) $item->date;
        if (!empty($item->published)) return (string) $item->published;

        $namespaces = $item->getNamespaces(true);

        if (isset($namespaces['dc'])) {
            $dc = $item->children($namespaces['dc']);
            if (!empty($dc->date)) {
                return (string) $dc->date;
            }
        }

        return null;
    }

    private static function getFeedTitle($xml)
    {
        if (!empty($xml->channel->title)) {
            return (string) $xml->channel->title;
        }

        if (!empty($xml->title)) {
            return (string) $xml->title;
        }

        return null;
    }

    private static function detectSource($url, $feedTitle = null)
    {
        if ($feedTitle) {
            return trim($feedTitle);
        }

        $host = parse_url($url, PHP_URL_HOST);

        $map = [
            'bbc' => 'BBC',
            'reuters' => 'Reuters',
            'dw' => 'DW',
            'cnn' => 'CNN'
        ];

        foreach ($map as $needle => $name) {
            if (strpos($host, $needle) !== false) {
                return $name;
            }
        }
	    return $host;
    }

    private static function cleanText($text)
    {
        $text = (string) $text;
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        return trim($text);
    }
}
