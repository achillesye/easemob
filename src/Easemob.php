<?php
namespace Easemob\Chat;

use Illuminate\Support\Facades\Cache;
use Exception;
/**
 *
 * Date: 16/6/7
 * Author: eric <eric@winhu.com>
 */
class Easemob
{
    private static $config = null;

    public function __construct()
    {
        self::initConfig();
    }

    public function test()
    {
        return self::getToken();
    }

    /**
     * 加载配指文件
     *
     * @return mixed|null
     */
    private static function initConfig()
    {
        if (!isset(self::$config)) {
            self::$config = config('easemob');
        }
        return self::$config;
    }

    /**
     * 获取APP 的 client_id
     *
     * @return string
     */
    private static function getClientId()
    {
        if (isset(self::$config['client_id'])) {
            return self::$config['client_id'];
        }
        return '';
    }

    /**
     * 获取APP 的 client_secret
     *
     * @return string
     */
    private static function getClientSecret()
    {
        if (isset(self::$config['client_secret'])) {
            return self::$config['client_secret'];
        }
        return '';
    }

    /**
     * 获取企业唯一标示
     *
     * @return string
     */
    private static function getOrgName()
    {
        if (isset(self::$config['org_name'])) {
            return self::$config['org_name'];
        }
        return '';
    }

    /**
     * 获取应用名字
     *
     * @return string
     */
    private static function getAppName()
    {
        if (isset(self::$config['app_name'])) {
            return self::$config['app_name'];
        }
        return '';
    }

    /**
     * 获取错误描述
     *
     * @return string
     */
    private static function getErrorMessage()
    {
        if (isset(self::$config['error_message'])) {
            return self::$config['error_message'];
        }
        return '';
    }
    /**
     * 获取服务url
     *
     * @return string
     */
    private static function getUrl()
    {

        if (isset(self::$config['easemob_url'])) {
            return self::$config['easemob_url'] . self::getOrgName() . '/' . self::getAppName() . '/';
        }
        return '';
    }

    /**
     * 从服务器端获取token
     *
     * @return mixed
     */
    public function getRemoteToken()
    {
        $options = array(
            "grant_type" => "client_credentials",
            "client_id" => self::getClientId(),
            "client_secret" => self::getClientSecret()
        );
        //json_encode()函数，可将PHP数组或对象转成json字符串，使用json_decode()函数，可以将json字符串转换为PHP数组或对象
        $body = json_encode($options);
        //设置请求地址
        $url = self::getUrl() . 'token';

        $tokenResult = $this->apiRequest($url, $body, $header = array());

        return $tokenResult;
    }

    /**
     * 缓存token
     *
     * @return string
     */
    private function setTokenCache()
    {
        $token_cache = self::$config['token_cache'];
        $token = '';
        switch ($token_cache) {
            case 'redis':
                $token_prefix = self::$config['cache_set']['redis']['prefix'];
                $minutes = self::$config['cache_set']['redis']['time'];
                $token = Cache::tags('easemob_token')->remember($token_prefix, $minutes, function () {
                    return $this->getRemoteToken();
                });
                break;
            case 'file':
                break;
            default:
                break;

        }
        return $token;

    }

    /**
     * 获取token
     *
     * @return string
     */
    public function getToken()
    {
        $token = self::setTokenCache();

        return "Authorization:Bearer " . $token['access_token'];
    }
    //-------------------------用户操作-----------------------------//

    /**
     * 创建授权用户
     *
     * @param $username
     * @param $password
     * @return mixed
     */
    public function createUser($username, $password, $nickname = '')
    {
        $url = self::getUrl() . 'users';
        $options = array(
            "username" => $username,
            "password" => $password
        );
        if (!empty($nickname)) {
            $options['nickname'] = $nickname;
        }
        $body = json_encode($options);
        $header = array($this->getToken());
        $result = $this->apiRequest($url, $body, $header);

        return $this->returnResult($result);
    }

    /**
     * 批量授权注册用户
     *
     * @param $options
     * @return mixed
     */
    public function createUsers($options)
    {
        $url = self::getUrl() . 'users';

        $body = json_encode($options);
        $header = array($this->getToken());
        $result = $this->apiRequest($url, $body, $header);

        return $this->returnResult($result);
    }

