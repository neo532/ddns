# dnspod-ddns

通过dnspod网站的接口，对指定域名的记录dns记录进行修改

看了一下github没有php版本的，就自己写了个

如果对你有用，可以star一下，2333

## 使用

**配置项:**

见conf.php

**运行:**

&lt;yourpath&gt;/php run.php

## 申请dnspod的ID和token
教程见dnspod官网：
[https://support.dnspod.cn/Kb/showarticle/tsid/227/](https://support.dnspod.cn/Kb/showarticle/tsid/227/)

## dnspod的dns一些注意事项

1.注册成功后，记得将id和token写到配置文件里

2.记得要填写你要跟新的域名,这里会拿到一些新的dns解析服务

3.记得跟新你域名注册商那里的域名dns解析服务地址为第二步拿到的dns服务器
