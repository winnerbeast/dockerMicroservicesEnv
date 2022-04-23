<?php
require_once './extend/vendor/autoload.php';

use  Elasticsearch\ClientBuilder;
use Faker\Factory;

class esSearch
{
    private $esClient;
    private $faker;

    public function __construct()
    {
        $this->esClient = ClientBuilder::create()->setHosts(['http://192.168.145.131:9202'])->build();

        $this->faker = Factory::create('zh_CN');

}

    public function insert($num = 100)
    {
        #bulk批量生成
        for($i = 0; $i <= $num; $i ++) {
            $params['body'][] = [
                'index' => [   #创建
                    '_index' => 'nezha_store_order',
                ]
            ];

            $params['body'][]=array(
                'id' => $i+1,
                "order_num" => $this->faker->numberBetween(),
                "order_id" => $this->faker->numberBetween(),
                "uid " => $i+2,
                "real_name" => $this->faker->userName,
                "user_phone" => $this->faker->phoneNumber,
                "user_address" => $this->faker->address,
                "cart_id" => $i+3,
                "freight_price" => $this->faker->randomFloat(),
                "total_num" => $this->faker->numberBetween(),
                "total_price" =>$this->faker->randomFloat(),
                "total_postage" => $this->faker->randomFloat(),
                "pay_price" => $this->faker->randomFloat(),
                "pay_postage" => $this->faker->numberBetween(),
                "deduction_price" => $this->faker->numberBetween(),
                "coupon_id " => mt_rand(1,100),
                "coupon_price" => mt_rand(10,1000),
                "paid" => mt_rand(1,100),
                "pay_type" => array_rand(['WXPay','AliPay','银联']),
                "pay_time" => $this->faker->date().' '.$this->faker->time(),
                "add_time" => $this->faker->date().' '.$this->faker->time(),
                "status" => mt_rand(1,5),
                "refund_status" => mt_rand(0,5),
                "refund_reason_wap_img" => null,
                "refund_reason_wap_explain" => null,
                "refund_reason_time" => null,
                "refund_reason_wap" => null,
                "refund_reason" => null,
                "refund_price" => null,
                "delivery_name" =>  $this->faker->userName,
                "delivery_type" => array_rand(['在线下单发货','货到付款']),
                "delivery_id" => $this->faker->numberBetween(),
                "gain_integral" => mt_rand(1,100),
                "back_integral" => mt_rand(1,50),
                "mark" => null,
                "remark" => $this->faker->words(),
                "mer_id" => $this->faker->numberBetween(),
                "combination_id" => mt_rand(0,9999),
                "pink_id" => mt_rand(0,9999),
                "cost" => $this->faker->randomFloat(),
                "seckill_id" => $this->faker->numberBetween(),
                "bargain_id" => $this->faker->numberBetween(),
                "verify_code" => $this->faker->uuid,
                "store_id" => mt_rand(0,9999),
                "shipping_type" => mt_rand(1,2),
                "clerk_id" => $this->faker->numberBetween(),
                "is_channel" => mt_rand(0,1),
                "is_remind" => mt_rand(0,1),
                "is_system_del" => mt_rand(0,1),
            );

           #  var_dump($params);die();
        }

        $res = $this->esClient->bulk($params);
        return $res;
    }

    public function index()
    {
        $query = [
            'query' => [
                'bool' => [
                    'must' => [
                        'match' => [
                            'first_name' => '张',
                        ]
                    ],
                    'filter' => [
                        'mt_rand' => [
                            'age' => ['gt' => 76]
                        ]
                    ]
                ]

            ]
        ];

        $params = [
            'index' => 'nezha_store_order',
//  'index' => 'm*', #index 和 type 是可以模糊匹配的，甚至这两个参数都是可选的
            'type' => '_doc',
            '_source' => ['id','order_id','real_name','user_phone','user_address','pay_time','pay_type'], // 请求指定的字段
            'body' => array_merge([
                'from' => 0,
                'size' => 5
            ],$query)
        ];
       return $this->esClient->search($params);
    }

}
$es = new esSearch();
$res = $es->insert();
var_export($res);


