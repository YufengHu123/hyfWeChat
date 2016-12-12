<?php

class wechat
{
    private  $apiKey ;//百度api store 上面的apikey 这里仅仅用来测试来,和微信公众号开发没关系
    private  $wechatToken; //微信上预留token
    private $appid;//微信上的appid
    private $appsecret;//微信上的appsecret
    private $postStr;//传入的原始字符串
    private $postObj;//解析后
  //构造函数，从服务器里收到数据并解析
    function __construct()
    {
        //初始化所有私有变量
        $this->wechatToken = 'feng';
        $this->apiKey = '';
        $this->appid = '';
        $this->appsecret = '';
        $this->postStr = $GLOBALS["HTTP_RAW_POST_DATA"];//这里要根据环境自行配置
        $this->postObj = simplexml_load_string($this->postStr, 'SimpleXMLElement', LIBXML_NOCDATA); 
    }
   //判断收到的信息是哪些，分发给处理函数
    public function distribute()
    {
        //初次验证url
        if ( $this->VerifyToken() == $_GET['signature'] && $_GET['echostr']){
            header('content-type:text');
            echo $_GET['echostr'];
        }else{
            //调起自定义菜单函数
            $this->definedMenu();
            if (!empty($this->postStr))
            {
                $msgType = $this->postObj->MsgType;
                //分发 类型 文本回复 事件响应 地里位置
                switch ($msgType)
                {
                    case "text":
                        $this->text();
                        break;
                    case "location":
                        $this->location();
                        break;
                    case "event":
                        $this->eventType();
                        break;
                    default:
                        echo "未知的消息类别";
                        break;
                }
            } else {
                echo "无法得到返回值";
            }
        }
    }
    //初次接入验证url token 函数
    private function VerifyToken(){
            $timestamp = $_GET['timestamp'];
            $nonce = $_GET['nonce'];
            $array = array($timestamp,$nonce,$this->wechatToken);
            sort($array,SORT_STRING);
            $tmpstr = implode('',$array);
            $hyf = sha1($tmpstr);
            return $hyf;
    }
    //根据用户输入文字做出相应处理函数
    public function text()
    {
        $content = trim($this->postObj->Content);//去除用户发来信息的前后空格

        $contentItems = explode(':',$content);

        if ($contentItems[0] == '天气'){
            $this->getWeatherForecast($contentItems[1]);
        }else{
            switch ($content)
            {
                case "峰":
                    $contentStr = "你输入了峰";
                    $this->sendText($contentStr);//发送信息
                    break;
                case "123":
                    $contentStr ="别瞎输";
                    $this->sendText($contentStr);//发送信息
                    break;
                case "1":
                    $contentStr = '<a href="www.baidu.com">百度</a>';
                    $this->sendText($contentStr);//发送信息
                    break;
                case  "2":
                    //发送图文消息 模拟数据
                    $arr = array(
                        array(
                            'title'=>'测试标题习主席访美圆满成功回国,恭喜习主席恭喜习主席哈哈哈哈哈哈哈哈哈哈哈',
                            'description'=>'这个是个小说,are you ok??',
                            'picUrl'=>'http://hyfeng.applinzi.com/Public/images/1.jpg',
                            'url'=>'www.baidu.com',
                        ),
                        array(
                            'title'=>'测试标题习主席访美圆满成功回国,恭喜习主席恭喜习主席哈哈哈哈哈哈哈哈哈哈哈',
                            'description'=>'这个是个小说,are you ok??',
                            'picUrl'=>'http://hyfeng.applinzi.com/Public/images/1.jpg',
                            'url'=>'www.baidu.com',
                        ),
                        array(
                            'title'=>'测试标题习主席访美圆满成功回国,恭喜习主席恭喜习主席哈哈哈哈哈哈哈哈哈哈哈',
                            'description'=>'这个是个小说,are you ok??',
                            'picUrl'=>'http://hyfeng.applinzi.com/Public/images/1.jpg',
                            'url'=>'www.baidu.com',
                        ),
                    );
                    $this->SendSinglePicMsg($arr);
                    break;
                case  '天气':
                    $this->getWeatherForecast();
                    break;
                default:
                    //当不满足预设条件的时候返回默认的自动回复
                    $contentStr = "非法操作";
                    $this->sendText($contentStr);//发送信息
                    break;
            }
        }
    }
   //回复图文消息 最多不可以超过十个
    public  function SendSinglePicMsg($arr=array()){
        //不检查用户是否输入为空，如需检查请在text()中自行实现
        $fromUsername = $this->postObj->FromUserName;
        $toUsername = $this->postObj->ToUserName;
        $time = time();
        $template= '';
        $textTpl = "
        <xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[%s]]></MsgType>
        <ArticleCount>".count($arr)."</ArticleCount>
        <Articles>
        ";
        //循环拼接图文
            foreach ($arr as $k=>$value){
                    $template.= "<item>
                                <Title><![CDATA[".$value['title']."]]></Title>
                                <Description><![CDATA[".$value['description']."]]></Description>
                                <PicUrl><![CDATA[".$value['picUrl']."]]></PicUrl>
                                <Url><![CDATA[".$value['url']."]]></Url>
                                </item>";
                    }
                $template.="</Articles>
                            </xml>
                        ";

