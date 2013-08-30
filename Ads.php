<?php
namespace spike\ads;

use spike\Notice;
use spike\dao\Ads;
use spike\dao\AdGoods;
use spike\dao\GoodsFeed;
use spike\dao\Enduser;

class Ads {

  private static $validates = array(
    'goods_id' => array(
      'length' => 16,
      'dataType' => 'string',
      'null' => false,
    ),
    'rate' => array(
      'length' => 8,
      'dataType' => 'integer',
      'null' => false,
    )
  );

  private static $VALIDATES = array(
    'post' => array(
    ),
    'get'  => array(
    ),
    'delete' => array(
    ),
  );

  /*
   * resutful
   */
	public static function rest(){
		$method = 'GET';
		if(isset($_SERVER['REQUEST_METHOD'])){
			$method = $_SERVER['REQUEST_METHOD'];
		}
    // アクセス制御
    // 不正アクセス対策いれます：具体的にどうする
    // 何を持って不正アクセスとするか
    // 1. 同じパラメータで何回もアクセスしてきた

       // ログインチェック

    // 入力値の単純妥当性検査
    // 入力妥当性検査

    self::validate();
    
		$methodName = strtolower($method);
		if(method_exists($this,$methodName)){
			self::$methodName($request);
		} else {
			self::get($request);
    }
  }

  public static function getParam($name,$method='post'){
    //get以外は$_POST[$name]を返す
    if($method == 'get'){
      return $_GET[$name];
    } else {
      return $_POST[$name];
    }
  }
  /**
   * @var Enduser
   */
  private static $enduser;

  public static function addError($error,$code){
    //出力のresultに$code
    //messageに$error
    //dataは無し
    //エラーメッセージ出力

  }

  private static $output = array();
  public static function set($name,$val){
    self::$output[$name] = $val;
  }
  public static function output(){
    $jsonMap = array(
      'result' => 'OK', // todo エラーが有ればエラーコードをresult,エラーメッセージをmessageに設定
      'message' => null,
      'data' => //todo
      //出力するdataの設定
      //ad_id
      //goods_feed(goods_id,goods_name)
      //ad_url
      //rate
      //price
      //rate_claim
      //rate_reward
      //reward
      //link_count
      //expire_date
    );
    echo json_encode($jsonMap);
  }
  /*
   * post
   */
  public static function post(){
    $goodsId = self::getParam('goods_id');
    $adId = self::getParam('ad_id');
    // 登録する広告
    $ads = new \spike\dao\Ads();
    if(!is_null($adId)){
      if($ads->load(array('ad_id'=>$adId))){
        $goodsId = $ads->goods_id;
      } else {
        // 入力が不正：不正アクセスの可能性
        // メール、Noticeでユーザ の情報を通知する
        // http_referrer = $_SERVER['HTTP_REFERER']
        // user_agent = $_SERVER['HTTP_USER_AGENT']
        // remoto_host = $_SERVER['REMOTE_HOST']
        // remoto_address = $_SERVER['REMOTE_ADDR']
        // language = $_SERVER['HTTP_ACCEPT_LANGUAGE']
        // country_code
        // region_code 
 
      }
    }
    // goods_feed読み込みと妥当性検査
    $goods = new GoodsFeed();
    $adGoods = new AdGoods();
    $curAds = null;
    if(!is_null($goodsId)){
      if(!$goods->load(array('goods_id'=>$goodsId))){
        // エラーにする
        self::addError('The goods is not exist!');
        return;
      }
      // すでに出稿されている広告の読み込みと妥当性検査
      if(is_null($adId)){
        $result = $adGoods->load(array('goods_id'=>$goods->goods_id));
        if($result){
          // 広告出稿が現在ある場合
          $curAds = new \spike\dao\Ads();
          if($curAds->load(array('ad_id'=>$adGoods->ad_id))){
            // 現在の出稿データとリンク
            $ads->previous_id = $curAds->ad_id;
          } else {
            // todo なんらかのデータ不整合が発生した。
            // メール、Noticeで goodsId adId resultの情報を通知する
          }
        }
      }
    }

    // 新しい広告の登録フィールド調整 : 入力値を設定する
    $rate = self::getParam('rate');
    // 請求割合と報酬割合を計算してオブジェクトに設定
    $ads->owner_id = $goods->owner_id;
    $ads->goods_id = $goods->goods_id;
    $ads->rate = $rate;
    $ads->rate_claim  = $rateClaim;
    $ads->rate_reward = $rateReward;
    $ads->registered_at = date('Y-m-d H:i:s');
    $ads->updated_at    = date('Y-m-d H:i:s');

    // 登録シナリオ作成
    $senario = array();
    // 状態確認して有効なら消さなければいけない
    if(!is_null($curAds)){
      $curAds->status_flag = 255;
      $scenario[] = array(
        'ClassName' => 'spike\\dao\\Ads',
        'filedsMap' => $curAds->getFieldsMap()
      );
    }
    // 新しい広告の登録
    $scenario[] = array(
      'ClassName' => 'spike\\dao\\Ads',
      'filedsMap' => $ads->getFieldsMap()
    );
    // AdGoodsの更新
    $scenario[] = array(
      'ClassName' => 'spike\\dao\\AdGoods',
      'filedsMap' => $adGoods->getFieldsMap()
    );

    // SQS登録
    SQS::reserve($scenario,'reservation_pubads');

    // Output
    self::output();

    // 通知メール
    
    // Notice
    Notice::notify($subject,$message,$description,Notice::P_INFO);
  }

