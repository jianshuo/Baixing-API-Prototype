Baixing-API-Prototype
=====================

这里是一个原型，讨论即将诞生的Baixing API。注意：这不是一个最终的文档，也没有包含代码，仅仅是一个原型的讨论。
欢迎大家提出自己的意见。

API Endpoints
-------------

http://graph.baixing.com/`m37400`.json

其中，｀m37400｀是请求的资源。资源的格式有如下两种：
- `id`
- `id/connection`

ID方式访问一个实体
------------------

百姓网里面的任何一个实体都有一个唯一的id。比如广告，类目，用户等。比如：

http://graph.baixing.com/217700056

将返回id为217700056的广告的信息如下：
```json
{

     "id": "23432432",
     "title": "A good bike to sell",
     "description": "A very good one. Come and take a look. It is cheap. Please contact me at xxxxxxxxxxx. My email address is xxxxxxxxxxxß",
     "价格": "200元",
     "category": { "id": "zixingche", "name": "自行车"}
     "area": {
          "id": "m2171",
          "name": "松南",
          "parent": {
               "id": "m4323",
               "name": "联洋"
          }
     }
     "createdTime": 1343222,
     "images": "4ff2a482cf1af725fbc53d763969e6d0.jpg ca286ee77bf4ea293455c30366d7db1a.jpg",
     "user": {
          "id": "u86527441",
          "name": "bebeawu"
     },
     "type": {
          "id": "m36239",
          "name": "钢琴"
     },
     "connections": {
          "categories": http://graph.baixing.com/23432432/categories",
          "myothers": http://graph.baixing.com/86527441/ads"
     }
}
```

ID/connection方式访问一个实体的连接
-------------------------------------------

http://graph.baixing.com/`m432432/listings`

表达的是`钢琴`这个类型的所有的帖子，返回的是一个数组

```json
{
    "data": [
          {
               "icon": "http://img1.baixing.net/m/fb0e0f9475b2d47c3751ec1677af43a9_sq.jpg",
               "createdTime": "8月8日",
               "id": "225350848",
               "title": "402扬琴",
               "area": {"id": "m7252", "name": "宝山"},
               "price": "900元"
          },
          {
               "icon": "http://img1.baixing.net/m/f34dfa013a7aff6243f19cfaec86b7d6_sq.jpg",
               "createdTime": "8月8日",
               "id": "102369204",
               "title": "惠普礼盒一套，买笔记本电脑时的赠品，转给需要的人。",
               "area": {"id": "m7259", "name": "闸北"},
               "price": "50元"
          },
          {
               "icon": "http://img1.baixing.net/m/0fc2df064de3a56858bf991b6477bb81_sq.jpg",
               "createdTime": "8月8日",
               "id": "211229232",
               "title": "好易通全球翻译王 T-3000 手写 含发票，包装",
               "area": {"id": "m7259", "name": "闸北"},
               "price": "200元"
          },
          {
               "icon": "http://img1.baixing.net/m/652c98022bc5c738003cb7df15bc33a8_sq.jpg",
               "createdTime": "8月8日",
               "id": "211104607",
               "title": "低价 各式软面抄硬面抄和水笔",
               "area": {"id": "m7260", "name": "长宁"},
               "price": "70元"
          },
          {
               "icon": "http://img3.baixing.net/m/56f13548aa9bdfbe894cf244e8f9dc45_sq.jpg",
               "createdTime": "8月8日",
               "id": "183121182",
               "title": "全新喜羊羊文具礼盒，闲置转让",
               "area": {"id": "m7259", "name": "闸北"},
               "price": "20元"
          } 
     ],
     "paging": {
          "next": "http://graph.baixing.com/m432432?offset=20",    
     }
}
```

每一个实体都有一些连接。比如说，一个类目就有listings这个连接，可以用来找到它包含的所有的帖子。

城市信息
========

关于城市的上下文信息可以通过在前面加上子域名完成。

http://`shanghai`.graph.baixing.com/
来限定所有的返回值都是来自上海的实体。

进度
====

在8月份，还不准备提供任何认证，就直接开放给所有的用户来使用就好。当然所获取的信息也都是公开信息。
