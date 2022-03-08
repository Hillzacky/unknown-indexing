<?php class Word {

    /**
     * Return html/json data by using cURL function.
     *
     * @param  string  $keyword
     * @return mixed
     */
    static function suggest($keyword, $lang)
    {
        $api = "http://suggestqueries.google.com/complete/search?callback=?&q=%s&client=android&hl=%s";
        $url = sprintf($api, $keyword, $lang);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json; charset=utf-8']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $content = curl_exec($ch);
        curl_close($ch);
        // Detect encoding and make everything UTF-8
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = iconv('ISO-8859-9', 'UTF-8', $content);
        }
        return $content;
    }

    /**
     * Return array list suggestion from string content
     *
     * @param  string  $string
     * @return array
     */
    static function parse($string, $limit)
    {
        // Remove the outer window.google.ac.h( .. and .. )
        $content = preg_replace("/(^.*?\()|(\))$/", '', $string);
        $data = json_decode($content, true);

        $result = [];
        if (empty($data[1])) {
            return $result;
        }
        $data = $data[1];
        foreach ($data as $key => $value) {
            if (!empty($value[0]) && !in_array($value[0], $result)) {
                array_push($result, $value[0]);
            }
        }
        $result = array_slice($result, 0, $limit);
        return $result;
    }

    /**
     * Search keyword string and get list suggestion search/trending keyword.
     *
     * @param  string  $keyword
     * @return mixed
     */
    static function search($keyword, $lang = 'en', $limit = 10)
    {
        // Remove undesired whitespace of $keyword
        $keyword = mb_convert_kana($keyword, 's');
        $keyword = trim($keyword);
        $keyword = preg_replace('/\s+/', ' ',$keyword);
        $content = static::suggest($keyword, $lang);
        return static::parse($content, $limit);
    }
}