    /**
     * 获取单个用户
     *
     * @param $username
     * @return mixed
     */
    public function getUser($username)
    {
        $url = self::getUrl() . 'users/' . $username;
        $header = array($this->getToken());
        $result = $this->apiRequest($url, '', $header, "GET");

        return $this->returnResult($result);
    }

    /**
     * 批量获取用户不分页
     *
     * @param int $limit
     * @return mixed
     */
    function getUsers($limit = null)
    {
        if (!empty($limit)) {
            $url = self::getUrl() . 'users?limit=' . $limit;
        } else {
            $url = self::getUrl() . 'users';
        }
        $header = array($this->getToken());
        $result = $this->apiRequest($url, '', $header, "GET");

        return $this->returnResult($result);
    }

    /**
     * 批量获取用户,分页处理
     *
     * @param int $limit
     * @param string $cursor
     * @return mixed
     */
    public function getUsersForPage($limit = 1, $cursor = '')
    {
        $url = self::getUrl() . 'users?limit=' . $limit . '&cursor=' . $cursor;

        $header = array($this->getToken());
        $result = $this->apiRequest($url, '', $header, "GET");
//        if(!empty($result["cursor"])){
//            $cursor=$result["cursor"];
////            $this->writeCursor("userfile.txt",$cursor);
//        }
        return $this->returnResult($result);
    }

    /**
     * 删除单个用户
     *
     * @param $username
     * @return mixed
     */
    public function deleteUser($username)
    {
        $url = self::getUrl() . 'users/' . $username;
        $header = array($this->getToken());
        $result = $this->apiRequest($url, '', $header, 'DELETE');

        return $this->returnResult($result);
    }

    /**
     * 批量删除 N 个用户
     *
     * 这里只是批量的一次性删除掉 N个用户，具体删除哪些并没有指定，可以在返回值中查看到哪些用户被删除掉了
     * @param int $limit
     * @return mixed
     */
    function deleteUsers($limit = 10)
    {
        $url = self::getUrl() . 'users?limit=' . $limit;
        $header = array($this->getToken());
        $result = $this->apiRequest($url, '', $header, 'DELETE');

        return $this->returnResult($result);

    }

    /**
     * 重置用户密码
     *
     * @param $username
     * @param $password
     * @return mixed
     */
    public function resetUserPassword($username, $password)
    {
        $url = self::getUrl() . 'users/' . $username . '/password';
        $header = array($this->getToken());
        $options = [
            'newpassword' => $password,
        ];
        $body = json_encode($options);

        $result = $this->apiRequest($url, $body, $header, 'PUT');

        return $this->returnResult($result);
    }

    /**
     * 编辑用户昵称
     *
     * @param $username
     * @param $nickname
     * @return mixed
     */
    public function editNickname($username, $nickname)
    {
        $url = self::getUrl() . 'users/' . $username;
        $options = array(
            "nickname" => $nickname
        );
        $body = json_encode($options);
        $header = array($this->getToken());
        $result = $this->apiRequest($url, $body, $header, 'PUT');

        return $this->returnResult($result);
    }

    /**
     * 查看用户是否在线
     *
     * @param $username
     * @return mixed
     */
    public function isOnline($username)
    {
        $url = self::getUrl() . 'users/' . $username . '/status';
        $header = array($this->getToken());
        $result = $this->apiRequest($url, '', $header, 'GET');

        return $this->returnResult($result);

    }

    /**
     * 禁用用户账户
     *
     * @param $username
     * @return mixed
     */
    public function deactiveUser($username)
    {
        $url = self::getUrl() . 'users/' . $username . '/deactivate';
        $header = array($this->getToken());
        $result = $this->apiRequest($url, '', $header);

        return $this->returnResult($result);
    }

    /**
     * 解禁用户账号
     *
     * @param $username
     * @return mixed
     */
    public function activeUser($username)
    {
        $url = self::getUrl() . 'users/' . $username . '/activate';
        $header = array($this->getToken());
        $result = $this->apiRequest($url, '', $header);

        return $this->returnResult($result);
    }

