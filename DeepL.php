<?php

class DeepL{
    public static function translate($auth_key,$text)
    {
        $data['text'] = [$text];
        $data['target_lang'] = 'PT-BR';

        $response = self::dispatcher($auth_key,$data);

        if (!$response OR empty($response['translations'][0]['text'])) {
            return $text;
        }

        return $response['translations'][0]['text'];
    }

    private static function dispatcher($auth_key,$data){
        
        $curl = curl_init();
        $data = is_string($data) ? $data : json_encode($data);
        
        $headers = [
            'Authorization: DeepL-Auth-Key ' 		 . $auth_key,
            'Content-Type: ' 		 . 'application/json',
            'Content-Length: ' 		 . strlen($data)
        ];
        
        curl_setopt_array($curl, [
            CURLOPT_URL             => 'https://api-free.deepl.com/v2/translate',
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => '',
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => 'POST',
            CURLOPT_POSTFIELDS      => $data,
            CURLOPT_HTTPHEADER      => $headers
        ]);
        
        $response = curl_exec($curl);

        error_log('DeepL API response: ' . $response);

        $e = curl_error($curl);
        
        curl_close($curl);
        
        if ($e) {
            error_log(json_encode(['status' => false, 'message' => $e, 'response' => $response]));
            return false;
        }
        
        return json_decode($response, 1);
    }
}
