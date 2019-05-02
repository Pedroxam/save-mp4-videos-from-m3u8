<?php
/*
 * By MyNameIsPedram - Pedroxam
*/

define('BASE', dirname(__FILE__));

function curl($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	// curl_setopt($ch, CURLOPT_PROXY, 'PROXY:IP');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_ENCODING, '');
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:31.0) Gecko/20100101 Firefox/31.0');
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}

function validate_url($url) {
	if (!filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
		return true;
	}
	return 'http://' . strtolower($url);
}

function get_segment($url) {
  $m3u8 = curl($url);
  if (strlen($m3u8) > 3) {
    $tmp =  strrpos($url, '/');
    if ($tmp !== false) {
      $base_url = substr($url, 0, $tmp + 1);
      if (validate_url($base_url)) {
        $array = preg_split('/\s*\R\s*/m', trim($m3u8), NULL, PREG_SPLIT_NO_EMPTY);
        $url2 = array();
        foreach ($array as $line) {
          $line = trim($line);
          if (strlen($line) > 2) {
            if ($line[0] != '#') {
              if (validate_url($line)) {
                $url2[] = $line;
              } else {
                $url2[] = $base_url . $line;
              }                    
            }
          }
        }
        return $url2;
      }
    }
  }
  return false;
}

function save_video($url) {
	$new_name = rand() . '.ts';
	file_put_contents(BASE . '/list.txt', "file '{$new_name}'\n", FILE_APPEND);
	$put = file_put_contents(BASE . '/' . $new_name, curl($url));
	if($put)
		return true;
	else
		return false;
}

function get_path($url,$chunk) {
	$parse = parse_url($url);
	preg_match('~([^/]+)\.m3u8~',$parse['path'],$match);
	$path = str_replace($match[0], '',$parse['path']);
	$port = isset($parse['port']) ? $parse['port'] : '80';
	$host = $parse['scheme'] . '://' . $parse['host'] . ':' . $port . $path . $chunk;
	return $host;
}

// ===== END FUNCTIONS ===========================

// ===== Concat Videos (FFmpeg Required) ===========================>>

if(isset($_GET['concat_files']))
{
	$new_video  = rand() . '.mp4';
	$log  = BASE . '/log.txt';
	$list = BASE . '/list.txt';
	$cmd  = "ffmpeg -f concat -safe 0 -i $list -c copy " . BASE . '/' . $new_video;
	pclose(popen("start /b " . $cmd . " 1> $log 2>&1", "r")); //windows
	// shell_exec($cmd . " 1> $log 2>&1"); //linux
	exit('Done ! Your Video is Ready ! ===> ' . $new_video );
}

// ===== Start Download Parts ===========================>>

$m3u8_url = 'http://example.com/path/chunks.m3u8?abcdefg';

$names = get_segment($m3u8_url);

foreach($names as $name) {
	save_video(get_path($m3u8_url,$name));
}

echo "<a href='./?concat_files=true'>Click Here To Concat Videos</a>";