    /**
     * 强制用户下线
     *
     * @param $username
     * @return mixed
     */
    public function disconnectUser($username)
    {
        $url = self::getUrl() . 'users/' . $username . '/disconnect';
        $header = array($this->getToken());
        $result = $this->apiRequest($url, '', $header, 'GET');

        return $this->returnResult($result);
    }

    /**
     * 查看用户离线消息数
     *
     * @param $username
     * @return mixed
     */
    public function getOfflineMessagesNum($username)
    {
        $url = self::getUrl() . 'users/' . $username . '/offline_msg_count';
        $header = array($this->getToken());
        $result = $this->apiRequest($url, '', $header, 'GET');

        return $this->returnResult($result);

    }
    public function getOfflineMessagesStatus($username, $msg_id)
    {
        $url = self::getUrl() . 'users/' . $username . '/offline_msg_status/'.$msg_id;
        $header = array($this->getToken(),'Content-Type:application/json');
        $result = $this->apiRequest($url, '', $header, 'GET');

        return $this->returnResult($result);

    }
    //-------------------------发送消息操作-----------------------------//

    /**
     * 上传语音/图片文件
     *
     * 上传文件大小不能超过 10M，超过会上传失败
     * @param $filePath
     * @return mixed
     */
    public function uploadFile($filePath)
    {
        $url = self::getUrl() . 'chatfiles';
        $file = file_get_contents($filePath);
        $body['file'] = $file;
        $header = array('enctype:multipart/form-data', $this->getToken(), "restrict-access:true");
        $result = $this->apiRequest($url, $body, $header, 'XXX');

        return $this->returnResult($result);
    }

    /**
     * 下载语音/图片文件
     *
     * @param $uuid
     * @param $shareSecret
     * @return string
     */
    public function downloadFile($uuid, $shareSecret)
    {
        $url = self::getUrl() . 'chatfiles/' . $uuid;
        $header = array("share-secret:" . $shareSecret, "Accept:application/octet-stream", $this->getToken());
        $result = $this->apiRequest($url, '', $header, 'GET');
        $filename = md5(time() . mt_rand(10, 99)) . ".png"; //新图片名称
        if (!file_exists("resource/down")) {
            mkdirs("resource/down/");
        }

        $file = @fopen("resource/down/" . $filename, "w+");//打开文件准备写入
        @fwrite($file, $result);//写入
        fclose($file);//关闭

        return $this->returnResult($result);
    }

    /**
     * 下载缩略图
     *
     * @param $uuid
     * @param $shareSecret
     * @return string
     */
    function downloadThumbnail($uuid, $shareSecret)
    {
        $url = self::getUrl() . 'chatfiles/' . $uuid;
        $header = array("share-secret:" . $shareSecret, "Accept:application/octet-stream", $this->getToken(), "thumbnail:true");
        $result = $this->apiRequest($url, '', $header, 'GET');
        $filename = md5(time() . mt_rand(10, 99)) . "th.png"; //新图片名称
        if (!file_exists(self::$config['download_file_path'])) {
            mkdirs(self::$config['download_file_path']);
        }

        $file = @fopen(self::$config['download_file_path'] . $filename, "w+");//打开文件准备写入
        @fwrite($file, $result);//写入
        fclose($file);//关闭

        return $this->returnResult($result);
    }

    /**
     * 发送文本消息
     *
     * @param string $from
     * @param $target_type [users 给用户发消息。chatgroups: 给群发消息，chatrooms: 给聊天室发消息]
     * @param $target [消息接收对象 这里需要用数组]
     * @param $content [发送的消息]
     * @param string $ext [扩展字段]
     * @return mixed
     */
    public function sendText($from = "admin", $target_type, $target, $content, $ext = '')
    {
        $url = self::getUrl() . 'messages';
        $body['target_type'] = $target_type;
        $body['target'] = $target;
        $options['type'] = "txt";
        $options['msg'] = $content;
        $body['msg'] = $options;
        $body['from'] = $from;
        $body['ext'] = $ext;
        $body_json = json_encode($body);
        $header = array($this->getToken());
        $result = $this->apiRequest($url, $body_json, $header);

        return $this->returnResult($result);
    }

