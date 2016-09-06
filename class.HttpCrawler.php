<?php
class HttpCrawler
{
	function HttpCrawler(){
		$this->cookie_fields = array("domain","access","path","secure","expire","name","value");
	}

	function SetCookie($cookiefile,$cookieinfo){
		if(!isset($cookieinfo["name"])) return false;
		$name = $cookieinfo["name"];
		$all_cookie = $this->ReadCookie($cookiefile);
		$all_cookie[$name] = $cookieinfo;
		return $this->SaveCookieFile($cookiefile,$all_cookie);
	}

	function GetCookieLineFromArr($_arr){
		//resort and remove fields
		$arr = array();
		if(!isset($_arr["access"])) $_arr["access"] = "TRUE";
		if(!isset($_arr["secure"])) $_arr["secure"] = "FALSE";
		if(!isset($_arr["expire"])) $_arr["expire"] = time() + 24 * 3600 * 30;
		if(!isset($_arr["path"])) $_arr["path"] = "/";
		foreach($this->cookie_fields as $k => $v)
		{
			$arr[$v] = $_arr[$v];
		}
		return implode("\t",$arr);
	}

	function SaveCookieFile($cookiefile,$all_cookie){
		$content = "";
		foreach($all_cookie as $name => $arr)
		{
			$line = $this->GetCookieLineFromArr($arr) . "\n";
			$content .= $line;
		}
		return file_put_contents($cookiefile,$content);
	}
	
	function RemoveCookie($cookiefile,$cookiename){
		$all_cookie = $this->ReadCookie($cookiefile);
		if(isset($all_cookie[$cookiename]))
		{
			unset($all_cookie[$cookiename]);
			return $this->SaveCookieFile($cookiefile,$all_cookie);
		}
	}
	
	function ReadCookie($cookiefile,$cookiename=""){
		$result = array();
		if(!file_exists($cookiefile)) return $result;
		$lines = file($cookiefile);
		foreach($lines as $line)
		{
			$line = trim($line);
			if($line == "" || substr($line,0,1) == "#") continue;
			list($domain,$access,$path,$secure,$expire,$name,$value) = $arr_temp = explode("\t",$line);
			if(!$name) continue;
			
			foreach($this->cookie_fields as $k => $v)
			{
				$result[$name][$v] = $arr_temp[$k];
			}
		}
		
		if($cookiename)
		{
			if(isset($result[$cookiename])) return $result[$cookiename];
			else return array();
		}
		
		return $result;
	}
	
	function GetHttpResult($_url,$_para=array(),$ch=""){
// 		if(isset($_para["method"]) && $_para["method"] == "post"){
// 			echo "posting: $_url <br>\n";
// 		}
// 		else{
// 			echo "getting: $_url <br>\n";
// 		}
		$cookiejar = "";
		
		if(!$ch) $ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL,$_url);

		//是否将头信息作为信息流输出
		if(isset($_para["header"]) && is_numeric($_para["header"]))
		{
			curl_setopt($ch, CURLOPT_HEADER, true);
		}
		else
		{
			curl_setopt($ch, CURLOPT_HEADER, false);
		}

