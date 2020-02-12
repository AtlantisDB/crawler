<?php

function log_write($data,$file){
	error_log("\n$data", 3, "/var/master/output_".$file.".txt");
}

function log_clear($file){
	$fh = fopen('/var/master/output_'.$file.'.txt', 'w');
	fclose($fh);
}

function keygenerate($content){
	$hash=md5($content);
	return $hash[0];
}

function hashgenerate($content){
	$hash=sha1($content);
	return $hash;
}

function make_timestamp($code=""){
	if ($code==""){
		return date('YmdHis');
	}else{
		return date('YmdHis',time()+intval($code));
	}
}

function codegenerate($length=8){
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$rs="";
	for ($i = 0; $i < $length; $i++){ $rs .= $characters[rand(0, strlen($characters) - 1)]; }
	return $rs;
}

function makesafe($d){
  $d = str_replace("\t","",$d);
  $d = str_replace("\n"," ",$d);
  $d = str_replace("\\","&#92;",$d);
  $d = str_replace("  "," ",$d);
  $d = str_replace("(c)","&#169;",$d);
  $d = str_replace("(r)","&#174;",$d);
  $d = str_replace("\"","&#34;",$d);
  $d = str_replace("'","&#39;",$d);
  $d = str_replace("’","&#39;",$d);
  $d = str_replace("<","&#60;",$d);
  $d = str_replace(">","&#62;",$d);
  $d = str_replace("DELETE FROM","",$d);
  return $d;
}

function makeurls($d){
  $d = str_replace("_","",$d);
  $d = str_replace("-","",$d);
  $d = str_replace("%","",$d);
  $d = str_replace("!","",$d);
  $d = str_replace("@","",$d);
  $d = str_replace("#","",$d);
  $d = str_replace("$","",$d);
  $d = str_replace("(","",$d);
  $d = str_replace(".","",$d);
  $d = str_replace("(","",$d);
  $d = str_replace("\"","",$d);
  $d = str_replace("/","",$d);
  $d = str_replace(":","",$d);
  $d = str_replace("|","",$d);
  return $d;
}

function html2txt($document){
	$document=str_replace("&lt;","<",$document);
	$document=str_replace("&gt;",">",$document);
  $search = array('@<script[^>]*?>.*?</script>@si',  // Strip out javascript
                 '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
                 '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
                 '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA
  );
  $text = preg_replace($search, '', $document);
  return $text;
}

function truncate($string,$length=100,$append="...") {
  $string = trim($string);

  if(strlen($string) > $length) {
    $string = wordwrap($string, $length);
    $string = explode("\n", $string, 2);
    $string = $string[0] . $append;
  }

  return $string;
}

function multiexplode ($delimiters,$string) {
    $ready = str_replace($delimiters, $delimiters[0], $string);
    $launch = explode($delimiters[0], $ready);
    return  $launch;
}
function partuppercase($string){
  if(preg_match("/[A-Z]/", $string)){
  	return true;
  }else{
   return false;
  }
}
function partlowercase($string){
  if(preg_match("/[a-z]/", $string)){
  	return true;
  }else{
   return false;
  }
}
function hasalpha($string){
	if(preg_match("/[a-z]/i", $string)){
    return true;
  }else{
    return false;
  }
}

function endswith($FullStr, $needle){
  $StrLen = strlen($needle);
  $FullStrEnd = substr($FullStr, strlen($FullStr) - $StrLen);
  return $FullStrEnd == $needle;
}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ Get site common phrases
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

