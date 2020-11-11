# websec

Работа с сервисом #websec

Сервис построен на двух файлах index.html и index.php

Первый файл является view нашего сайта а также javascript app. Какие библиотеки использованы:
jquery
axeos
bootstrap 4
Библиотеки подключены cdn способом так как находится в корне *.html файла. Структура try помогает нам отловить первый exception на не валидный url. Внутри нашего try мы создаем экземпляр класса URL с передачей в конструктор нашего url из input в нашем view. Дальше мы делаем простую проверку на проверку вышивания строки с исполняемым кодом в POST на проверяемом сайте
let xss = `${site}<script>parent.postMessage(JSON.stringify({key: 'value'}), '*');<\/script>`;
Мы используем уязвимость xss для передачи исполняемого кода на страницу
postMessage(JSON.stringify({key: 'value'})

Дает нам кроссплатформенный доступ к сайту и мы можем управлять тем что происходит на вью пользователя и за одно передавать нам месседжи с например кешем или семейными данными пользователя.

Мы эмулируем среду пользователя в нашем iframe выталкивая его в наше view.
let iframe = document.createElement("iframe")
iframe.setAttribute('src',xss)
document.body.appendChild(iframe)

Дальше с помощью слушателя событий отлавливаем кроссплатформенный месседж от нашего атакуемого сайта.
function handlerMessage(e){
     var data = JSON.parse(e.data);
     console.log(e)
     var origin = e.origin;
     XSS.textContent = "XSS уязвимость обнаружена"
   }
   if(window.addEventListener){
     window.addEventListener('message', handlerMessage);
   } else {
     window.attachEvent('message', handlerMessage);
   }

Цыпляем хендлер види функции в наш евент листнер. Если атака произойдет то сайт из iframe отправит нам сообщение и установит контакт между нами и нашим пользователем который потенциально подвержен атаке. Еслим это не произошло то пишим что сайт защищен от xss угроз.


XSS.textContent = "XSS уязвимость не обнаружена"


Теперь давайте разберемся с тем как использовано Google api по проверки на опасные сайты.

index.php реагирует на POST запрос из нашего view который передает переменную с строкой нашего url
axios({
           method: 'POST',
           withCredentials: true,
           headers: {'Content-Type': 'application/x-www-form-urlencoded'},
           url: 'http://xss.mnml.pp.ua/index.php',//адресс нашего сервера
           data: `site=${site}`//адрес проверяемого сайта
         })


Дальше нам нужно реализовать правильный json для запроса данных из database Google API. Передаем нашу строку с url (API_KEY - это кей полученный из гугл консоли)
   curl_setopt_array($curl, array(
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



Дальше обработка нашего response от гугла. На этом этапе можно создать свою базу данных которая просто будет обновляться что сократит затраты на Google API.

   $response = curl_exec($curl);
   $err = curl_error($curl);
 
   curl_close($curl);
 
   if ($err) {
   echo "cURL Error #:" . $err;
   } else {
   echo $response;
   }

Тут тоже работаем с exception и да конечно же отправляем обратно ответ на POST из нашего view.

.then(function (response) {
           var data = response.data.matches;
           console.table(data)
 
           let _td = document.querySelectorAll('td')
           for (let i = 0; i < _td.length; i++) {
             const element = _td[i];
             element.textContent = "NO"
           }
           for (let i = 0; i < data.length; i++) {
             const element = data[i];
            
             let tr = document.getElementById(element.platformType)
             let td = tr.getElementsByClassName(element.threatType)
             td[0].textContent = "YES"
           }
         })
         .catch(function (error) {
           console.log(error)//в случае ошибки нашего пост запроса на сервер выводятся в консоле
         });

Дальше в нашем index.html мы обработаем данные чтобы выплюнуть в наше view.
У нас есть table на view, и нам нужно сначало зачистить все ячейки прописам в них “NO” а в следующем for, мы прописываем только в нужные ячейки, ответ с найденной уязвимостью - “YES”.

Так как нам может быть внесен не валидный url или может не быть никаких уязвимостей мы обработаем view в соответст

} catch (error) {
         XSS.textContent = "XSS уязвимость не обнаружена"
         ERROR.textContent = "невалидный url, возможно вы не указали <https://>"
         let _td = document.querySelectorAll('td')
           for (let i = 0; i < _td.length; i++) {
             const element = _td[i];
             element.textContent = "NO"
           }
       }
 


  О уязвимостях

Все что можно затестить есть тут https://testsafebrowsing.appspot.com/
Нужно понимать что каждая из наших уязвимостей может как быть угрозой на всех ОС так и быть специфичной для конкретной.

Malware
Вредоносная программа[1][2] (другие термины: зловредная программа, вредонос[3], зловред[4]; англ. malware — словослияние слов malicious и software) — любое программное обеспечение, предназначенное для получения несанкционированного доступа к вычислительным ресурсам самой ЭВМ или к информации, хранимой на ЭВМ, с целью несанкционированного использования ресурсов ЭВМ или причинения вреда (нанесения ущерба) владельцу информации, и/или владельцу ЭВМ, и/или владельцу сети ЭВМ, путём копирования, искажения, удаления или подмены информации. Многие[какие?] антивирусы считают крэки (кряки), кейгены и прочие программы для взлома приложений вредоносными программами, или потенциально опасными.


Social engineering

Социáльная инженерия — в контексте информационной безопасности — психологическое манипулирование людьми с целью совершения определенных действий или разглашения конфиденциальной информации. Следует отличать от понятия социальной инженерии в социальных науках - которое не касается разглашения конфиденциальной информации. Совокупность уловок с целью сбора информации, подделки или несанкционированного доступа, от традиционного "мошенничества" отличается тем, что часто является одним из многих шагов в более сложной схеме мошенничества[1].

Unwanted software

Переведено с английского языка.-Потенциально нежелательная программа или потенциально нежелательное приложение - это программное обеспечение, которое пользователь может счесть нежелательным. Он используется в качестве субъективного критерия маркировки продуктами безопасности и родительского контроля.




Potentially Harmful Applications

Потенциально опасные приложения (PHA) - это приложения, которые могут подвергнуть опасности пользователей, пользовательские данные или устройства. Эти приложения часто называют вредоносным ПО . Мы разработали ряд категорий для различных типов PHA, включая трояны, фишинговые и шпионские приложения, и мы постоянно обновляем и добавляем новые категории.
