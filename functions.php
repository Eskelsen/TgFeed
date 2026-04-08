<?php

function normalize($text) {
    $text = mb_strtolower($text, 'UTF-8');
    return iconv('UTF-8', 'ASCII//TRANSLIT', $text);
}

function findMatches($text, $keywords) {
    $text = normalize($text);
    $found = [];

    foreach ($keywords as $keyword) {
        $keywordNorm = normalize($keyword);

        if (strpos($text, $keywordNorm) !== false) {
            $found[$keywordNorm] = true;
        }
    }

    return array_keys($found);
}

function tgmSendMsg($chat_id, $msg, $tkn){
	$url = TG_URLBASE . $tkn . '/sendMessage';
	$data = [
		'chat_id' => $chat_id,
		'text' 	  => $msg
	];
	$raw = dispatcher($url, $data, 'POST', true);
	echo $raw . PHP_EOL;
    $data = json_decode($raw, 1);
	return $data['result'] ?? false;
}

function countryCodeToFlag(string $code){
    return mb_chr(127397 + ord($code[0]), 'UTF-8')
         . mb_chr(127397 + ord($code[1]), 'UTF-8');
}

function appendFlagsFromCountries(string $text){
    $countries = [
        'BR' => ['Brasil', 'Brazil'],
        'US' => ['Estados Unidos', 'United States'],
        'ES' => ['Espanha', 'Spain', 'España'],
        'DE' => ['Alemanha', 'Germany', 'Alemania'],
        'FR' => ['França', 'France', 'Francia'],
        'IT' => ['Itália', 'Italy', 'Italia'],
        'PT' => ['Portugal'],
        'AR' => ['Argentina'],
        'MX' => ['México', 'Mexico'],
        'IR' => ['Irã', 'Iran', 'Irán'],
        'IL' => ['Israel'],
        'OM' => ['Omã', 'Oman', 'Omán'],
        'BH' => ['Bahrein', 'Bahrain', 'Baréin'],
        'QA' => ['Catar', 'Qatar'],
        'SA' => ['Arábia Saudita', 'Saudi Arabia', 'Arabia Saudita','KSA'],
        'IQ' => ['Iraque', 'Iraq'],
        'JO' => ['Jordânia', 'Jordan', 'Jordania'],
        'PK' => ['Paquistão', 'Pakistan', 'Pakistán'],
        'SY' => ['Síria', 'Syria', 'Siria'],
        'LB' => ['Líbano', 'Lebanon', 'Líbano'],
        'AE' => ['Emirados Árabes Unidos', 'United Arab Emirates', 'Emiratos Árabes Unidos', 'UAE'],
        'KW' => ['Kuwait']
    ];

    $flags = [];

    foreach ($countries as $code => $names) {
        foreach ($names as $name) {
            if (preg_match('/\b' . preg_quote($name, '/') . '\b/iu', $text)) {
                $flags[$code] = countryCodeToFlag($code);
                break;
            }
        }
    }

    return $flags ? implode('', $flags) . ' ' . $text : $text;
}

function dispatcher($url, $data = '', $method = 'GET', $type = true){
	
    $curl = curl_init();
	$data = is_string($data) ? $data : json_encode($data);
	
	$application = ($type) ? 'application/json' : 'application/x-www-form-urlencoded; charset=utf-8';
	
	$headers = [
		'Content-Type: ' 		 . $application,
		'Content-Length: ' 		 . strlen($data)
	];
	
    curl_setopt_array($curl, [
        CURLOPT_URL             => $url,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_ENCODING        => '',
        CURLOPT_MAXREDIRS       => 10,
        CURLOPT_TIMEOUT         => 30,
        CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST   => $method,
        CURLOPT_POSTFIELDS      => $data,
        CURLOPT_HTTPHEADER      => $headers
    ]);
	
    $response = curl_exec($curl);

    $e = curl_error($curl);
	
    curl_close($curl);
	
	if ($e) {
		exit(json_encode(['status' => false, 'message' => $e]));
	}
	
    return $response;
}
