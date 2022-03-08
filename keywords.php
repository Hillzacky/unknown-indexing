<?php class Word {
  
  static function req($url = null, $options = null) {
    if (is_null($url)) return false;
    $defaults = array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_FRESH_CONNECT => 1
    );
    if ($options) foreach ($options as $k => $v) $defaults[$k] = $v;
    array_filter($defaults, function($a) { return !empty($a); });
    $ch = curl_init();
    curl_setopt_array($ch, $defaults);
    $out = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    return ($err)?$err:$out;
  }
  
  static function trendDaily($lang='id',$country='ID',$tz='-420',$ns=15){
    $response = file_get_contents("https://trends.google.com/trends/api/dailytrends?hl=$lang&geo=$country&tz=$tz&ns=$ns");
    $result = json_decode(trim(substr($response, 5)));
    foreach($result->default->trendingSearchesDays[0]->trendingSearches as $td){
      $data[] = $td->title->query;
    }
    return $data;
  }
  
  static function amazon($query, $opt=[]){
    $response = static::req('https://completion.amazon.com/search/complete?mkt=1&search-alias=aps&q='.urlencode($query), $opt);
    return json_decode($response)[1];
  }
  
  static function baidu($query){
    $response = static::req('http://suggestion.baidu.com/su?&wd='.urlencode($query));
    preg_match('/s:(\[.*\])\}\);/', stripslashes(iconv('GB2312', 'UTF-8', $response)), $match);
    return json_decode($match[1]);
  }
  
  static function bilibili($query){
    $response = static::req('https://s.search.bilibili.com/main/suggest?term='.urlencode($query));
    return json_decode($response, true);
  }
  
  static function google($query, $opt=[]){
    // &hl=lang&gl=country&ds=source
    $xml = static::req('https://suggestqueries.google.com/complete/search?output=toolbar&q='.urlencode($query), $opt);
    $xml = simplexml_load_string(utf8_encode($xml));
    foreach ($xml as $sugg) $data[] = (string) $sugg->suggestion->attributes()->data;
    return $data;
  }
  
  static function joybuy($query, $opt=[]){
    $opt[CURLOPT_HTTPHEADER] = ['Referer: https://www.jd.com/'];
    $response = static::req('https://dd-search.jd.com/?ver=2&key='.urlencode($query), $opt);
    $json = json_decode($response);
    foreach ($json as $sugg) {
      $key = $sugg->keyword;
      if (is_null($key)) continue;
      $count = $sugg->qresult;
      $data[] = ['keyword'=>$key,'result'=>$count];
    }
    return $data;
  }
  
  static function moegirlpedia($query, $opt=[]){
    $response = static::req('https://zh.moegirl.org.cn/api.php?action=opensearch&search='.urlencode($query), $opt);
    $json = json_decode($response);
    for ($i = 0; $i < count($json[1]); $i++) {
      $key = $json[1][$i];
      $description = $json[2][$i];
      $url = $json[3][$i];
      $data[] = ['keyword'=>$key,'desc'=>$description,'url'=>$url];
    }
    return $data;
  }
  
  static function pixiv($query, $opt=[]){
    $opt[CURLOPT_HTTPHEADER] = ['Referer: https://www.pixiv.net/'];
    $response = static::req('https://www.pixiv.net/rpc/cps.php?keyword='.urlencode($query), $opt);
    $json = json_decode($response);
    $results = $json->candidates;
    foreach ($results as $sugg) {
      $key = $sugg->tag_name;
      $count = $sugg->access_count;
      $data[] = ['keyword'=>$key,'result'=>$count];
    }
    return $data;
  }
  
  static function sinaweibo($query){
    $response = static::req('https://s.weibo.com/Ajax_Search/suggest?key='.urlencode('#'.$query));
    $json = json_decode($response);
    return $json->data;
  }
  
  static function taobao($query){
    $response = static::req('https://suggest.taobao.com/sug?code=utf-8&q='.urlencode($query));
    $json = json_decode($response);
    $result = $json->result;
    foreach ($result as $sugg) {
      $key = $sugg[0];
      $count = round($sugg[1]);
      $data[] = ['keyword'=>$key,'result'=>$count];
    }
    return $data;
  }
  
  static function wikipedia($query, $opt=[]){
    if (strpos($query, ' ') !== false) {
      $parts = explode(' ', $query);
      $code = array_shift($parts);
      $query = implode(' ', $parts);
      $response = static::req("https://$code.wikipedia.org/w/api.php?action=opensearch&search=".urlencode($query), $opt);
      $json = json_decode($response);
      for ($i = 0; $i < count($json[1]); $i++) {
        $key = $json[1][$i];
        $description = $json[2][$i];
        $url = $json[3][$i];
        $data[] = ['keyword'=>$key,'desc'=>$description,'url'=>$url];
      }
      if (count($json[1]) === 0) {
        $data = ['result'=>'No search suggestions found. Search Wikipedia.'.$code.' for '.$query];
      }
      return $data;
    } else { return false; }
  }
  
  static function wolframalpha($query, $opt=[]){
    $response = static::req('https://www.wolframalpha.com/input/autocomplete.jsp?i='.urlencode($query), $opt);
    $json = json_decode($response);
    $results = $json->results;
    foreach ($results as $sugg) {
      $key = $sugg->input;
      $description = $sugg->description;
      $data[] = ['keyword'=>$key,'desc'=>$description];
    }
    return $data;
  }
  
  static function zhihu($query){
    $response = static::req('https://www.zhihu.com/api/v4/search/suggest?q='.urlencode($query));
    $json = json_decode($response);
    $suggest = $json->suggest;
    foreach ($suggest as $sugg) {
      $data[] = $sugg->query;
    }
    return $data;
  }
  
}
