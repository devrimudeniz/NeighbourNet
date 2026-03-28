<?php
/**
 * News Fetcher Helper
 * Fetches and parses Google News RSS feeds for Kalkan/Kaş articles from UK press
 */

function fetchNews($cache_duration = 21600) { // 6 hours cache
    $cache_file = __DIR__ . '/../cache/news_cache.json';
    $cache_dir = __DIR__ . '/../cache';
    
    if (!file_exists($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_duration) {
        $cached = file_get_contents($cache_file);
        $data = json_decode($cached, true);
        if ($data) return $data;
    }
    
    // 3 feeds only = faster cold load (was 5)
    $feeds = [
        'https://news.google.com/rss/search?q=Kalkan+Turkey&hl=en-GB&gl=GB&ceid=GB:en',
        'https://news.google.com/rss/search?q=Kalkan+Ka%C5%9F+Antalya&hl=tr&gl=TR&ceid=TR:tr',
        'https://news.google.com/rss/search?q=Turquoise+Coast+Turkey&hl=en-GB&gl=GB&ceid=GB:en',
    ];
    
    $all_news = [];
    $one_month_ago = strtotime('-30 days');
    $max_per_feed = 15; // stop after 15 items per feed for speed
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 3,
            'user_agent' => 'Mozilla/5.0 (compatible; KalkanSocial/1.0)'
        ]
    ]);
    
    // UK press: BBC, Telegraph, Guardian, Times, Standard, Mail, Mirror, Independent, Express, Sun, Metro, FT, etc.
    $uk_sources = [
        '.co.uk', 'bbc.com', 'bbc.co.uk', 'theguardian.com', 'telegraph.co.uk', 'dailymail.co.uk',
        'mirror.co.uk', 'independent.co.uk', 'express.co.uk', 'thesun.co.uk', 'metro.co.uk',
        'standard.co.uk', 'thetimes.co.uk', 'ft.com', 'dailystar.co.uk',
        'walesonline.co.uk', 'manchestereveningnews.co.uk', 'liverpoolecho.co.uk',
        'birminghammail.co.uk', 'chroniclelive.co.uk', 'edinburghlive.co.uk', 'belfastlive.co.uk',
        'skysports.com', 'channel4.com', 'itv.com', 'reuters.com', 'theconversation.com'
    ];
    $tr_sources = ['.com.tr', 'hurriyet', 'milliyet', 'sabah', 'sozcu', 'haberturk', 'ntv', 'cnnturk', 'trthaber', 'aa.com.tr', 'aksam', 'star.com.tr', 'yenisafak', 'turkiyegazetesi'];
    
    foreach ($feeds as $feed_url) {
        $xml_content = @file_get_contents($feed_url, false, $context);
        if (!$xml_content) continue;
        
        $xml = @simplexml_load_string($xml_content);
        if (!$xml || !isset($xml->channel->item)) continue;
        
        $count = 0;
        foreach ($xml->channel->item as $item) {
            if ($count >= $max_per_feed) break;
            
            $pub_date = strtotime((string)$item->pubDate);
            if ($pub_date < $one_month_ago) continue;
            
            $link = (string)$item->link;
            $source = (string)$item->source;
            $source_url = isset($item->source['url']) ? (string)$item->source['url'] : '';
            $domain = '';
            if ($source_url) {
                $parsed = parse_url($source_url);
                $domain = $parsed['host'] ?? '';
            }
            
            $is_uk = false;
            foreach ($uk_sources as $uk) {
                if (stripos($domain, $uk) !== false || stripos($source, $uk) !== false) {
                    $is_uk = true;
                    break;
                }
            }
            $is_tr = false;
            foreach ($tr_sources as $tr) {
                if (stripos($domain, $tr) !== false || stripos($source, $tr) !== false) {
                    $is_tr = true;
                    break;
                }
            }
            
            $news_item = [
                'title' => html_entity_decode((string)$item->title, ENT_QUOTES, 'UTF-8'),
                'link' => $link,
                'source' => $source,
                'source_url' => $source_url,
                'domain' => $domain,
                'is_uk' => $is_uk,
                'is_tr' => $is_tr,
                'date' => date('Y-m-d H:i', $pub_date),
                'date_human' => date('d M Y', $pub_date),
                'timestamp' => $pub_date,
                'image' => extractImageFromDescription((string)$item->description),
                'description' => html_entity_decode(strip_tags((string)$item->description), ENT_QUOTES, 'UTF-8')
            ];
            
            $key = md5($news_item['title']);
            if (!isset($all_news[$key])) {
                $all_news[$key] = $news_item;
                $count++;
            }
        }
    }
    
    usort($all_news, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    $all_news = array_slice($all_news, 0, 36);
    file_put_contents($cache_file, json_encode($all_news));
    
    return $all_news;
}

/**
 * Extract image from description (if any) - Google News often includes images
 */
function extractImageFromDescription($description) {
    if (preg_match('/<img[^>]+src="([^"]+)"/', $description, $matches)) {
        return $matches[1];
    }
    return null;
}
