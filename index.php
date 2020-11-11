<?php
// print_r($_POST[site])
if (!empty($_POST)){//получили не пустой пост идем его обрабатывать
    $curl = curl_init();
    
    curl_setopt_array($curl, array(//фармируем POST используя функцию curl ,CURLOPT_URL - наш сервис с гугл апи для проверки безопасности страницы  CURLOPT_POSTFIELDS наш обект с параметрами для передачи даных в гугл апи
    CURLOPT_URL => "https://safebrowsing.googleapis.com/v4/threatMatches:find?key=API_KEY",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => '{"client": {
        "clientId":      "yourcompanyname",
        "clientVersion": "1.5.2"
        },
        "threatInfo": {
        "threatTypes":      ["MALWARE", "SOCIAL_ENGINEERING","THREAT_TYPE_UNSPECIFIED","UNWANTED_SOFTWARE","POTENTIALLY_HARMFUL_APPLICATION"],
        "platformTypes":    ["PLATFORM_TYPE_UNSPECIFIED","WINDOWS","LINUX","ANDROID","OSX","IOS","ANY_PLATFORM","ALL_PLATFORMS","CHROME"],
        "threatEntryTypes": ["URL"],
        "threatEntries":    [{"url": "'.$_POST[site].'" }]
        }
    }',
    CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache",
        "content-type: application/json"
    ),
    ));

    $response = curl_exec($curl);//ответ от апи
    $err = curl_error($curl);//в случае ошибки нашего запроса

    curl_close($curl);//закрываем сессию нашего курла

    if ($err) {//если получили ошибку
    echo "cURL Error #:" . $err;
    } else {//в противном случае отправит ответ на нашу страничку
    echo $response;
    }
}else {//получили пустой запрос вывели ошибку
    echo "POST data NULL";
}
?>