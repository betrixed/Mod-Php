<?php

/*
See the "licence.txt" file at the root "private" folder of this site
*/
namespace Pcan\Google;

class Captcha {
    
    private $privateKey;
    
    /** construct with private key */
    function __construct($privateKey)
    {
        $this->privateKey = $privateKey;
    }
    /**
      check request google captcha success.
    */
    public function checkRequest($request)
    {
        $forgoogle = $request->getPost('g-recaptcha-response');
        

        $google_url = "https://www.google.com/recaptcha/api/siteverify";

        $data = ["secret" => $this->privateKey, "response" => $forgoogle ];

        $options = [ 'http' => [
                'header' => 'Content-type: application/x-www-form-urlencoded\r\n',
                'method' => 'POST',
                'content' => http_build_query($data),
            ]];
        $context = stream_context_create($options);
        $result = file_get_contents($google_url, false, $context);

        $j = json_decode($result);
        return $j->success;
    }
}

