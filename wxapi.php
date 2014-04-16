<?php
/*
PHP微信公众平台接口
接口基于腾讯微信官方SDK，文件做了一些简单的封装，植入了常用的一些功能。
1，快递查询
2，单词翻译
3，听歌
*/
//define your token
define("TOKEN", "molab");
$wechatObj = new wechatCallbackapiTest();
$wechatObj->responseMsg();
//$wechatObj->valid();

class wechatCallbackapiTest{
	public function valid(){
        $echoStr = $_GET["echostr"];
        //valid signature , option
        if($this->checkSignature()){
        	echo $echoStr;
        	exit;
        }
    }

    public function responseMsg(){
		//get post data, May be due to the different environments
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
		
      	//extract post data
		if (!empty($postStr)){
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			$RX_TYPE = trim($postObj->MsgType);
			
			switch($RX_TYPE){
				case 'text':
					$resultStr = $this->handleText($postObj);
					break;
				case 'image':
					$resultStr = $this->handleImage($postObj);
					break;
				case 'voice':
					$resultStr = $this->handleVoice($postObj);
					break;
				case 'video':
					$resultStr = $this->handleVideo($postObj);
					break;
				case 'location':
					$resultStr = $this->handleLocation($postObj);
					break;
				case 'event':
					$resultStr = $this->handleEvent($postObj);
					break;
				case 'link':
					$resultStr = $this->handleLink($postObj);
					break;
				default:
					$resultStr = "未知消息类型".$RX_TYPE;
					break;
			}
        }else{
        	echo "";
        	exit;
        }
    }
	