		//
		if(isset($_para["nobody"]) && is_numeric($_para["nobody"]))
		{
			curl_setopt($ch, CURLOPT_NOBODY, true);
		}
		else
		{
			curl_setopt($ch, CURLOPT_NOBODY, false);
		}				
		
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//SSL connect error错误更改为false
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);//by ike 20111122
		
		if(isset($_para["maxredirs"]) && is_numeric($_para["maxredirs"]))
		{
			curl_setopt($ch, CURLOPT_MAXREDIRS, $_para["maxredirs"]);
		}
		else
		{
			curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		}
		
		if(isset($_para["autoreferer"]) && $_para["autoreferer"] == false)
		{
			curl_setopt($ch, CURLOPT_AUTOREFERER, false);
		}
		elseif(isset($_para["referer"]) && $_para["referer"])
		{
			curl_setopt($ch, CURLOPT_AUTOREFERER, false);
		}
		else
		{
			curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		}
		
		if(isset($_para["headerfunction"]) && $_para["headerfunction"])
		{
			curl_setopt($ch, CURLOPT_HEADERFUNCTION, $_para["headerfunction"]);
		}
		
		if(isset($_para["readfunction"]) && $_para["readfunction"])
		{
			curl_setopt($ch, CURLOPT_READFUNCTION, $_para["readfunction"]);
		}
		
		if(isset($_para["writefunction"]) && $_para["writefunction"])
		{
			curl_setopt($ch, CURLOPT_WRITEFUNCTION, $_para["writefunction"]);
		}
		
		if(isset($_para["referer"])) curl_setopt($ch, CURLOPT_REFERER, $_para["referer"]);
		if(isset($_para["verbose"])) curl_setopt($ch, CURLOPT_VERBOSE, true);
		
		if(!$cookiejar && isset($_para["cookiejar"])) $cookiejar = $_para["cookiejar"];
		//if(!$cookiejar && isset($_para["ID"])) $cookiejar = $this->getCookieJarByAffId($_para["ID"]);
		
		if($cookiejar && isset($_para["addcookie"]) && is_array($_para["addcookie"]))
		{
			foreach($_para["addcookie"] as $cookiename => $cookieinfo)
			{
				$this->SetCookie($cookiejar,$cookieinfo);
			}
		}
		
		if($cookiejar && isset($_para["remvoecookie"]) && is_array($_para["remvoecookie"]))
		{
			foreach($_para["remvoecookie"] as $cookiename)
			{
				$this->RemoveCookie($cookiejar,$cookiename);
			}
		}
		
		if($cookiejar)
		{
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiejar);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiejar);
		}
		
		if(isset($_para["cookie"]))
		{
			curl_setopt($ch,CURLOPT_COOKIE,$_para["cookie"]);
		}

		if(isset($_para["addheader"]) && is_array($_para["addheader"]))
		{
			//like:array('Content-type: text/plain', 'Content-length: 100')
			curl_setopt($ch,CURLOPT_HTTPHEADER,$_para["addheader"]); 
		}
		
		if(isset($_para["stderr_temp"])){
			$verbose = fopen(INCLUDE_ROOT."temp.txt", 'w+');
			curl_setopt($ch, CURLOPT_STDERR, $verbose);
		}
		
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.86 Safari/537.36');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'ecdhe_ecdsa_aes_256_sha');
		//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		if(isset($_para["no_ssl_verifyhost"]))
		{
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		}else{
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}
		
		if(!isset($_para["no_encoding"]))
		{
			curl_setopt($ch, CURLOPT_ENCODING, "gzip, deflate");
		}

		if(isset($_para["method"]) && $_para["method"] == "post")
		{
			curl_setopt($ch, CURLOPT_POST, true);
			
			if(isset($_para["postdata"]))
			{
				//$postdata = $_para["postdata"];
				//if(is_array($postdata)) $postdata = http_build_query($postdata));
				curl_setopt($ch, CURLOPT_POSTFIELDS,$_para["postdata"]);
				//echo $_para["postdata"];die;
			}
	
			if(defined("DEBUG_MODE") && DEBUG_MODE && $_para["postdata"]) echo "postdata: ". print_r($_para["postdata"],true)." <br>\n";
		}
		
		$pagecontent = curl_exec($ch);
		$error_msg = curl_error($ch);		
		$curl_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if(defined("DEBUG_MODE") && DEBUG_MODE && $curl_code != 200){
			echo "warning: curl_code = $curl_code <br>\n";
			echo "Curl error: $error_msg\n";
		}
		
		if(isset($_para["stderr_temp"])){
			rewind($verbose);
			$verboseLog = stream_get_contents($verbose);
			//echo "\r\nVerbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
			fclose($verbose);
		}
		
		return array(
			"code" => $curl_code,
			"content" => $pagecontent,
			"error_msg" => $error_msg,
			"verbose" => isset($verboseLog) ? $verboseLog : "",
		);
	}

}//end class
?>