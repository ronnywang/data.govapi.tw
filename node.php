<?php

$node_id = intval($node_id);
if (!$node_id) {
    error("no node_id");
    exit;
}
$url = 'https://data.gov.tw/dataset/' . $node_id;
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
$ret['id'] = $node_id;
$ret['title'] = trim($doc->getElementById('post-content')->getElementsByTagName('h1')->item(0)->nodeValue);
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
        while (true) {
            foreach ($value_dom->getElementsByTagName('tr') as $tr_dom) {
                $td_doms = $tr_dom->getElementsByTagName('td');

                $type = trim(strtolower($td_doms->item(0)->nodeValue));
                $url = 'http://data.gov.tw' . $td_doms->item(0)->getElementsByTagName('a')->item(0)->getAttribute('href');
                $desc = trim($td_doms->item(2)->nodeValue);
                $ret[$name][] = array(
                    'type' => $type,
                    'url' => $url,
                    'description' => $desc,
                );
            }

            foreach ($value_dom->getElementsByTagName('li') as $li_dom) {
                if ($li_dom->getAttribute('class') == 'pager-next') {
                    $next_url = 'http://data.gov.tw' . $li_dom->getElementsByTagName('a')->item(0)->getAttribute('href');
                    $curl = curl_init($next_url);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_USERAGENT, $agent);
                    $content = curl_exec($curl);
                    $info = curl_getinfo($curl);
                    if ($info['http_code'] != 200) {
                        error("找不到這資料, $next_url (code={$info['http_code']})");
                    }
                    curl_close($curl);

                    $next_doc = new DOMDocument;
                    @$next_doc->loadHTML($content);
                    foreach ($next_doc->getElementsByTagName('th') as $th_dom) {
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
                            continue 3;
                        }
                    }
                }
            }
            break;
        }
    } elseif ($name == '資料集評分') {
        continue;
    } else {
        $ret[$name] = trim($value_dom->nodeValue);
    }
}

foreach ($doc->getElementsByTagName('li') as $li_dom) {
    if ($li_dom->getAttribute('class') == 'statistics_counter') {
        $ret['瀏覽次數'] = intval(explode('：', trim($li_dom->nodeValue))[1]);
    } else if (strpos($li_dom->getAttribute('class'), 'statistics_download_counter') !== false) {
        $ret['下載次數'] = intval(explode(': ', trim($li_dom->nodeValue))[1]);
    }
}

echo json_encode(array(
    'error' => false,
    'url' => $url,
    'data' => $ret,
), JSON_UNESCAPED_UNICODE);
