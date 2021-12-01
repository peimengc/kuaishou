<?php


namespace Peimengc\Kuaishou;


use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;

class Kuaishou
{
    protected $guzzleOptions = [];
    protected $middlewares = [];
    protected $handlerStack;
    protected $studioToken;

    public function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }

    public function setGuzzleOptions(array $options)
    {
        $this->guzzleOptions = $options;
        return $this;
    }

    public function getGuzzleOptions($key)
    {
        return $this->guzzleOptions[$key] ?? null;
    }

    public function pushMiddleware(callable $middleware, $name)
    {
        $this->middlewares[$name] = $middleware;
        return $this;
    }

    //发送请求
    protected function request($method, $uri, array $options = [], $raw = false)
    {
        if ($this->studioToken && strpos($uri, 'https://studio.kuaishou.com') !== false) {
            $options['headers']['Authorization'] = 'Bearer ' . $this->studioToken;
        }
        $options = array_merge($this->guzzleOptions, $options, ['handler' => $this->getHandlerStack()]);
        $response = $this->getHttpClient()->request($method, $uri, $options);
        if ($raw) return $response;
        return json_decode($response->getBody()->getContents(), true);
    }

    protected function httpPost($uri, $data = [])
    {
        return $this->request('POST', $uri, ['form_params' => $data]);
    }

    protected function httpGet($uri, $data = [])
    {
        return $this->request('GET', $uri, ['query' => $data]);
    }

    protected function httpPostJson($uri, $data = [])
    {
        return $this->request('POST', $uri, ['json' => $data]);
    }

    protected function getHandlerStack()
    {
        if ($this->handlerStack) {
            return $this->handlerStack;
        }

        $this->handlerStack = HandlerStack::create(new  CurlHandler());

        foreach ($this->middlewares as $name => $middleware) {
            $this->handlerStack->push($middleware, $name);
        }

        return $this->handlerStack;
    }

    //获取二维码
    public function qrStart($sid = 'kuaishou.server.web')
    {
        return $this->httpPost('https://id.kuaishou.com/rest/c/infra/ks/qr/start', compact('sid'));
    }

    //检测是否扫码
    public function qrScanResult($qrLoginToken, $qrLoginSignature)
    {
        return $this->httpPost('https://id.kuaishou.com/rest/c/infra/ks/qr/scanResult', compact('qrLoginSignature', 'qrLoginToken'));
    }

    //检测是否扫码
    public function qrAcceptResult($qrLoginToken, $qrLoginSignature, $sid = 'kuaishou.server.web')
    {
        return $this->httpPost('https://id.kuaishou.com/rest/c/infra/ks/qr/acceptResult', compact('qrLoginSignature', 'qrLoginToken', 'sid'));
    }

    //扫码回调 qrToken 换取 web_st
    public function qrCallback($qrToken, $sid = 'kuaishou.server.web')
    {
        return $this->httpPost('https://id.kuaishou.com/pass/kuaishou/login/qr/callback', compact('qrToken', 'sid'));
    }

    //获取authToken 云直播 kuaishou.live.platform
    public function loginPassToken($sid)
    {
        return $this->httpPost('https://id.kuaishou.com/pass/kuaishou/login/passToken', compact('sid'));
    }

    //云直播设置cookie
    public function studioSetCookie($authToken)
    {
        return $this->httpGet('https://studio.kuaishou.com/api/set-cookie', compact('authToken'));
    }

    //云直播token
    public function studioToken($serviceToken, $ksId)
    {
        $res = $this->httpPostJson('https://studio.kuaishou.com/api/auth/token', compact('ksId', 'serviceToken'));
        $this->setStudioToken($res['token']);
        return $res;
    }

    public function setStudioToken($token)
    {
        $this->studioToken = $token;
        return $this;
    }

    public function getStudioToken()
    {
        return $this->studioToken;
    }

    //云直播UserInfo
    public function studioUserInfo()
    {
        return $this->httpGet('https://studio.kuaishou.com/api/user/info');
    }

    //扫码后 cookie kuaishou.server.web,kuaishou.live.web
    public function sts($authToken, $sid = 'kuaishou.server.web')
    {
        return $this->httpGet('https://www.kuaishou.com/rest/infra/sts', compact('authToken', 'sid'));
    }

    //快手小店
    public function kwaiLoginPassToken($sid)
    {
        return $this->httpPost('https://id.kwaixiaodian.com/pass/kuaishou/login/passToken', compact('sid'));
    }

    //kuaishou.shop.b
    public function kwaists($authToken, $sid = 'kuaishou.shop.b')
    {
        return $this->httpGet('https://s.kwaixiaodian.com/rest/infra/sts', compact('authToken', 'sid'));
    }

    // 当前直播信息 商品列表,数据统计,直播信息等
    public function liveCurrent()
    {
        return $this->httpPost(' https://zs.kwaixiaodian.com/rest/pc/live/assistant/live/current');
    }

    //开始讲解
    public function kwaiShopCarRecordStart($liveStreamId, $itemId)
    {
        return $this->httpPostJson('https://zs.kwaixiaodian.com/rest/pc/live/assistant/shopCar/record/start', compact('liveStreamId', 'itemId'));
    }

    //结束讲解
    public function kwaiShopCarRecordEnd($liveStreamId, $itemId)
    {
        return $this->httpPostJson('https://zs.kwaixiaodian.com/rest/pc/live/assistant/shopCar/record/end', compact('liveStreamId', 'itemId'));
    }

    //扫码后 cookie kuaishou.web.cp.api
    public function jigousts($authToken, $sid = 'kuaishou.web.cp.api')
    {
        return $this->httpGet('https://jigou.kuaishou.com/rest/infra/sts', compact('authToken', 'sid'));
    }

    //扫码后 cookie kuaishou.web.cp.api
    public function cpsts($authToken, $sid = 'kuaishou.web.cp.api')
    {
        $followUrl = 'https://cp.kuaishou.com/profile';
        $setRootDomain = 'true';
        return $this->httpGet('https://cp.kuaishou.com/rest/infra/sts', compact('authToken', 'sid', 'followUrl', 'setRootDomain'));
    }

    //磁力金牛
    public function niusts($authToken, $sid = 'kuaishou.ad.esp')
    {
        return $this->httpGet('https://niu.e.kuaishou.com/rest/infra/sts', compact('authToken', 'sid'));
    }

    public function cpPhotoPushList($userId, $page = 1, $count = 10)
    {
        $url = 'https://cp.kuaishou.com/rest/pc/photo/push/list';
        return $this->httpPostJson($url, compact('userId', 'page', 'count'));
    }

    // cookie kuaishou.web.cp.api
    public function loginVerifyToken($authToken, $sid = 'kuaishou.web.cp.api')
    {
        $url = 'https://www.kuaishou.com/account/login/api/verifyToken';
        return $this->httpPostJson($url, compact('authToken', 'sid'));
    }

    //用户信息
    public function userInfo()
    {
        return $this->httpPostJson('https://www.kuaishou.com/graphql', [
            "operationName" => "userInfoQuery",
            "variables" => [],
            "query" => "query userInfoQuery {  visionOwnerInfo {    id    name    avatar    eid    userId    __typename  }}"
        ]);
    }

    //发送短信
    public function requestMobileCode($mobile)
    {
        return $this->appPost('/rest/n/user/requestMobileCode', [
            'mobileCountryCode' => '+86',
            'mobile' => $mobile,
            'type' => '27',
        ]);
    }

    // 短信认证
    public function mobileVerifyCode($mobile, $code)
    {
        return $this->appPost('rest/n/user/login/mobileVerifyCode', [
            'raw' => Carbon::now()->getTimestampMs(),
            'code' => $code,
            'mobileCountryCode' => '+86',
            'mobile' => $mobile,
            'type' => 27,
        ]);
    }

    // 多账号登录
    public function loginToken($loginType, $smsCode, $loginToken, $userId)
    {
        return $this->appPost('rest/n/user/login/token', [
            'loginType' => $loginType,
            'smsCode' => $smsCode,
            'loginToken' => $loginToken,
            'userId' => $userId,
            'giveUpAccountCancel' => 'false',
            'raw' => Carbon::now()->getTimestampMs(),
        ]);
    }

    //粉条钱包余额
    public function fansTopBalance()
    {
        return $this->httpPostJson('https://webapp.kuaishou.com/rest/w/fansTop/account/balance/listAll/New');
    }

    protected function appPost($uri, array $data = [])
    {
        $queryStr = 'mod=Netease%28MuMu%29&lon=116.397321&country_code=CN&kpn=KUAISHOU&oc=GDT_YUNMENGFEED%2C13&egid=DFP76EAEEEFF70B66F63985E4732E8819FD03B214B562391B362DD7B6D766E50&hotfix_ver=&sh=1440&appver=6.9.2.11245&max_memory=192&isp=&browseType=1&kpf=ANDROID_PHONE&did=ANDROID_a6d4eb245231e2fe&net=WIFI&app=0&ud=0&c=GDT_YUNMENGFEED%2C13&sys=ANDROID_6.0.1&sw=810&ftt=&language=zh-cn&iuid=&lat=39.908596&did_gt=1630203237261&ver=6.9';
        parse_str($queryStr, $query);
        $data = array_merge(['client_key' => '3c2cd3f3', 'os' => 'android'], $data);
        return $this->request('POST', $uri, ['form_params' => $data, 'query' => $query, 'base_uri' => 'https://apissl.gifshow.com']);
    }

    public function walletList($channel = 0, $rechargeSwitch = 1)
    {
        $uri = 'https://niu.e.kuaishou.com/rest/n/wallet/recharge/walletList';
        return $this->httpGet($uri, compact('channel', 'rechargeSwitch'));
    }

    public function niuOwnerInfo()
    {
        $uri = 'https://niu.e.kuaishou.com/rest/n/esp/web/owner/info';
        return $this->httpPostJson($uri);
    }

    //直播列表
    public function studioAllRooms($start, $end = null)
    {
        $end = $end ?: time() . 999;
        $uri = 'https://studio.kuaishou.com/api/live/all-rooms';
        return $this->httpGet($uri, compact('start', 'end'));
    }

    public function studioGetRooms($streamId)
    {
        $uri = 'https://studio.kuaishou.com/api/live/get-room';
        return $this->httpGet($uri, compact('streamId'));
    }

    public function niuLiveOrderCreate(array $data)
    {
        $uri = 'https://niu.e.kuaishou.com/rest/n/esp/web/order/live/create';
        return $this->httpPostJson($uri, $data);
    }

    public function niuPhotoList($userId, $pcursor = '', $count = 50)
    {
        $uri = 'https://niu.e.kuaishou.com/rest/n/esp/web/photo/list';
        return $this->httpPostJson($uri, compact('userId', 'pcursor', 'count'));
    }
}