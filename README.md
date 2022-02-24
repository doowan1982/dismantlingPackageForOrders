# dismantlingPackageForOrders
订单拆包算法

```php
$packageDismantling = new DismantlingPackage(2000); //最大2000

$result = $packageDismantling->dismantle([
    [
        'goodsId' => 4632, 
        'weight' => 2200, 
        'num' => 6
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
```