function content_get_phrases($html){
  $meta=content_get_metadata($html);
  $content = html2txt($html);
  $content = str_replace("'","",$content);
  $content = str_replace("\"","",$content);
  $content=strtolower("".$meta["title"]." - ".$meta["description"]." - ".$content."");

  $match_all=content_get_phrases_content($content);
  $match_title=content_get_phrases_content($meta["title"]);
  $match_description=content_get_phrases_content($meta["description"]);

  $phraseview=array();
  $phrasesaved=array();

  $exploded = multiexplode(array(",",".","|",":","!","?","-","(",")"),$content);
  foreach ($exploded as $key => $value){
    $value = " ".$value." ";
    $value = preg_replace("/\b\w{1,2}\b/u", '', $value);
    $value = str_replace("  "," ",$value);
    $value = str_replace("  "," ",$value);
    $value = preg_replace("/[^a-zA-Z 0-9]+/", "", $value);
    $value = trim($value);
    $save=true;
    if (!strpos($value, " ") !== false){
      $save=false;
    }
    if (!strlen($value)>=10){
      $save=false;
    }

    if ($save==true){
      $parsekey=md5($value);
      if (!isset($phrasesaved[$parsekey])){
        $phrasesaved[$parsekey]=true;
        $countfound=0;
        $countfound=$countfound+substr_count($match_all, $value);
        $countfound=$countfound+(substr_count($match_title, $value)*80);
        $countfound=$countfound+(substr_count($match_description, $value)*20);
        array_push($phraseview,$value);
        if ($countfound>=2){
          for ($k = 0 ; $k < $countfound; $k++){
            array_push($phraseview,$value);
          }
        }
      }
    }else{
      unset($exploded[$key]);
    }
  }

  //Get top results
  $returnarray=array();
  $phrases=array_count_values($phraseview);
  arsort($phrases);
  $limit=0;
  foreach ($phrases as $key => $value){
    if ($limit<=2){
      if ($value>=5){
        if (strpos($key, " ") !== false){
          if (strlen($key)>=10){
            //sitelog("logic","Found phrase ".$key." with ".$value." matches");
            array_push($returnarray,$key);
          }
        }
      }
    }
  }

  return $returnarray;

}

function content_get_phrases_content($contentmatch){
  $contentmatch = strtolower($contentmatch);
  $contentmatch = str_replace(" the ","",$contentmatch);
  $contentmatch = str_replace(" and ","",$contentmatch);
  $contentmatch = preg_replace("/\b\w{1,2}\b/u", '', $contentmatch);
  $contentmatch = preg_replace("/[^a-zA-Z 0-9]+/", "", $contentmatch);
  $contentmatch = str_replace("  "," ",$contentmatch);
  $contentmatch = str_replace("  "," ",$contentmatch);
  return $contentmatch;
}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ Check if valid domain
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

function check_valid_domain($code){
  $validgo=(preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $code) //valid chars check
	&& preg_match("/^.{1,253}$/", $code) //overall length check
  && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $code)); //length of each label

	if ($validgo==true){
		return "true";
	}else{
		//check with out * wildcard
		$codecheckwild=str_replace("*.","",$code);
		$validgoagain=(preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $codecheckwild) //valid chars check
    && preg_match("/^.{1,253}$/", $codecheckwild) //overall length check
    && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $codecheckwild)); //length of each label
		if ($validgoagain==true){
			return true;
		}else{
			return false;
		}
	}
}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ Check if the webpage has already been saved
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

function get_webpage_id($url){
  $returns=false;
  $key=keygenerate($url);
  $hash=hashgenerate($url);

  $query=sqdb_query("SELECT id FROM webpages_info_urlhashes_".$key." WHERE content='".$hash."' LIMIT 1","save");
  if (sqdb_num_rows($query,"save") > 0){
    while ($row=sqdb_fetch_array($query,"save")){
      $returns=$row["id"];
    }
  }

  return $returns;
}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ Check if the webpage has already been saved
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

function check_webpage_indexed($url){
  $returns=false;
  $key=keygenerate($url);
  $hash=hashgenerate($url);

  $query=sqdb_query("SELECT * FROM webpages_info_urlhashes_".$key." WHERE content='".$hash."' LIMIT 1","save");
  if (sqdb_num_rows($query,"save") > 0){
    $returns=true;
  }

  return $returns;
}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ BLACKHAT Site Source Scan!
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

