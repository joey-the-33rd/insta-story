<?php
class instagram_story {
    protected $cookie_file;

    public function __construct() {
        $this->cookie_file = dirname(__FILE__) . "/cookie.txt";
    }

    protected function file_get_contents_curl($url) {
        if (!file_exists($this->cookie_file)) {
            throw new Exception("Cookie file not found. Please update your cookie.txt.");
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $answer = curl_exec($curl);
        if (curl_errno($curl)) {
            curl_close($curl);
            throw new Exception("Curl error: " . curl_error($curl));
        }
        curl_close($curl);
        return $answer;
    }

    public function getStory($username) {
        $username = htmlspecialchars(strip_tags($username));
        $url = @file_get_contents("https://www.instagram.com/$username/");
        if ($url === false) {
            echo "Error fetching Instagram page for user: $username";
            return;
        }
        $json_pattern = '/window\._sharedData\s*=\s*(\{.*\});<\/script>/';
        if (!preg_match($json_pattern, $url, $matches)) {
            echo "Could not extract JSON data from Instagram page.";
            return;
        }
        $array = json_decode($matches[1], true);
        if (!$array) {
            echo "Error decoding JSON data.";
            return;
        }
        if (!isset($array['entry_data']['ProfilePage'][0]['graphql']['user']['id'])) {
            echo "User ID not found in JSON data.";
            return;
        }
        $user_id = $array['entry_data']['ProfilePage'][0]['graphql']['user']['id'];
        $query_url = "https://www.instagram.com/graphql/query/?query_hash=de8017ee0a7c9c45ec4260733d81ea31&variables=" . urlencode(json_encode([
            "reel_ids" => [$user_id],
            "tag_names" => [],
            "location_ids" => [],
            "highlight_reel_ids" => [],
            "precomposed_overlay" => false,
            "show_story_viewer_list" => true,
            "story_viewer_fetch_count" => 50,
            "story_viewer_cursor" => ""
        ]));
        try {
            $stories_json = $this->file_get_contents_curl($query_url);
        } catch (Exception $e) {
            echo "Error fetching stories: " . $e->getMessage();
            return;
        }
        $data = json_decode($stories_json, true);
        if (!$data || !isset($data['data']['reels_media'][0]['items'])) {
            echo "No stories found or error decoding stories data.";
            return;
        }
        $stories = $data['data']['reels_media'][0]['items'];
        $_story = [];
        foreach ($stories as $story) {
            if (!array_key_exists('video_resources', $story)) {
                $_story[] = $story['display_url'];
            } else {
                $_story[] = $story['video_resources'][0]['src'];
            }
        }
        foreach ($_story as $story) {
            if (strpos($story, 'mp4') === false) {
                echo "<a href=\"$story&dl=1\"><img src=\"$story\" alt=\"Instagram story image\"></a>";
            } else {
                echo '<video width="320" height="240" controls>';
                echo '<source src="' . htmlspecialchars($story) . '" type="video/mp4">';
                echo '</video>';
                echo "<a href=\"$story&dl=1\">Download</a>";
            }
        }
    }
}