    /**
     * 发送透传消息
     *
     * @param string $from
     * @param $target_type
     * @param $target
     * @param $action
     * @param $ext
     * @return mixed
     */
    public function sendCmd($from = "admin", $target_type, $target, $action, $ext)
    {
        $url = self::getUrl() . 'messages';

        $body['target_type'] = $target_type;
        $body['target'] = $target;
        $options['type'] = "cmd";
        $options['action'] = $action;
        $body['msg'] = $options;
        $body['from'] = $from;
        $body['ext'] = $ext;

        $body_json = json_encode($body);
        $header = array($this->getToken());
        $result = $this->apiRequest($url, $body_json, $header);

        return $this->returnResult($result);
    }

    /**
     * 发送图片消息
     *
     * @param $filePath
     * @param string $from
     * @param $target_type
     * @param $target
     * @param $filename
     * @param string $ext
     * @return mixed
     */
    public function sendImage($filePath, $from = "admin", $target_type, $target, $filename, $ext = '')
    {
        $result = $this->uploadFile($filePath);
        $uri = $result['uri'];
        $uuid = $result['entities'][0]['uuid'];
        $shareSecret = $result['entities'][0]['share-secret'];

        $url = self::getUrl() . 'messages';
        $body['target_type'] = $target_type;
        $body['target'] = $target;
        $options['type'] = "img";
        // 成功上传文件返回的UUID
        $options['url'] = $uri . '/' . $uuid;
        $options['filename'] = $filename;
        // 成功上传文件后返回的secret
        $options['secret'] = $shareSecret;
        $options['size'] = array(
            "width" => 480,
            "height" => 720
        );
        $body['msg'] = $options;
        $body['from'] = $from;
        $body['ext'] = $ext;

        $body_json = json_encode($body);
        $header = array($this->getToken());
        $result = $this->apiRequest($url, $body_json, $header);

        return $this->returnResult($result);
    }

    /**
     * 发送语音消息
     *
     * @param $filePath
     * @param string $from
     * @param $target_type
     * @param $target
     * @param $filename
     * @param $length
     * @param string $ext
     * @return mixed
     */
    public function sendAudio($filePath, $from = "admin", $target_type, $target, $filename, $length, $ext = '')
    {
        $result = $this->uploadFile($filePath);
        $uri = $result['uri'];
        $uuid = $result['entities'][0]['uuid'];
        $shareSecret = $result['entities'][0]['share-secret'];

        $url = self::getUrl() . 'messages';
        $body['target_type'] = $target_type;
        $body['target'] = $target;
        $options['type'] = "audio";
        $options['url'] = $uri . '/' . $uuid;
        $options['filename'] = $filename;
        $options['length'] = $length;
        $options['secret'] = $shareSecret;
        $body['msg'] = $options;
        $body['from'] = $from;
        $body['ext'] = $ext;

        $body_json = json_encode($body);
        $header = array($this->getToken());

        $result = $this->apiRequest($url, $body_json, $header);

        return $this->returnResult($result);
    }

    /**
     * 发送视频消息
     *
     * @param $filePath
     * @param string $from
     * @param $target_type
     * @param $target
     * @param $filename
     * @param $length
     * @param $thumb
     * @param $thumb_secret
     * @param string $ext
     * @return mixed
     */
    public function sendVedio($filePath, $from = "admin", $target_type, $target, $filename, $length, $thumb, $thumb_secret, $ext = '')
    {
        $result = $this->uploadFile($filePath);
        $uri = $result['uri'];
        $uuid = $result['entities'][0]['uuid'];
        $shareSecret = $result['entities'][0]['share-secret'];

        $url = self::getUrl() . 'messages';
        $body['target_type'] = $target_type;
        $body['target'] = $target;
        $options['type'] = "video";
        $options['url'] = $uri . '/' . $uuid;
        $options['filename'] = $filename;
        //成功上传视频缩略图返回的UUID
        $options['thumb'] = $thumb;
        //视频播放长度
        $options['length'] = $length;
        $options['secret'] = $shareSecret;
        //成功上传视频缩略图后返回的secret
        $options['thumb_secret'] = $thumb_secret;
        $body['msg'] = $options;
        $body['from'] = $from;
        $body['ext'] = $ext;

        $body_json = json_encode($body);
        $header = array($this->getToken());

        $result = $this->apiRequest($url, $body_json, $header);

        return $this->returnResult($result);
    }
    //-------------------------聊天室操作-----------------------------//
    /**
     * 创建聊天室
     *
     * @param $options
     * @options[name] 聊天室名称，此属性为必须的
     * @options[description] 聊天室描述，此属性为必须的
     * @options[maxusers] 聊天室成员最大数（包括群主），值为数值类型，默认值200，此属性为可选的
     * @options[owner] 聊天室的管理员，此属性为必须的
     * @options[members] 聊天室成员，此属性为可选的，但是如果加了此项，数组元素至少一个（注：群主 jma1 不需要写入到 members 里面
     * @return mixed
     */
    public function createChatRoom($options)
    {
        $url = self::getUrl() . 'chatrooms';
        $header = array($this->getToken());
        $body = json_encode($options);
        $result = $this->apiRequest($url, $body, $header);

        return $this->returnResult($result);
    }