  /*
   * get
   */
  public static function get(){
    
  }

  /*
   * put
   */
  public static function put(){
    
  }

  /*
   * delete
   */
  public static function delete(){
    
  }


  /*
   * 入力値妥当性チェック
   * goods_id string not null length 16
   * rate integer not null length 4
   */
  public static function inputValidation($inputPram){
  
    //goods_id,rateがパラメータとして渡されているか
    if(!array_key_exists('goods_id') && !array_key_exists('rate'){
      throw new Exception('goods_idとrateをパラメータとして渡してください');
    }
    //不要なパラメータが渡されていないか
    
    // goods_id,rate null チェック
    if(!self::validates['goods_id']['null']){
      if(!$this->validationNull($inputPram['goods_id'])){
        throw new Exception('goods_idが空です');
      }
    }
    if(!self::validates['rate']['null']){
      if(!$this->validationNull($inputPram['rate'])){
        throw new Exception('rateが空です');
      }
    }
    // goods_idは16桁の文字列か
    if(!$this->validationDataType($inputPram['goods_id'],self::validates['goods_id']['dataType'])){
      throw new Exception('goods_idは'.self::validates['goods_id']['dataType'].'ではありません');
    }
    if(!$this->validationLength($inputPram['goods_id'],self::validates['goods_id']['length'])){
      throw new Exception('goods_idは'.self::validates['goods_id']['length'].'文字以下で入力して下さい');
    }
    
    // rateは4桁の整数か
    if(!$this->validationDataType($inputPram['rate'],self::validates['rate']['dataType'])){
      throw new Exception('rateは'.self::validates['rate']['dataType'].'ではありません');
    }
    if(!$this->validationLength($inputPram['rate'],self::validates['rate']['length'])){
      throw new Exception('rateは'.self::validates['rate']['length'].'文字以下で入力して下さい');
    }
  }

  /*
   * null check
   */
  public static function validationNull($value){
    //引数は配列NG
    if(in_array($value)){
      return false;
    }
    if(is_null($value) || strlen($value) == 0){
      return false;
    } else {
      return true;
    }
  }

  /*
   * length check
   */
  public static function validationLength($value,$length){
    //引数は配列NG
    if(in_array($value)){
      return false;
    }
    if(strlen($value) > $length){
      return false;
    } else {
      return true;
    }
  }

  /*
   * data type check
   */
  public static function validationDataType($value,$dataType){
    //引数は配列NG
    if(in_array($value)){
      return false;
    }
    if(gettype($goods_id) != $dataType){
      return false;
    } else {
      return true;
    }
  }

  /*
   * 商品情報の確認
   */
  public static function validationGoodsFeed($inputPram){

    // Dynamoのgoods_feed テーブルからgoods_idをキーにして情報取得 
    $cond = array('goods_id' => $inputPram['goods_id']);
    $row = \spike\DynamoDB::get('goods_feed',$cond);

    // 商品が存在しない場合エラー
    if(!is_array($row) || count($row) == 0){
      throw new Exception('商品がありません');
    }

    // 存在する場合、owner_id,priceをメンバ変数へ
    self::fieldMap['owner_id'] = $row['owner_id'];
    self::fieldMap['price'] = $row['price'];
  }

  /*
   * 広告 validation
   */
  public static function validationAds(){
    
    // 有効な広告が有るか確認
    // Dynamo ad_goods からgoods_idをキーにして検索
    $cond = array('goods_id' => $inputPram['goods_id']);
    $row = \spike\DynamoDB::get('ad_goods',$cond);

    // 有効な広告が見つかればエラー
    if(is_array($row) || count($row) > 0){
      throw new Exception('この広告は既に出稿されています');
    }

  }
  
  /*
   * ads data Set
   */
  public static function adjustFieldsAds(){
    $scenario = null;
    // 前回の広告を無効にするscenario 1
    // Dynamo ads からad_id をキーにして検索
    $row = \spike\DynamoDB::get('ad_goods',$cond);
    // 無ければスルー
    // 有れば 見つかったadsレコードのstatus_flagを変更 
    if(is_array($row) || count($row) > 0){
      $scenario = $row;
      
    }


    //Queueに登録するシナリオ adsの更新 scenario 2

    // ad_id 8桁 [0-9a-zA-Z]

    // goods_id = input goods_id

    // ad_url = https://ad.spike.cc?xxxx

    // owner_id = goods_feed.owner_id
    // rate = parameter rate
    // price = goods_feed.price
    // rate_claim = rete - rate_reward
    // rate_reward = rate - rate_claim
    // 報酬額の計算
    // 報酬率 = 料率 - 請求料率(default 5)

    // 報酬金額 = 商品価格 * (報酬率 / 1000)
    // reward = price * (rate_reward / 1000)
    // link_count = 0
    // expire_date = date + 30days
    // status_flag = 100
    // http_referrer = $_SERVER['HTTP_REFERER']
    // user_agent = $_SERVER['HTTP_USER_AGENT']
    // remoto_host = $_SERVER['REMOTE_HOST']
    // remoto_address = $_SERVER['REMOTE_ADDR']
    // language = $_SERVER['HTTP_ACCEPT_LANGUAGE']
    // geoip_region_by_name(remoto_host) 商用のGeoIP Region Editionしか使えない
    // country_code = geoip_country_code_by_name(remoto_host)
    // region_code = 
    // registered_at = date('Y-m-d H:i:m')
    // updated_at = date('Y-m-d H:i:m')

    // ad_goods の更新　scenario 3
    // ad_id  
    // goods_id
  
  }

  /*
   * Queueに登録
   */
  public static function registerQueue($scenario){
    //シナリオをキューに登録
    //adsを出稿の状態でSQSに登録
    $this->object->reserve();
    sleep(5);

    //キューの確認
    //キューに登録されるまで確認 タイムアウトoo秒 超過で登録失敗とみなす
    $scenario = \spike\SQS::peek($this->queueName);
    $scenario = Map::obj2map($scenario);
    sleep(8);

    \spike\SQS::register($this->queueName);
    sleep(5);

  }


  /*
   * 出力情報設定
   */
  public static function adjustOutput(){
    //Queueが実行されるまで待機
    //Dynamoに登録されているadsの情報をad_idをキーにして取得
    //登録されていない エラーを設定
    //登録されている
  }


  /*
   * 出力
   */
  public static function outputJson(){
    //viewにjson_encode出力

  }

}
