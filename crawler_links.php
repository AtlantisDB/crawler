<?php

include("functions.php");
include("startup.php");
include("sqdb.php");

log_clear("links");
log_write("Startup Test @ ".make_timestamp()."!","links");

log_write("Loading links to check...","links");

$querye=sqdb_query("SELECT * FROM crawl_check ORDER BY id ASC LIMIT 1","index");
if (sqdb_num_rows($querye) > 0){
  while ($row=sqdb_fetch_array($querye)){
    $id = $row['id'];
    $scanurl = $row['content'];
		if (strpos($scanurl, "http") === false){ $scanurl="http://".$scanurl.""; }
		$webpage_score=5;
		$webpage_https=false;
		$webpage_db_new=true;

    log_write("Starting process on url ".$scanurl."","links");

    $ch = curl_init($scanurl);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0.3 Safari/605.1.15 (compatible; AtlantisDB SpiderBot/1.2 Instance)');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 7);
    $webpage_html = curl_exec($ch);
    $webpage_header=curl_getinfo($ch);
    curl_close($ch);

    $webpage_size_bytes=strlen($webpage_html);
    $webpage_url=$webpage_header["url"];
    //Remove www So No Duplicates for and without www.
    $webpage_url=str_replace("https://www.","https://",$webpage_url);
    $webpage_url=str_replace("http://www.","http://",$webpage_url);
		//check if site site is using https
		if (strpos($webpage_url, "https://") !== false){ $webpage_https=true; }
		//Clean up the URL for saving
		$webpage_url=str_replace("https://","",$webpage_url);
		$webpage_url=str_replace("http://","",$webpage_url);
		$webpage_url=trim($webpage_url,'/');
		if (strpos($webpage_url, "#") !== false){ $webpage_url=substr($webpage_url, 0, strpos($webpage_url, "#")); }
		$webpage_url=strtolower($webpage_url);
		//Check if the url is one we can index
		if (check_noindex($webpage_url)==true){ $webpage_score=0; }
		if ($webpage_size_bytes<=500){ $webpage_score=0; }
		if ($webpage_db_new==true){ if (check_webpage_indexed($webpage_url)==true){ $webpage_score=0; } }


		//Generate basic info about the content
		$webpage_key=keygenerate($webpage_url);
		$webpage_url_hash=hashgenerate($webpage_url);
		$webpage_adult=check_adult($webpage_html);

    log_write("Starting scan on webpage with the URL ".$webpage_url." with byte size of ".$webpage_size_bytes." and key ".$webpage_key." hash ".$webpage_url_hash."","links");

		//Check if we can keep running
		if ($webpage_score>=1){
      log_write("Passed basic checks, starting advanced scanning","links");
			//sitelog("good","Passed basic checks, starting advanced scanning");
			//sitelog("info","Adult content check gave us ".$webpage_adult."");

			//Check basic contents
			$webpage_meta=content_get_metadata($webpage_html);

			//DO checks using content given to generate a score
			$temp_title=preg_replace("/[^A-Za-z0-9-_\s]/", "",$webpage_meta["title"]);
			$temp_description=preg_replace("/[^A-Za-z0-9-_\s]/", "",$webpage_meta["description"]);
			$temp_title_len=strlen($temp_title);
			$temp_description_len=strlen($temp_description);

			if ($temp_title!=""){ $webpage_score+=20; }
			if ($temp_description!=""){ $webpage_score+=30;  }
			if ($temp_title_len<5){ if ($temp_title!=""){ $webpage_score-=20; }}
			if ($temp_description_len<6){ if ($temp_description!=""){ $webpage_score-=30; }}
			if (strpos($webpage_meta["description"], "!DOCTYPE html") !== false){ $webpage_score-=2; }
			if (strpos($webpage_meta["robots"], "noindex") !== false){ $webpage_score-=900; }
			if ($temp_title_len>=10){ $webpage_score+=2; }
			if ($temp_title_len>=15){ $webpage_score+=4; }
			if ($temp_title_len>=20){ $webpage_score+=6; }
			if ($temp_title_len>=30){ $webpage_score+=8; }
			if ($temp_title_len>=70){ $webpage_score-=5; }
			if ($temp_title_len>=80){ $webpage_score-=5; }
			if ($temp_title_len>=90){ $webpage_score-=5; }
			if ($temp_title_len>=100){ $webpage_score-=5; }
			if ($temp_description_len <= 100){ $webpage_score-=5; }
			if ($temp_description_len <= 80){ $webpage_score-=5; }
			if ($temp_description_len <= 70){ $webpage_score-=10; }
			if ($temp_description_len <= 50){ $webpage_score-=10; }
			if ($temp_description_len <= 40){ $webpage_score-=15; }
			if ($temp_description_len <= 30){ $webpage_score-=15; }

			//$webpage_linkinscore=round((check_webpage_links_weight($webpage_url))/40);
			////sitelog("score","After all main checks the score is now ".$webpage_score." with ".$webpage_linkinscore." external links for the site");
      log_write("After all main checks the score is now ".$webpage_score."","links");
			$webpage_score=$webpage_score+$webpage_linkinscore;

      //Common phrases in the page for simple index search
			$phrases=content_get_phrases($webpage_html);

			if ($webpage_score>=1){

				//generate word soup
				//$save_title=create_word_soup($webpage_meta["title"]);
				//$save_description=create_word_soup($webpage_meta["description"]);
				//sitelog("info","word soup for title: ".$save_title."");
				//sitelog("info","word soup for description: ".$save_description."");

				//Saving NEW
				if ($webpage_db_new==true){
          $querye=sqdb_query("INSERT INTO crawl_save(content) VALUES('$webpage_url')","index");
          log_write("Added webpage to save list","links");
				}
				//Saving UPDATE
				if ($webpage_db_new==false){
          //We dont need to save, we have it indexed it will get updated later!
				}


				//Scan for new links to add to crawler
				$newlinksfound=array();
				if (preg_match_all('|["\']http\:\/\/([a-zA-Z0-9\-\_\?\&\#]\S*)["\']|i', $webpage_html, $links, PREG_SET_ORDER)){
					foreach ($links as $value){
						$priority=10;
						$link=$value[1];
						$link=makesafe($link);
						$urlcon=strlen($link);
						if ($webpage_url==$link){ $priority=$priority-999; }
						$link=str_replace("https://www.","https://",$link);
			    	$link=str_replace("http://www.","http://",$link);
						$link=str_replace("https://","",$link);
						$link=str_replace("http://","",$link);
						$link=trim($link,'/');
						$link=strtolower($link);
						if (check_noindex($link)==true){ $priority=$priority-999; }
						if (check_webpage_indexed($link)==true){ $priority=$priority-999; }
						if (check_valid_domain($link)==true){ $priority=$priority-999; }
						if (strpos($webpage_meta["robots"], "nofollow") !== false){ $priority=$priority-999; }
						if (strpos($webpage_meta["heyanna-robots"], "nofollow") !== false){$priority=$priority-999; }
						$linkkey=md5($link);

						//Fixing and Prority Check
						if (!isset($newlinksfound[$linkkey])){
							if ($urlcon >= 20){ $priority=$priority-1; }
							if ($urlcon >= 50){ $priority=$priority-2; }
							if ($urlcon >= 80){ $priority=$priority-2; }
							if ($urlcon >= 110){ $priority=$priority-3; }

							//Add New URL to DB
							if ($priority>0){
								$newlinksfound[$linkkey]=true;
								$queryt=sqdb_query("SELECT * FROM crawl_check WHERE url='$link' LIMIT 1");
								if (!sqdb_num_rows($queryt) > 0){
									$result = sqdb_query("INSERT INTO crawl_check(url) VALUES('$link')");
                  log_write("Found new url to index ".$link."","links");
								}else{
                  log_write("We already have url in system to scan ".$link."","links");
								}
							}
						}
					}
				}
			}else{
        log_write("On more indepth scan the webpage failed more advanced scans with a score under 0","links");
			}
		}else{
      log_write("Failed basic checks and cant start main scan","links");
		}
  }
}else{
  log_write("No links found to check","links");
}

?>