function check_blackhat($content){
  $returns=false;
  $content=makesafe($content);
  $content=strtolower($content);
  // &#34; = "
  // &#39; = '

  //$content=str_replace("window.location = &#34;http","XBAD=bad=BADX",$content);
  preg_match_all("|XBAD=([a-z]*)=BADX|i",  $content, $out,PREG_PATTERN_ORDER);
  $number=count($out[1]);
  if ($number>=1){ $returns=true; }

  return $returns;
}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ Virus Site Source Scan!
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

function check_virus($content){
  $returns=false;
  $content=makesafe($content);
  $content=strtolower($content);
  // &#34; = "
  // &#39; = '

  //$content=str_replace("window.location = &#34;http","XBAD=bad=BADX",$content);
  preg_match_all("|XBAD=([a-z]*)=BADX|i",  $content, $out,PREG_PATTERN_ORDER);
  $number=count($out[1]);
  if ($number>=1){ $returns=true; }

  return $returns;
}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ BLACKHAT Site Source Scan!
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

function check_noindex($url){
  $returns=false;

  if (strpos($url, "twitter.com") !== false){ if (strpos($url, "/status") !== false){ $returns=true; }}
  if (strpos($url, "m.twitter.com") !== false){ $returns=true; }
  if (strpos($url, "?") !== false){ $returns=true; }
  if (strpos($url, "facebook.com/photo.php") !== false){ $returns=true; }
  if (strpos($url, "m.facebook.com") !== false){ $returns=true; }
  if (strpos($url, "facebook.com") !== false){ if (strpos($url, "/posts") !== false){ $returns=true; }}
	if (strpos($url, "facebook.com") !== false){ if (strpos($url, "/places") !== false){ $returns=true; }}
  if (strpos($url, "reddit.com") !== false){ if (strpos($url, "/comments") !== false){ $returns=true; }}
	if (strpos($url, "stackoverflow.com") !== false){ if (strpos($url, "/users") !== false){ $returns=true; }}
	if (strpos($url, "stackoverflow.com") !== false){ if (strpos($url, "/jobs") !== false){ $returns=true; }}

  return $returns;
}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ Bad source finder
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

function check_adult($d){
  $number=0;
  $d=strtolower($d);
  $d = str_replace("."," ",$d);
  $d = str_replace(","," ",$d);
  $d = str_replace("("," ",$d);
  $d = str_replace(")"," ",$d);
  $d = str_replace("/"," ",$d);
  $d = str_replace("-"," ",$d);
  $d = str_replace("_"," ",$d);
  $d = str_replace("<"," ",$d);
  $d = str_replace(">"," ",$d);
  $d = str_replace("\""," ",$d);
  $d = str_replace(" fuck ","X=bad=X",$d);
  $d = str_replace(" pussy ","X=bad=X",$d);
  $d = str_replace(" cock ","X=bad=X",$d);
  $d = str_replace(" sexy ","X=bad=X",$d);
  $d = str_replace(" dick ","X=bad=X",$d);
  $d = str_replace(" fucking ","X=bad=X",$d);
  $d = str_replace(" porn ","X=bad=X",$d);
  $d = str_replace(" dildo ","X=bad=X",$d);
  $d = str_replace(" orgasms ","X=bad=X",$d);
  $d = str_replace(" fucks her ","X=bad=X",$d);
  $d = str_replace(" doggystyle ","X=bad=X",$d);
  $d = str_replace(" naked men ","X=bad=X",$d);
  $d = str_replace(" naked dudes ","X=bad=X",$d);
  $d = str_replace(" sexcam ","X=bad=X",$d);
  $d = str_replace(" nude girls ","X=bad=X",$d);
  $d = str_replace(" naked girls ","X=bad=X",$d);
  $d = str_replace(" cartoon sex ","X=bad=X",$d);
  $d = str_replace(" orgies ","X=bad=X",$d);
  $d = str_replace(" adult movies ","X=bad=X",$d);
  $d = str_replace(" celebrity sex ","X=bad=X",$d);
  $d = str_replace(" sex ","X=bad=X",$d);
  $d = str_replace(" free xxx ","X=bad=X",$d);
  $d = str_replace(" sex movies ","X=bad=X",$d);
  preg_match_all("|X=([a-z]*)=X|i",  $d, $out,PREG_PATTERN_ORDER);
  $number=count($out[1]);
  if ($number>=30){
    return 1;
  }else{
    return 0;
  }
}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ Get Site Meta Data
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

