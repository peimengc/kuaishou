<?php


namespace Peimengc\Kuaishou;


use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
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
    public function zsLiveCurrent()
    {
        return $this->zspost('https://zs.kwaixiaodian.com/rest/pc/live/assistant/live/current');
    }

    //开始讲解
    public function zsShopCarRecordStart($liveStreamId, $itemId)
    {
        return $this->zspost('https://zs.kwaixiaodian.com/rest/pc/live/assistant/shopCar/record/start', compact('liveStreamId', 'itemId'));
    }

    //结束讲解
    public function zsShopCarRecordEnd($liveStreamId, $itemId)
    {
        return $this->zspost('https://zs.kwaixiaodian.com/rest/pc/live/assistant/shopCar/record/end', compact('liveStreamId', 'itemId'));
    }

    public function zspost($uri, $data = [])
    {
        return $this->request('POST', $uri, [
            'json' => $data,
            'headers' => [
                'Origin' => 'https://zs.kwaixiaodian.com'
            ]
        ]);
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

    //直播间投放时作品列表
    public function fansTopAdvancedOrder($liveStreamId, string $pcursor = '')
    {
        return $this->httpPostJson('https://pages.kuaishou.com/rest/k/live/fansTop/advanced/order', [
            "liveStreamId" => $liveStreamId,
            "pcursor" => $pcursor,
        ]);
    }

    /**
     * 创建订单
     * {"result":4007,"error_msg":"直播关闭不能创建订单"}
     * {"result":400,"error_msg":"userIntentionType参数不合法"}
     * 返回信息包含 merchantId 的需要支付操作 一般都是块币支付时才会有
     * [{"data": {"userId": **, "ksOrderId": "**", "needRetry": false, "merchantId": "**"}, "domain": "fanstop", "result": 1, "message": "成功"}]
     * {"data":{"userId":**,"ksOrderId":"**","needRetry":false,"merchantId":""},"domain":"fanstop","result":1,"message":"成功"}
     */
    public function fansTopOrderCreateNew(array $data)
    {
        return $this->httpPostJson('https://pages.kuaishou.com/rest/k/live/fansTop/order/create/new', $data);
    }

    //根据merchantId获取交易额
    public function appTradeCashier($merchantId, $ksOrderId)
    {
        return $this->httpPost('https://www.kuaishoupay.com/pay/order/app/trade/cashier', [
            'merchant_id' => $merchantId,
            'out_order_no' => $ksOrderId,
            'extra' => '',
            'is_install_wechat' => 'false',
            'is_install_alipay' => 'false',
            'is_install_union_pay' => 'false',
            'is_install_wechat_sdk' => 'true',
            'is_install_alipay_sdk' => 'true',
            'is_install_union_pay_sdk' => 'false',
            'retry_times' => '0',
        ]);
    }

    //支付
    public function appTradeCreatePayOrder($merchantId, $ksOrderId, $payAmount)
    {
        /**
         * @var $cookieJar CookieJar
         */
        $cookieJar = $this->getGuzzleOptions('cookies');
        $cookie = $cookieJar->getCookieByName('kspay_csrf_token');
        $kspay_csrf_token = $cookie ? $cookie->getValue() : '';
        return $this->httpPost('https://www.kuaishoupay.com/pay/order/app/trade/create_pay_order', [
            'merchant_id' => $merchantId,
            'out_order_no' => $ksOrderId,
            'provider' => 'KSCOIN',
            'payment_method' => 'IN_APP',
            'provider_channel_type' => 'NORMAL',
            'provider_pay_amount' => $payAmount,
            'kspay_csrf_token' => $kspay_csrf_token,
            'fq_stage' => '',
        ]);
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

    //创建磁力金牛订单
    public function niuLiveOrderCreate(array $data)
    {
        $uri = 'https://niu.e.kuaishou.com/rest/n/esp/web/order/live/create';
        return $this->httpPostJson($uri, $data);
    }

    //作品列表
    public function niuPhotoList($userId, $pcursor = '', $count = 50)
    {
        $uri = 'https://niu.e.kuaishou.com/rest/n/esp/web/photo/list';
        return $this->httpPostJson($uri, compact('userId', 'pcursor', 'count'));
    }

    //订单列表 0 全部 4推广中 2审核中 1待付款 5已完成
    public function niuOrderList($cursor = '', $promotionStatus = 0, $pageSize = 20, $promotionType = 2)
    {
        $uri = 'https://niu.e.kuaishou.com/rest/n/esp/web/order/v2/list';
        return $this->httpPostJson($uri, compact('promotionStatus', 'cursor', 'pageSize', 'promotionType'));
    }

    //订单列表 $helpBuyType1自买0帮买
    public function niuLiveOrderList($liveStreamId, $helpBuyType = 1, $orderId = 0)
    {
        $uri = 'https://niu.e.kuaishou.com/rest/n/esp/web/order/v2/live/list';
        return $this->httpPostJson($uri, compact('liveStreamId', 'helpBuyType', 'orderId'));
    }

    //订单详情
    public function niuLiveOrderDetail($orderId)
    {
        $uri = 'https://niu.e.kuaishou.com/rest/n/esp/web/order/detail';
        return $this->httpPostJson($uri, compact('orderId'));
    }

    //订单报表
    public function niuLiveOrderReport($orderId)
    {
        $uri = 'https://niu.e.kuaishou.com/rest/n/esp/web/report/live';
        return $this->httpPostJson($uri, compact('orderId'));
    }

    //关闭订单
    public function niuLiveOrderClose($orderId)
    {
        $uri = 'https://niu.e.kuaishou.com/rest/n/esp/web/order/common/close';
        return $this->httpPostJson($uri, compact('orderId'));
    }

    //机构粉条订单
    public function jgLiveOrderCreate(array $data)
    {
        $uri = 'https://jigou.kuaishou.com/rest/cp/org/fanstop/promote/live/order/create';
        return $this->httpPostJson($uri, $data);
    }

    public function jgUserInfo()
    {
        $url = 'https://jigou.kuaishou.com/rest/cp/org/account/current';
        return $this->httpPostJson($url, [
            'path' => '/',
            'kuaishou.web.cp.api_ph' => '',
        ]);
    }

    //成员信息
    public function jgMemberInfo($memberId)
    {
        $url = 'https://jigou.kuaishou.com/rest/cp/org/member/detail/baseinfo';
        return $this->httpPostJson($url, [
            'memberId' => $memberId,
            'kuaishou.web.cp.api_ph' => '',
        ]);
    }

    // 机构账号作品列表
    public function jgPhotoList($memberId, $fromTime = -1, $count = 50)
    {
        $url = 'https://jigou.kuaishou.com/rest/cp/org/fanstop/promote/live/photo/list';

        return $this->httpPostJson($url, [
            "memberId" => $memberId,
            "fromTime" => $fromTime,
            "count" => $count,
        ]);
    }

    //机构服务平台 已开播的直播间
    public function jgLiveCurrent($page = 1, $count = 50)
    {
        $url = 'https://jigou.kuaishou.com/rest/cp/org/fanstop/promote/live/current';
        return $this->httpPostJson($url, [
            "page" => $page,
            "count" => $count,
            "total" => 0,
            "sort" => 1,
            "memberId" => "",
            "desc" => null,
            "kuaishou.web.cp.api_ph" => ""
        ]);
    }

    public function kbPrice()
    {
        $url = 'https://jigou.kuaishou.com/rest/cp/org/fanstop/money/kb/price';
        return $this->httpPostJson($url, [
            "kuaishou.web.cp.api_ph" => ""
        ]);
    }

    public function accountType()
    {
        $url = 'https://jigou.kuaishou.com/rest/cp/org/fanstop/money/account/type';
        return $this->httpPostJson($url, [
            "kuaishou.web.cp.api_ph" => ""
        ]);
    }
}