    /**
     * 修改聊天室信息
     *
     * @param $chatroom_id
     * @param $options
     * @return mixed
     */
    public function modifyChatRoom($chatroom_id, $options)
    {
        $url = self::getUrl() . 'chatrooms/' . $chatroom_id;
        $header = array($this->getToken());
        $body = json_encode($options);
        $result = $this->apiRequest($url, $body, $header, 'PUT');

        return $this->returnResult($result);
    }

    /*
     * 删除聊天室
     *
     */
    public function deleteChatRoom($chatroom_id)
    {
        $url = self::getUrl() . 'chatrooms/' . $chatroom_id;
        $header = array($this->getToken());
        $result = $this->apiRequest($url, '', $header, 'DELETE');

        return $this->returnResult($result);
    }

    /**
     * 获取app中所有的聊天室
     *
     * @return mixed
     */
    public function getChatRooms()
    {
        $url = self::getUrl() . 'chatrooms';
        $header = array($this->getToken());
        $result = $this->apiRequest($url, '', $header, "GET");

        return $this->returnResult($result);
    }

    /**
     * 获取一个聊天室的详情
     *
     * @param $chatroom_id
     * @return mixed
     */
    public function getChatRoomDetail($chatroom_id)
    {
        $url = self::getUrl() . 'chatrooms/' . $chatroom_id;
        $header = array($this->getToken());
        $result = $this->apiRequest($url, '', $header, 'GET');
        return $this->returnResult($result);
    }

    /**
     * 获取一个用户加入的所有聊天室
     *
     * @param $username
     * @return mixed
     */
    function getChatRoomJoined($username)
    {
        $url = self::getUrl() . 'users/' . $username . '/joined_chatrooms';
        $header = array($this->getToken());
        $result = $this->apiRequest($url, '', $header, 'GET');

        return $this->returnResult($result);
    }

    /**
     * 聊天室单个成员添加
     *
     * @param $chatroom_id
     * @param $username
     * @return mixed
     */
    function addChatRoomMember($chatroom_id, $username)
    {
        $url = self::getUrl() . 'chatrooms/' . $chatroom_id . '/users/' . $username;
        $header = array($this->getToken(), 'Content-Type:application/json');
        $result = $this->apiRequest($url, '', $header);

        return $this->returnResult($result);
    }

    /**
     * 聊天室批量成员添加
     *
     * @param $chatroom_id
     * @param $usernames
     * @return mixed
     */
    function addChatRoomMembers($chatroom_id, $usernames)
    {
        $url = self::getUrl() . 'chatrooms/' . $chatroom_id . '/users';
        $body = json_encode($usernames);
        $header = array($this->getToken());
        $result = $this->apiRequest($url, $body, $header);

        return $this->returnResult($result);
    }

    /**
     * 聊天室单个成员删除
     *
     * @param $chatroom_id
     * @param $username
     * @return mixed
     */
    function deleteChatRoomMember($chatroom_id, $username)
    {
        $url = self::getUrl() . 'chatrooms/' . $chatroom_id . '/users/' . $username;
        $header = array($this->getToken());
        $result = $this->apiRequest($url, '', $header, 'DELETE');

        return $this->returnResult($result);
    }

