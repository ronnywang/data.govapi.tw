<?php

$node_id = intval($node_id);
if (!$node_id) {
    error("no node_id");
    exit;
}
$url = 'http://data.gov.tw/node/' . $node_id;
$agent = "data.govapi.tw by IP: {$_SERVER['REMOTE_ADDR']}";
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_USERAGENT, $agent);
$content = curl_exec($curl);
$info = curl_getinfo($curl);
if ($info['http_code'] != 200) {
    error("找不到這資料, $url (code={$info['http_code']})");
}
curl_close($curl);

$doc = new DOMDocument;
@$doc->loadHTML($content);


$ret = array();
$ret['title'] = trim($doc->getElementsByTagName('h1')->item(0)->nodeValue);
foreach ($doc->getElementsByTagName('th') as $th_dom) {
    $name = $th_dom->nodeValue;
    $value_dom = $th_dom->nextSibling;
    while ($value_dom) {
        if ($value_dom->nodeType == XML_ELEMENT_NODE) {
            break;
        }
        $value_dom = $value_dom->nextSibling;
    }
    if (!$value_dom) {
        continue;
    }

    if ($name == '資料資源') {
        $ret[$name] = array();
        foreach ($value_dom->getElementsByTagName('a') as $a_dom) {
            if (in_array(strtolower($a_dom->nodeValue), array('doc', 'word', 'pdf', 'webservice'))) {
                continue;
            }
            if ($a_dom->nodeValue == '檢視資料' or trim($a_dom->nodeValue) == '') {
                continue;
            }
            $ret[$name][] = array(
                'type' => strtolower($a_dom->nodeValue),
                'url' => 'http://data.gov.tw' . $a_dom->getAttribute('href'),
            );
        }
    } elseif ($name == '資料集評分') {
        continue;
    } else {
        $ret[$name] = trim($value_dom->nodeValue);
    }
}
echo json_encode(array(
    'error' => false,
    'url' => $url,
    'data' => $ret,
), JSON_UNESCAPED_UNICODE);