function content_get_metadata($html){
  //Do some filter for extra junk that messes with meta orders
  $html=str_replace("itemprop=","name=",$html); $html=str_replace("property=","name=",$html); $html=str_replace("value=","content=",$html);
  $html=str_replace("og:description","description",$html); $html=str_replace("twitter:","",$html);
  $html=str_replace("og:site_name","title",$html);
  $html=str_replace("data-page-subject=\"true\" ","",$html); $html=str_replace("data-page-subject=\"false\" ","",$html);
  $html=str_replace("\n"," ",$html);
  $meta=array();

  $meta['title']="";
  $meta['keywords']="";
  $meta['description']="";
  $meta['author']="";
  $meta['language']="";
  $meta['robots']="";
  $meta['url']="";
  $meta['heyanna-robots']="";

  //If Name Before Content
  preg_match_all("|<meta[^>]+name=\"([^\"]*)\"[^>]" . "+content=\"([^\"]*)\"|i",  $html, $out,PREG_PATTERN_ORDER);
  for ($i=0;$i < count($out[1]);$i++) {
    if (strtolower($out[1][$i]) == "title"){ $meta['title']=truncate(html2txt($out[2][$i]),100,""); }
    if (strtolower($out[1][$i]) == "keywords"){ $meta['keywords']=truncate(html2txt($out[2][$i]),120,""); }
    if (strtolower($out[1][$i]) == "description"){ $meta['description']=truncate(html2txt($out[2][$i]),160,""); }
    if (strtolower($out[1][$i]) == "author"){ $meta['author']=html2txt($out[2][$i]); }
    if (strtolower($out[1][$i]) == "language"){ $meta['language']=html2txt($out[2][$i]); }
    if (strtolower($out[1][$i]) == "robots"){ $meta['robots']=html2txt($out[2][$i]); }
    if (strtolower($out[1][$i]) == "heyanna-robots"){ $meta['heyanna-robots']=html2txt($out[2][$i]); }
    if (strtolower($out[1][$i]) == "url"){ $meta['url']=html2txt($out[2][$i]); }
  }
  //If Name After Content
  preg_match_all("|<meta[^>]+content=\"([^\"]*)\"[^>]" . "+name=\"([^\"]*)\"|i",  $html, $out,PREG_PATTERN_ORDER);
  for ($i=0;$i < count($out[1]);$i++) {
    if (strtolower($out[2][$i]) == "title"){ $meta['title']=truncate(html2txt($out[1][$i]),100,""); }
    if (strtolower($out[2][$i]) == "keywords"){ $meta['keywords']=truncate(html2txt($out[1][$i]),120,""); }
    if (strtolower($out[2][$i]) == "description"){ $meta['description']=truncate(html2txt($out[1][$i]),160,""); }
    if (strtolower($out[2][$i]) == "author"){ $meta['author']=html2txt($out[1][$i]); }
    if (strtolower($out[2][$i]) == "language"){ $meta['language']=html2txt($out[1][$i]); }
    if (strtolower($out[2][$i]) == "robots"){ $meta['robots']=html2txt($out[1][$i]); }
    if (strtolower($out[2][$i]) == "heyanna-robots"){ $meta['heyanna-robots']=html2txt($out[1][$i]); }
    if (strtolower($out[2][$i]) == "url"){ $meta['url']=html2txt($out[1][$i]); }
  }
  //If data not there find it somewhere.
  if (preg_match("|title>(.*)</title|im", $html, $var)){ $meta['title']=truncate(html2txt($var[1]),100,""); }
  return $meta;
}


?>