    /**
     * 聊天室批量成员删除
     *
     * @param $chatroom_id
     * @param $usernames
     * @return mixed
     */
    function deleteChatRoomMembers($chatroom_id, $usernames)
    {
        $url = self::getUrl() . 'chatrooms/' . $chatroom_id . '/users/' . $usernames;
        $header = array($this->getToken());
        $result = $this->apiRequest($url, '', $header, 'DELETE');

        return $this->returnResult($result);
    }

    /**
     * 获取聊天记录
     *
     * 在URL后面加上参数,根据 timestamp 查询聊天记录
     * 只取最近的消息可以只用 timestamp>1403166586000
     * @param null $ql
     * @return mixed
     */
    public function getChatRecord($ql = null){
        if(!empty($ql)){
            $url = self::getUrl().'chatmessages?ql='.$ql;
        }else{
            $url = self::getUrl().'chatmessages';
        }
        $header = array($this->getToken());
        $result = $this->apiRequest($url,'',$header,"GET");

        return $this->returnResult($result);
    }

    /**
     * 分页获取记录
     *
     * @param $ql
     * @param int $limit
     * @param string $cursor
     * @return mixed
     */
    function getChatRecordForPage($ql,$limit=0,$cursor=''){
        if(!empty($ql)){
            $url = self::getUrl().'chatmessages?ql='.$ql.'&limit='.$limit.'&cursor='.$cursor;
        }
        $header =array($this->getToken());
        $result = $this->apiRequest($url,'',$header,"GET");
        $cursor = $result["cursor"];
//        $this->writeCursor("chatfile.txt",$cursor);
        return $this->returnResult($result);
    }

    /**
     * 判断处理结果
     *
     * @param $result
     * @return mixed
     * @throws Exception
     */
    private static function returnResult($result)
    {
        if (isset($result['error'])) {
            $error_info = self::getErrorMessage();
            $error = $result['error'];
            if (array_key_exists($error, $error_info)) {
                if (is_array($error_info[$error])) {
                    $error_description = $result['error_description'];
                    foreach ($error_info[$error] as $k => $v) {
                        if (stripos($error_description, $k)) {
                            throw new Exception($v);
                        }
                    }
                } else {
                    throw new Exception($error_info[$error]);
                }
            }
            throw new Exception('操作失败,'.$result['error']);
        }

        return $result;
    }
    /**
     * 发送API请求
     *
     * @param $url
     * @param $body
     * @param $header
     * @param string $type
     * @return mixed
     */
    private function apiRequest($url, $body, $header, $type = "POST")
    {
        //1.创建一个curl资源
        $ch = curl_init();
        //2.设置URL和相应的选项
        curl_setopt($ch, CURLOPT_URL, $url);//设置url
        //1)设置请求头
        //array_push($header, 'Accept:application/json');
        //array_push($header,'Content-Type:application/json');
        //array_push($header, 'http:multipart/form-data');
        //设置为false,只会获得响应的正文(true的话会连响应头一并获取到)
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 设置超时限制防止死循环
        //设置发起连接前的等待时间，如果设置为0，则无限等待。
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        //将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //2)设备请求体
        if (count($body) > 0) {
            //$b=json_encode($body,true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);//全部数据使用HTTP协议中的"POST"操作来发送。
        }
        //设置请求头
        if (count($header) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        //上传文件相关设置
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);// 从证书中检查SSL加密算

        //3)设置提交方式
        switch ($type) {
            case "GET":
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case "POST":
                curl_setopt($ch, CURLOPT_POST, true);
                break;
            case "PUT"://使用一个自定义的请求信息来代替"GET"或"HEAD"作为HTTP请求。这对于执行"DELETE" 或者其他更隐蔽的HTT
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                break;
            case "DELETE":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
        }

        //4)在HTTP请求中包含一个"User-Agent: "头的字符串。-----必设
        curl_setopt($ch, CURLOPT_USERAGENT, 'SSTS Browser/1.0');
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        //5) 模拟用户使用的浏览器
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)');
        //3.抓取URL并把它传递给浏览器
        $res = curl_exec($ch);

        $result = json_decode($res, true);
        //4.关闭curl资源，并且释放系统资源
        curl_close($ch);

        if (empty($result))
            return $res;
        else
            return $result;

    }
}
