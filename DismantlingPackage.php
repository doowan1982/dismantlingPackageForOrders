<?php
/**
 * 商品订单按重量拆包
 * @author  doowan <doowan@qq.com>
 * @date 2022-2-24
 */
class DismantlingPackage{

    /**
     * 拆包限制值
     * @var integer
     */
    private $maxWeight = 0;

    /**
     * 拆包后分解出的包数据
     * @var array
     */
    private $packages = [];

    public function __construct($maxWeight){
        $this->maxWeight = $maxWeight;
        $this->packages[] = $this->createGoodsPackages();
    }

    /**
     * 拆包
     * @param  array $goods 商品信息 [['goodsId' => 1, 'weight' => 1.2, num => 1], ...]
     * @return array [[goodsId => num, ...], [goodsId => num, ..], ..] 分包数据
     */
    public function dismantle($goods){
        
        $array = $this->mergeGoods($goods);

        while(true){
            $array = $this->allocate($this->sortByTotalWeight($array));
            if(empty($array)){
                break;
            }
            $this->packages[] = $this->createGoodsPackages();
        }

        $result = [];
        foreach($this->packages as $key=>$package){
            $array = [];
            $packageName = "包裹".($key+1);
            foreach($package->getGoods() as $goodsId=>$value){
                $array[$goodsId] = $value['num'];
                self::vardump("{$packageName}中的商品：{$goodsId}，数量：{$value['num']}，重量：".($value['num'] * $value['weight']));
            }
            $result[] = $array;
            self::vardump("{$packageName}总重量:".$package->getTotalWeight());
        }
        return $result;
    }

    /**
     * 商品分包
     * @param  array $goods Goods数组
     * @return goods 处理后剩余的Goods数组
     */
    protected function allocate($goods){

        $currentGoods = array_shift($goods);

        if($currentGoods->weight > $this->maxWeight){
            throw new Exception("商品【{$currentGoods->id}】单位重量超过最大限制值【$this->maxWeight】");
        }

        $overflowNum = $this->getOverflowNum($currentGoods->num, $currentGoods->weight);

        $package = end($this->packages); //取最新的包裹

        $putPackageNum = 0;
        //如果重量已经大于预定义值，则将当前$currentGoods的值做拆分
        if($overflowNum > 0){
            $putPackageNum = $currentGoods->num - $overflowNum;
        }else{
            $putPackageNum = $currentGoods->num;
        }

        $package->addGoods($currentGoods, $putPackageNum);

        $goods = $this->appendGoods($package, $goods);//剩余时进行拼包操作

        if($overflowNum > 0){
            //重新加入goods中进行后续处理
            $goods[] = $this->getGoods($currentGoods->id, $currentGoods->weight, $overflowNum);
        }
        return $goods;
    }

    //var_dump
    public static function vardump($value){
        echo $value, "\r\n";
        // var_dump($value);
    }

    //Get GoodsPackages
    protected function createGoodsPackages(){
        return new GoodsPackages();
    }

    //Get Goods
    protected function getGoods($id, $weight, $num){
        return new Goods($id, $weight, $num);
    }

    //Continuous unpacking, split 
    protected function appendGoods(GoodsPackages $package, $goods){
        while(true){
            $capacity = $this->maxWeight - $package->getTotalWeight();
            if($capacity === 0){
                break;
            }
            $offset = -1;
            $approach = 0;
            foreach($goods as $k=>$v){
                if($v->getTotalWeight() <= $capacity && $approach < $v->getTotalWeight()){
                    $offset = $k;
                    $approach = $v->getTotalWeight();
                }
            }
            if($offset === -1){
                break;
            }
            $package->addGoods($goods[$offset], $goods[$offset]->num);
            array_splice($goods, $offset, 1);
        }
        return $goods;
    }

    //计算超出重量的商品
    protected function getOverflowNum($num, $weight){
        $current = $num;
        while(true){
            if(($current * $weight) <= $this->maxWeight){
                return $num - $current;
            }
            $current--;
        }
        return $current;
    }


    //初始化合并传入的商品数据
    private function mergeGoods($goods){
        foreach($goods as $v){
            $weight = $v['weight'] ?? 0;
            $num = $v['num'] ?? 0;
            $id = $v['goodsId'];
            if(!isset($array[$id])){
                $array[$id] = $this->getGoods($id, $weight, $num);
            }else{
                $array[$id]->weight = max($array[$id]->weight, $weight); //取最大的重量值
                $array[$id]->addNum($num);
            }
        }

        foreach($array as $id => $value){
            self::vardump("商品{$id}，共有{$value->num}个");
        }

        return array_values($array);
    }

    private function sortByTotalWeight($array){
        usort($array, function($first, $second){
            if($first->getTotalWeight() === $second->getTotalWeight()){
                return 0;
            }
            return $first->getTotalWeight() > $second->getTotalWeight() ? -1 : 1;
        });
        return $array;
    }

}

class Goods{

    public $id;

    public $num;

    public $weight;

    private $totalWeight;

    public function __construct($id, $weight, $num = 0){
        $this->id = $id;
        $this->weight = $weight;
        $this->addNum($num);
    }

    /**
     * 追加数量
     * @param integer $num
     */
    public function addNum($num){
        $this->num += $num;
        $this->totalWeight = 0;
    }

    /**
     * 返回当前商品的总数量
     * @return float
     */
    public function getTotalWeight(){
        if(!$this->totalWeight){
            $this->totalWeight = $this->num * $this->weight;
        }
        return $this->totalWeight;
    }

}

class GoodsPackages{

    //当前包的总数量
    private $totalWeight = 0;

    //当前包中所包含的商品明细
    private $goods = [];

    /**
     * @param Goods $goods
     * @param integer $num  商品数量
     */
    public function addGoods(Goods $goods, $num){
        $id = $goods->id;
        if(!isset($this->goods[$id])){
            $this->goods[$id] = [
                'num' => 0
            ];
        }
        $this->goods[$id]['weight'] = $goods->weight;
        $this->goods[$id]['num'] += $num;
        $this->totalWeight += $num * $goods->weight;
    }

    /**
     * 返回当前包中的商品数据
     * @return array
     */
    public function getGoods(){
        return $this->goods;
    }

    /**
     * 返回当前package中的总重量
     * @return float;
     */
    public function getTotalWeight(){
        return $this->totalWeight;
    }
}

$packageDismantling = new DismantlingPackage(2000); //最终2000

$result = $packageDismantling->dismantle([
    [
        'goodsId' => 4632, 
        'weight' => 600, 
        'num' => 5
    ],
    [
        'goodsId' => 4630, 
        'weight' => 300.65, 
        'num' => 2
    ],
    [
        'goodsId' => 4626, 
        'weight' => 99, 
        'num' => 2
    ],
    [
        'goodsId' => 4628, 
        'weight' => 320.05, 
        'num' => 1
    ],
]);


// vardump开启后输出：
// 商品4632，共有5个
// 商品4630，共有2个
// 商品4626，共有2个
// 商品4628，共有1个
// 包裹1中的商品：4632，数量：3，重量：1800
// 包裹1中的商品：4626，数量：2，重量：198
// 包裹1总重量:1998
// 包裹2中的商品：4632，数量：2，重量：1200
// 包裹2中的商品：4630，数量：2，重量：601.3
// 包裹2总重量:1801.3
// 包裹3中的商品：4628，数量：1，重量：320.05
// 包裹3总重量:320.05