        $textTpl.=$template;
        $msgType = "news";//返回的数据类型
        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType);//格式化写入XML
        header("Content-type:text;charset=utf-8");
        echo $resultStr;//发送
    }
    //添加关注响应函数
    private function eventTypeOfSubscribe(){
        //示例，欢迎信息
        $contentStr = "您好！我是玉峰\n";
        $contentStr.= "欢迎关注我们的公众号:能否再见,虽然我TMD也感觉这名字很俗\n";
        $contentStr.= "我想多回复一些东西\n";
        $contentStr.= "但是不知道应该写点什么,这是最后一行！\n";
        $this->sendText($contentStr);
        return ;
    }
    //取消关注响应函数
    private function eventTypeofUnsubscribe(){
        //此响应函数无法回复用户信息
        return;
    }
    //地里位置改变响应事件
    private function eventTypeOfLocation(){
        return;
    }
    //底部菜单Click事件响应函数
    private function eventTypeOfClick(){
        switch ($this->postObj->EventKey){
            case  'item1':
                $this->sendText('点击了item1菜单');
                break;
            case  'item2';
                $this->sendText('点击了item2菜单');
                break;
            case  'item3';
                $this->sendText('点击了item3菜单');
                break;
            case  'item4';
                $this->sendText('点击了item4菜单');
                break;
            case  'item5';
                $this->sendText('点击了item5菜单');
                break;
        }
    }
    //底部菜单View事件响应函数
    private function eventTypeOfView(){
        //此事件直接跳转相应链接
        return;
    }
    //事件类型判断函数
    private function eventType()
    {
        switch (strtolower($this->postObj->Event)){
            case  'location':
                $this->eventTypeOfLocation();
                break;
            case  'click';
                $this->eventTypeOfClick();
                break;
            case  'view';
                $this->eventTypeOfView();
                break;
            case  'subscribe';
                $this->eventTypeOfSubscribe();
                break;
            case  'unsubscribe';
                $this->eventTypeofUnsubscribe();
                break;
            default:
                $this->sendText('非法事件');
                break;
        }
    }
    //发送纯文本消息函数负责被动文本信息的发送，传入$contentStr即可返回给微信服务器
    private function sendText($contentStr)
    {
        //不检查用户是否输入为空，如需检查请在text()中自行实现
        $fromUsername = $this->postObj->FromUserName;
        $toUsername = $this->postObj->ToUserName;
        $time = time();
        $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                    </xml>";
        $msgType = "text";//返回的数据类型
        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);//格式化写入XML
        echo $resultStr;//发送
    }
   //获取accesstoken
    private function  getWxAccessToken(){
        if ($_SESSION['access_token'] && $_SESSION['expire_time']>time()){
        //token
            return $_SESSION['access_token'];
        }else{
//            1.请求地址
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->appsecret;
            //2.初始化
            $ch= curl_init();
            //设置curl的参数
            curl_setopt($ch,CURLOPT_URL,$url);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            //调用接口
            $res = curl_exec($ch);
            //4.关闭
            curl_close($ch);
            if (curl_errno($ch)){
                dump(curl_error($ch));
            }
            //json转数组
            $arr = json_decode($res,true);
            //将获取到的token
            $_SESSION['access_token'] = $arr['access_token'];
            $_SESSION['expire_time']= time()+7000;
            return $arr['access_token'];
        }
    }
    //获取微信ip
    private function getWxServerIp(){
        $url = "https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=".$this->getWxAccessToken();
        //2.初始化
        $ch= curl_init();
        //设置curl的参数
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        //调用接口
        $res = curl_exec($ch);
        //4.关闭
        curl_close($ch);

        if (curl_errno($ch)){
            dump(curl_error($ch));
        }
        //json转数组
        $arr = json_decode($res,true);
        dump($arr);
    }
    //获取天气情况api,这里应用了百度的api stroe里面的接口天气预报接口,用于测试第三方sdk的引入
    private function getWeatherForecast($cityName ='北京'){
        $ch = curl_init();
        $url = 'http://apis.baidu.com/apistore/weatherservice/cityname?cityname='.urlencode($cityName);
        $header = array(
            'apikey:'.$this->apiKey,
        );
        // 添加apikey到header
        curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 执行HTTP请求
        curl_setopt($ch , CURLOPT_URL , $url);
        $res = curl_exec($ch);
        $arr = json_decode($res,true);

        if (count($arr["retData"]) == 0){
           $content = "查询失败,请检查输入关键字是否正确";
           $this->sendText($content);
            return;
        }
        dump($arr);

        $content = "查询地区:".$arr['retData']['city']."\n".
                   "天气情况:".$arr['retData']['weather']."\n".
                   "气温:".$arr['retData']['temp']."\n".
                   "最高气温:".$arr['retData']['h_tmp']."\n".
                   "最低气温:".$arr['retData']['l_tmp']."\n".
                   "风向:".$arr['retData']['WD']."\n".
                   "风力:".$arr['retData']['WS']."\n".
                   "日出时间:".$arr['retData']['sunrise']."\n".
                   "日出时间:".$arr['retData']['sunset']."\n".
                    "发布时间:".$arr['retData']['time']."\n";
        $this->sendText($content);
    }
   //封装数据请求函数
    private function  http_curl($url,$type='get',$res = 'json',$arr = ''){
        //获取curl
        $ch= curl_init();
        //设置curl的参数
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        if ($type == 'post'){
            curl_setopt($ch,CURLOPT_POST,1);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$arr);
        }
        //3.采集
        $output = curl_exec($ch);
        //4.关闭
        curl_close($ch);
        if ($res=='json'){
            if (curl_errno($ch)){
                return curl_error($ch);
            }else{
                return json_decode($output,true);
            }
        }
    }
    //自定义菜单函数
    private function definedMenu(){
        //创建微信菜单
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$this->getWxAccessToken();
        $postArr = array(
            "button"=> array(
                array(
                    'name'=>urlencode('菜单一'),
                    'type'=>'click',
                    'key'=>'item1',
                ),
                array(
                    'name'=>urlencode('菜单二'),
                    'sub_button'=>array(
                            array(
                                'name'=>urlencode('拍照'),
                                'type'=>'click',
                                 'key'=>'item2',
                            ),
                            array(
                                'name'=>urlencode('视频'),
                                'type'=>'view',
                                'url'=>'http://www.baidu.com',
                             ),
                    ),
                ),
                array(
                    'name'=>urlencode('菜单三'),
                    'type'=>'click',
                    'key'=>'item3',
                ),
            ),
        );

        $postJson = urldecode(json_encode($postArr));

        dump($postJson);

        dump($_SESSION['']);

        $res = $this->http_curl($url,'post','json',$postJson);


        dump($res);
    }
    //群发函数
    function sendMsgAll(){

        //1.获取全局access_token
        $access_token = $this->getWxAccessToken();
        //模拟群发接口数据 单图文
        $array = array(
            'touser'=>'',
            'mpnews'=>array(
                'media_id'=> '',
            ),
            'msgtype'=>'mpnews',
        );
//        //模拟群发接口数据 纯文本
//        $array = array(
//            'touser'=>'',
//            'text'=>array(
//                'content'=> '群发文本内容',
//                         ),
//            'msgtype'=>'text',
//        );
        //3.array转json

        $postJson = json_encode($array);
        //4.调用curl
        $url = 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token='.$access_token;
//        $res = $this->http_curl($url,'post','json',$postJson);
    }

}
?>