	public function replyType($type, $postObj){
		$fromUsername = $postObj->FromUserName;
		$toUsername = $postObj->ToUserName;
		$time = time();
		switch($type){
			case 'text':
				$tpl = "<xml>
					<ToUserName><![CDATA[{$fromUsername}]]></ToUserName>
					<FromUserName><![CDATA[{$toUsername}]]></FromUserName>
					<CreateTime>{$time}</CreateTime>
					<MsgType><![CDATA[{$type}]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					<FuncFlag>0</FuncFlag>
					</xml>";
				break;
			case 'image':
				$tpl = "<xml>
					<ToUserName><![CDATA[{$fromUsername}]]></ToUserName>
					<FromUserName><![CDATA[{$toUsername}]]></FromUserName>
					<CreateTime>{$time}</CreateTime>
					<MsgType><![CDATA[{$type}]]></MsgType>
					<Image>
						<MediaId><![CDATA[media_id]]></MediaId>
					</Image>
					</xml>";
				break;
			case 'voice':
				$tpl = "<xml>
					<ToUserName><![CDATA[{$fromUsername}]]></ToUserName>
					<FromUserName><![CDATA[{$toUsername}]]></FromUserName>
					<CreateTime>{$time}</CreateTime>
					<MsgType><![CDATA[{$type}]]></MsgType>
					<Voice>
						<MediaId><![CDATA[media_id]]></MediaId>
					</Voice>
					</xml>";
				break;
			case 'video':
				$tpl = "<xml>
					<ToUserName><![CDATA[{$fromUsername}]]></ToUserName>
					<FromUserName><![CDATA[{$toUsername}]]></FromUserName>
					<CreateTime>{$time}</CreateTime>
					<MsgType><![CDATA[{$type}]]></MsgType>
					<Video>
						<MediaId><![CDATA[media_id]]></MediaId>
						<Title><![CDATA[title]]></Title>
						<Description><![CDATA[description]]></Description>
					</Video> 
					</xml>";
				break;
			case 'music':
				$tpl = "<xml>
					<ToUserName><![CDATA[{$fromUsername}]]></ToUserName>
					<FromUserName><![CDATA[{$toUsername}]]></FromUserName>
					<CreateTime>{$time}</CreateTime>
					<MsgType><![CDATA[{$type}]]></MsgType>
					<Music>
						<Title><![CDATA[%s]]></Title>
						<Description><![CDATA[%s]]></Description>
						<MusicUrl><![CDATA[%s]]></MusicUrl>
						<HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
					</Music>
					</xml>";
				break;
			case 'news':
				$tpl = "<xml>
					<ToUserName><![CDATA[{$fromUsername}]]></ToUserName>
					<FromUserName><![CDATA[{$toUsername}]]></FromUserName>
					<CreateTime>{$time}</CreateTime>
					<MsgType><![CDATA[{$type}]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					<ArticleCount>1</ArticleCount>
					<Articles>
						<item>
							<Title><![CDATA[%s]]></Title>
							<Description><![CDATA[%s]]>
					</Description>
							<PicUrl><![CDATA[%s]]></PicUrl>
							<Url><![CDATA[%s]]></Url>
						</item>
					</Articles>
					<FuncFlag>0</FuncFlag>
					</xml>";
				break;
			default:
				$textTpl = "<xml>
					<ToUserName><![CDATA[{$fromUsername}]]></ToUserName>
					<FromUserName><![CDATA[{$toUsername}]]></FromUserName>
					<CreateTime>{$time}</CreateTime>
					<MsgType><![CDATA[{text}]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					<FuncFlag>0</FuncFlag>
					</xml>";
				break;
		}
		return $tpl;
	}
	
	public function handleText($postObj){
		$fromUsername = $postObj->FromUserName;
		$toUsername = $postObj->ToUserName;
		$keyword = trim($postObj->Content);
		$time = time();
		$textTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[%s]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					<FuncFlag>0</FuncFlag>
					</xml>";
		
		if(!empty( $keyword )){
			$msgType = "text";
			//substr($keyword,4)
			$strlen = strlen($keyword);
			if($strlen>6){
				$act = mb_strcut($keyword,0,6,'utf-8');//获取执行操作
				//$act = mb_substr($keyword,0,2,'utf-8');//截取开始两个字符
				$content = mb_strcut($keyword,6,$strlen,'utf-8');//获取执行内容
				if($act=="天气"){
					$data = $this->weather($content);
					if(empty($data->weatherinfo)){
						$contentStr = "抱歉，没有查到\"".$content."\"的天气信息！";
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					}else{
						$contentStr = "【".$data->weatherinfo->city."天气预报】\n".
						$data->weatherinfo->date_y." ".
						$data->weatherinfo->fchh."时发布"."\n实时天气:".
						$data->weatherinfo->weather1." ".
						$data->weatherinfo->temp1." ".
						$data->weatherinfo->wind1."\n温馨提示：".
						$data->weatherinfo->index_d."\n明天:".
						$data->weatherinfo->weather2." ".
						$data->weatherinfo->temp2." ".
						$data->weatherinfo->wind2."\n后天:".
						$data->weatherinfo->weather3." ".
						$data->weatherinfo->temp3." ".
						$data->weatherinfo->wind3;
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					}
					//$contentStr = "天气查询".$content;
				}else if($act=="快递"){
					$contentStr = "快递查询".$content;
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
				}else if($act=="翻译"){
					$contentStr = $this->baiduDict($content);
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
				}else if($act=="线路"){
					$contentStr = "线路查询".$content;
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
				}else{
					$contentStr = $act."的查询功能我们正在开发~敬请期待";
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
				}
			}else{
				if($keyword=="老夏" || $keyword=="夏露"){
					$newsTpl = $this->replyType('news',$postObj);
					$content = "Hey , girl! What\'s your name";
					$title = "没错，我就是老夏，如假包换的老夏！";
					$description = "你好，我是……，大家都叫我老夏，不妨你也这样称呼我吧！我是一个程序员，主要擅长LAMP。目前从事互联网行业，喜欢折腾新的东西……我有一个博客，记录我的生活、写着我的笔记，如果你有兴趣，你可以点击这里！";
					$picurl = "http://sanshu.qiniudn.com/pic_anymouse1359347919-1.jpg?token=5FZTA1Dfl7J2SbsAiSNwWusgvd1k10IMyKNY9b1G:rxOz8tgh0a_s9c8CLOCbW8Zycrk=:eyJTIjoic2Fuc2h1LnFpbml1ZG4uY29tL3BpY19hbnltb3VzZTEzNTkzNDc5MTktMS5qcGciLCJFIjoxMzk3NTQyMDEwfQ==&imageView/2/w/203/h/203";
					$url = "http://find.aliapp.com/Resume/";
					$resultStr = sprintf($newsTpl, $content, $title, $description, $picurl, $url, 0);
					echo $resultStr;
				}else if($keyword == "听歌"){
					$music = array(
						array(
							'title'=>'You are beautiful',
							'description'=>'You are beautiful',
							'url'=>'http://find.aliapp.com/Uploads/WeChat/Musics/James%20Blunt%20-%20You%20Are%20Beautiful.mp3',
							'hqurl'=>'http://find.aliapp.com/Uploads/WeChat/Musics/James%20Blunt%20-%20You%20Are%20Beautiful.mp3'
						),
						array(
							'title'=>'Jewel - Stand',
							'description'=>'Stand',
							'url'=>'http://find.aliapp.com/Uploads/WeChat/Musics/jewel%20-%20stand.mp3',
							'hqurl'=>'http://find.aliapp.com/Uploads/WeChat/Musics/jewel%20-%20stand.mp3'
						),
						array(
							'title'=>'TimeLess',
							'description'=>'Timeless',
							'url'=>'http://find.aliapp.com/Uploads/WeChat/Musics/Kelly%20Clarkson%20-%20Timeless.mp3',
							'hqurl'=>'http://find.aliapp.com/Uploads/WeChat/Musics/Kelly%20Clarkson%20-%20Timeless.mp3'
						),
						array(
							'title'=>'Because of you',
							'description'=>'Because of you',
							'url'=>'http://find.aliapp.com/Uploads/WeChat/Musics/kelly%20clarkson%20-%20because%20of%20you.mp3',
							'hqurl'=>'http://find.aliapp.com/Uploads/WeChat/Musics/kelly%20clarkson%20-%20because%20of%20you.mp3'
						),
						array(
							'title'=>'Every Moment of my life',
							'description'=>'Every Moment of my life',
							'url'=>'http://find.aliapp.com/Uploads/WeChat/Musics/Sarah%20Connor%20-%20Every%20Moment%20Of%20My%20Life.mp3',
							'hqurl'=>'http://find.aliapp.com/Uploads/WeChat/Musics/Sarah%20Connor%20-%20Every%20Moment%20Of%20My%20Life.mp3'
						),
						array(
							'title'=>'TimeLess',
							'description'=>'Timeless',
							'url'=>'http://find.aliapp.com/Uploads/WeChat/Musics/Kelly%20Clarkson%20-%20Timeless.mp3',
							'hqurl'=>'http://find.aliapp.com/Uploads/WeChat/Musics/Kelly%20Clarkson%20-%20Timeless.mp3'
						)
					);
					$musicTpl = $this->replyType("music",$postObj);
					$len = count($music);
					$rd = rand(0,$len-1);
					$resultStr = sprintf($musicTpl, $music[$rd]['title'],$music[$rd]['description'],$music[$rd]['url'],$music[$rd]['hqurl']);
				}else{
					$contentStr = "您的消息已经收到，我们将尽快给您答复，请稍安勿躁~\n\n您的原始消息:".$keyword."\n我正在积极成长当中，目前功能尚少，敬请原谅。现在，你可以发送'听歌'给我享受美妙的音乐啦";
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
				}
			}
			//$contentStr = "Welcome to wechat world!";
			echo $resultStr;
		}else{
			echo "啦，说点什么呗~";
		}
	}
	
	public function handleEvent($postObj){
		$content = "";
		switch($postObj->Event){
			case 'subscribe':
				$newsTpl = $this->replyType('news',$postObj);
				$content = "Hey , girl! What\'s your name";
				$title = "Ladies and 乡亲们，欢饮关注户外管家！";
				$description = "我是户外管家，您的贴身旅游小卫士！我们专长户外游，竭诚为您提供良好的户外游服务！约伴、路线、天气、订票、分享……都可以找我！";
				$picurl = "http://sanshu.qiniudn.com/pic_anymouse1359347919-1.jpg?token=5FZTA1Dfl7J2SbsAiSNwWusgvd1k10IMyKNY9b1G:rxOz8tgh0a_s9c8CLOCbW8Zycrk=:eyJTIjoic2Fuc2h1LnFpbml1ZG4uY29tL3BpY19hbnltb3VzZTEzNTkzNDc5MTktMS5qcGciLCJFIjoxMzk3NTQyMDEwfQ==&imageView/2/w/203/h/203";
				$url = "http://find.aliapp.com/Resume/";
				$resultStr = sprintf($newsTpl, $content, $title, $description, $picurl, $url, 0);
				echo $resultStr;
				break;
			case 'CLICK':
				$this->handleMenu($postObj);
				break;
			case 'VIEW':
				break;
			case 'LOCATION':
				break;
			default:
				$content = "未知事件".$postObj->Event;
				$textTpl = $this->replyType("text",$postObj);
				$resultStr = sprintf($textTpl, $content, 0);
				echo $resultStr;
				break;
		}
	}
	
	public function handleMenu($postObj){
		$EventKey = $postObj->EventKey;
		$content = "";
		switch($EventKey){
			case 'jiebanyou':
				$content = "你点击了结伴游";
				break;
			case 'fenxiang':
				$content = "你点击了旅途分享";
				break;
			case 'jiudian':
				$content = "你要订酒店";
				break;
			case 'jiaotong':
				$content = "你要查交通";
				break;
			case 'menpiao':
				$content = "门票预定";
				break;
			default:
				$content = "火星人";
				break;
		}
		$textTpl = $this->replyType("text",$postObj);
		$resultStr = sprintf($textTpl, $content, 0);
		echo $resultStr;
	}
	
	public function handleLocation($postObj){
		$newsTpl = $this->replyType('news',$postObj);
		$x = $postObj->Location_X;
		$y = $postObj->Location_Y;
		$s = $postObj->Scale;
		$l = $postObj->Label;
		$title = "你发送了一条地理信息";
		$description = "纬度：".$x."；\n经度：".$y."；\n缩放比例：".$s."；\n地址：".$l;
		$content = "描述";
		$picurl = "http://sanshu.qiniudn.com/pic59b1OOOPIC46.jpg?token=5FZTA1Dfl7J2SbsAiSNwWusgvd1k10IMyKNY9b1G:3tyD0k5p5-Hs_JH_p2Ram2j8lJc=:eyJTIjoic2Fuc2h1LnFpbml1ZG4uY29tL3BpYzU5YjFPT09QSUM0Ni5qcGciLCJFIjoxMzk3NjYyODQ4fQ==&imageView/2/w/203/h/203";
		//$url = "http://blog.molab.cn";
		//$url = "http://maps.google.com/maps?q=".$x.",".$y."&iwloc=A&hl=zh-CN";
		$url = "http://maps.google.com/maps?q=".$x.",".$y;
		$resultStr = sprintf($newsTpl, $content, $title, $description, $picurl, $url, 0);
		echo $resultStr;
	}
	
	private function weather($n){
		include("ProvinceList.php");
		$c_name = $city_id[$n];
		if(!empty($c_name)){
			$json = file_get_contents("http://m.weather.com.cn/data/".$c_name.".html");
			return json_decode($json);
		}else{
			return null;
		}
	}
	
	private function baiduDict($word,$from="auto",$to="auto"){
		$word_code = urlencode($word);
		$appid = "Rp0Y2XgqZCMENfW2qybiWY8t";
		$url = "http://openapi.baidu.com/public/2.0/bmt/translate?client_id=".$appid."&q=".$word_code."&from=".$from."&to=".$to;
		$text = json_decode($this->language_text($url));
		$text = $text->trans_result;
		return $text[0]->dst;
	}
	
	private function language_text($url){
		if(!function_exists('file_get_contents')){
            $file_contents = file_get_contents($url);
        }else{
            //初始化一个cURL对象
            $ch = curl_init();
            $timeout = 5;
            //设置需要抓取的URL
            curl_setopt ($ch, CURLOPT_URL, $url);
            //设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
            //在发起连接前等待的时间，如果设置为0，则无限等待
            curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            //运行cURL，请求网页
            $file_contents = curl_exec($ch);
            //关闭URL请求
            curl_close($ch);
        }
        return $file_contents;
	}
		
	private function checkSignature(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}
?>
