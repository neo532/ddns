<?php
class Ddns{
    private $conf;
    private $error;
    public function getErr(){
        return $this->error;
    }
    public function __construct(array $conf){
        $this->conf = $conf;
    }

    private function header(){
        return array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'User-Agent' => "DNSPOD-DDNS-CLIENT/1.0.0({$this->conf['dnspod_email']})",
        );
    }

    public function upgradeIp(){
        do{
            $publicIp = $this->getPublicIp();
            if( false===$publicIp ){
                break;
            }
            $recordInfo = $this->getRecordIp(); 
            if( false===$recordInfo ){
                break;
            }

            if( $recordInfo['value']===$publicIp ){
                return false;
            }
            $updateRst = $this->updateRecord($recordInfo['id'], $publicIp);
            if( false===$updateRst ){
                break;
            }
        }while(false);
        if( !empty($this->getErr()) ){
            return date('Y-m-d H:i:s')." ".$this->getErr()."\n";
        }else{
            return date('Y-m-d H:i:s')." ".json_encode($updateRst, JSON_UNESCAPED_UNICODE)."\n";
        }
    }

    private function param(array $paramList){
        $paramList['login_token'] = $this->conf['dnspod_id'].','.$this->conf['dnspod_token'];
        $paramList['format'] = 'json';
        $paramList['lang'] = 'cn';
        $paramList['error_on_empty'] = 'no';
        $paramList['domain'] = $this->conf['dnspod_domain'];
        $paramList['sub_domain'] = $this->conf['dnspod_subdomain'];
        return $paramList;
    }

    public function updateRecord($recordIp, $publicIp){
        $paramList = array(
            'record_id' => $recordIp,
            'record_type' => 'A',
            'record_line' => '默认',
            'value' => $publicIp,
        );
        $rst = $this->curl(
            'POST',
            'https://dnsapi.cn/Record.Modify',
            $this->param($paramList),
            $this->header()
        );
        if( empty($rst) ){
            $this->error(__LINE__, __METHOD__.' return null');
            return false;
        }
        $rstList = json_decode($rst, true);
        if( empty($rstList) ){
            $this->error(__LINE__, '['.__METHOD__.'][decode error]['.$rst.']');
            return false;
        }
        if( '1'!==trim($rstList['status']['code']) ){
            $this->error(__LINE__, '['.__METHOD__.'][status error]['.$rst.']');
            return false;
        }
        return $rstList;
    }

    public function getRecordIp(){
        $rst = $this->getDomainRecord();
        if( false===$rst ){
            $this->error(__LINE__, '['.__METHOD__.'] return null');
            return false;
        }
        foreach($rst['records'] as $index=>&$row) {
            unset($rst['records'][$index]);
            if( $this->conf['dnspod_subdomain']===$row['name'] ){
                return array(
                    'id' => $row['id'],
                    'value' => $row['value'],
                );
            }
        }
        $this->error(__LINE__, '['.__METHOD__."]Can't find the subdomain");
        return false;
    }

    public function getDomainRecord(){
        $rst = $this->curl(
            'POST',
            'https://dnsapi.cn/Record.List',
            $this->param([]),
            $this->header()
        );
        if( empty($rst) ){
            $this->error(__LINE__, '['.__METHOD__.'] return null');
            return false;
        }
        $rstList = json_decode($rst, true);
        if( empty($rstList) ){
            $this->error(__LINE__, '['.__METHOD__.'][decode error]['.$rst.']');
            return false;
        }
        if( '1'!==trim($rstList['status']['code']) ){
            $this->error(__LINE__, '['.__METHOD__.'][status error]['.$rst.']');
            return false;
        }
        return $rstList;
    }

    public function getPublicIp(){
        $rst = $this->curl('GET', 'http://www.httpbin.org/ip');
        if( empty($rst) ){
            $this->error(__LINE__, '['.__METHOD__.'][return null]');
            return false;
        }
        $rst = json_decode($rst, true);
        return $rst['origin'];
    }

    private function curl($method, $url, $param=[], $headerList=[]){
        $param = is_array($param)? http_build_query($param, null, '&', PHP_QUERY_RFC3986): $param; 

        $objCurl = curl_init();

        if( !empty($headerList) ){
            $header=[];
            foreach($headerList as $key=>$value){
                $header[]="{$key}:{$value}";
            }
            curl_setopt($objCurl,CURLOPT_HTTPHEADER,$header);
        }
        curl_setopt($objCurl, CURLOPT_POSTFIELDS, $param);
        curl_setopt($objCurl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($objCurl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($objCurl, CURLOPT_URL, $url);
        curl_setopt($objCurl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36');
        curl_setopt($objCurl, CURLOPT_NOSIGNAL, true);
        curl_setopt($objCurl, CURLOPT_TIMEOUT_MS, 30000);
        curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT_MS, 30000);
        curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($objCurl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($objCurl, CURLOPT_MAXREDIRS, 3);
        curl_setopt($objCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($objCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $resp = curl_exec($objCurl);
        curl_close($objCurl);
        return $resp;
    }

    private function error($errNo, $msg){
        $this->error = "line:{$errNo},msg:{$msg}";
    }
}
