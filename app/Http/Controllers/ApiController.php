<?php

namespace App\Http\Controllers;

use App\Sheerdata;
use Illuminate\Http\Request;
use DateTime;

class ApiController extends Controller
{
    public function phpFunction()
    {
        return 1;
    }
    public function getDataByDate($date)
    {
        $columnName = 'dd';
        $data = Sheerdata::where($columnName, $date)->get();
        return response()->json(['data' => $data]);
    }
    public function getDicByData(Request $request)
    {
        $res1json = null;
        $res2json = null;
        $resp = null;
        $search = $request->search ? $request->search : "search";
        $type = $request->type ? $request->type : "def";
        $page = $request->page ? $request->page : "1";
        if (preg_match('/[\'^£$%&*()}{#@#~?!><>,|=_&+¬-]/', $request->search)) { $search = "search"; }
        if($type == "def"){
            // w
            $ch1 = curl_init("https://api.4dmanager.com/api/search_m?s=$search");
            curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch1, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch1, CURLOPT_CONNECTTIMEOUT, 2);
            $res1 = curl_exec($ch1);
            $res1json = json_decode($res1);
            // m
            $ch2 = curl_init("https://app-apdapi-prod-southeastasia-01.azurewebsites.net/4d-dictionary/numbers/keywords/$search");
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 2);
            $res2 = curl_exec($ch2);
            $res2json = json_decode($res2);
        }else{
            $typeArr = array(
                "tua"=>"tpk",
                "kuan"=>"gym",
                "wanz"=>"wzt"
            );
            $t = $typeArr[$type];
            $ch1 = curl_init("https://api.4dmanager.com/api/qzt?t=$t&p=$page");
            curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch1, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch1, CURLOPT_CONNECTTIMEOUT, 2);
            $res1 = curl_exec($ch1);
            $res1json = json_decode($res1);
        }
        if(isset($res1json) || isset($res2json)){
            if(isset($res1json) && isset($res1json->qzt[45])){
                if($res1json->qzt[45]->no == "0046"){
                    $res1json->qzt[45]->no = "046";
                }
            }
            // $key = "KEY_NO";
            $resp = array(
                "main"=>isset($res1json) ? $res1json : null,
                "m4d"=>isset($res2json) ? json_decode($res2json) : null,
                "pic"=>array(
                    "m4d"=>"https://magnum4d.my/Magnum4d/media/4D-Dictionary/$key.GIF?ext=.gif",
                    "tua"=>"https://repo.4dmanager.com/qzt/tpk/$key.png",
                    "kuan"=>"https://repo.4dmanager.com/qzt/gym/$key.png",
                    "wanz"=>"https://prddmccms1.blob.core.windows.net/number-dictionary/$key.jpg",
                )
            );
            return $resp;
        }else{
            return null;
        }
    }
    
    public function saveData($date){
        $data = $this->dataByDate($date);
        foreach ($data as $vars) {
            return $vars;
        }
    }
    public function getDataBySearch(Request $request){
        $number = isset($request->no) && $request->no !== "...." && $request->no !== "----" ? $request->no : "7777";
        $Perm = isset($request->Perm) ? "PM" : "nopm";
        $select4D = "";
        $selected4D = [];
        if(isset($request->service)){
            foreach ($request->service as $k => $s){
                $select4D .= $k."-";
                $selected4D[$k] = true;
            }
        }else{
            $select4D = "M-PMP-ST";
            $selected4D["M"] = true;
            $selected4D["PMP"] = true;
            $selected4D["ST"] = true;
        }
        $hisjson = $this->historyData($Perm,$select4D,$number,"en");
        // number pic API
        $ch1 = curl_init("https://api.4dmanager.com/api/no_qzt?no=$number");
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch1, CURLOPT_CONNECTTIMEOUT, 2);
        $res1 = curl_exec($ch1);
        $direcjson = json_decode($res1);
        // LOOP
        $sitesCount = array(
            "M" => 0,
            "PMP" => 0,
            "ST" => 0,
            "STC" => 0,
            "EE" => 0,
            "CS" => 0,
            "SG" => 0,
            "GD" => 0,
            "NL" => 0,
            "PD" => 0,
            "LH" => 0,
            "BN" => 0,
        );
        $przCount = array(
            "st" => 0,
            "nd" => 0,
            "rd" => 0,
            "sp" => 0,
            "cp" => 0
        );
        if(isset($hisjson)){
            foreach($hisjson as $v){
                $sitesCount[$v->type]+=1; 
                if($v->prize == "首獎"){
                    $v->prize = "First";
                    $przCount["st"]+=1;
                }elseif($v->prize == "二獎"){
                    $v->prize = "Second";
                    $przCount["nd"]+=1;
                }elseif($v->prize == "三獎"){
                    $v->prize = "Third";
                    $przCount["rd"]+=1;
                }elseif($v->prize == "特別獎"){
                    $v->prize = "Sp";
                    $przCount["sp"]+=1;
                }elseif($v->prize == "安慰獎"){
                    $v->prize = "Cp";
                    $przCount["cp"]+=1;
                }elseif($v->prize == "First"){
                    $przCount["st"]+=1;
                }elseif($v->prize == "Second"){
                    $przCount["nd"]+=1;
                }elseif($v->prize == "Third"){
                    $przCount["rd"]+=1;
                }elseif($v->prize == "Sp"){
                    $przCount["sp"]+=1;
                }elseif($v->prize == "Cp"){
                    $przCount["cp"]+=1;
                }
            }
        }
        return [
            "history" => $hisjson,
            "information" => $direcjson,
            "no" => $number,
            "search_no" => $selected4D,
            "count" => $sitesCount,
            "prize" => $przCount
        ];
    }
    public function historyData($permutation,$sites,$number,$type = "en"){
        $sitesToArray = explode("-",$sites);
        $sitesFilter = "'" .implode("','",$sitesToArray). "'";
        // return $sitesFilter;
        if($permutation == 'PM'){
            $number =  implode(",",$this->permute($number));
        }
        $sql = "select c.* from (";
        $sql.= "(select dd,type,n1 as num, '首獎' as prize from result a where n1 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,n2 as num, '二獎' as prize from result b where n2 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,n3 as num, '三獎' as prize from result b where n3 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s1 as num, '特別獎' as prize from result b where s1 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s2 as num, '特別獎' as prize from result b where s2 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s3 as num, '特別獎' as prize from result b where s3 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s4 as num, '特別獎' as prize from result b where s4 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s5 as num, '特別獎' as prize from result b where s5 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s6 as num, '特別獎' as prize from result b where s6 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s7 as num, '特別獎' as prize from result b where s7 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s8 as num, '特別獎' as prize from result b where s8 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s9 as num, '特別獎' as prize from result b where s9 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s10 as num, '特別獎' as prize from result b where s10 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s11 as num, '特別獎' as prize from result b where s11 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s12 as num, '特別獎' as prize from result b where s12 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s13 as num, '特別獎' as prize from result b where s13 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c1 as num, '安慰獎' as prize from result b where c1 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c2 as num, '安慰獎' as prize from result b where c2 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c3 as num, '安慰獎' as prize from result b where c3 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c4 as num, '安慰獎' as prize from result b where c4 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c5 as num, '安慰獎' as prize from result b where c5 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c6 as num, '安慰獎' as prize from result b where c6 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c7 as num, '安慰獎' as prize from result b where c7 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c8 as num, '安慰獎' as prize from result b where c8 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c9 as num, '安慰獎' as prize from result b where c9 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c10 as num, '安慰獎' as prize from result b where c10 IN ($number) and type IN ($sitesFilter)))";
        $sql.= "c order by c.dd desc";
        $query = DB::select(DB::raw($sql));
        if($number == "0000"){
            for($i = 0; $i < sizeof($query); $i++) {
                if($query[$i]->num == "----") {
                    array_splice($query, $i, 1);
                    $i--;
                }
            }
        }
        return $query;
    }
    public function getMainByDate($date){
        $today = date("Y-m-d");
        //live
        $url_main = "https://mapp.fast4dking.com/nocache/result_v23.json";
        $url_sub = "https://4dyes2.com/getLiveResult.php";
        $url_nl = "https://mobile.fast4dking.com/v2/nocache/result_nl_v24.json";
        //bydate
        if($date == "date" || $date >= $today){
            $date = $today;
        }else{
            //past
            $url_main = "https://mapp.fast4dking.com/past_results_v23.php?d=".$date;
            $url_sub = "https://4dyes2.com/getLiveResult.php?date=".$date;
            $url_nl = "past";
        }
        //is Live

        if($date == $today && date("Gi") <= 1829){
            $today_live = new DateTime($today);
            $today_live->modify('-1 days');
            $date = $today_live->format('Y-m-d');
        }
        //main DONE
        $ch1 = curl_init($url_main);
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch1, CURLOPT_CONNECTTIMEOUT, 2);
        $res1 = curl_exec($ch1);
        $main1 = json_decode($res1);
        //main api format
        if(!isset($main1)){
            $main1 = [];
        }
        $main1_final = $this->main1_formatter($main1);
        //sub
        $ch2 = curl_init($url_sub);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, ['referer: https://4dyes2.com/en/past-result']);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 2);
        $res2 = curl_exec($ch2);
        $main2 = json_decode($res2); 
        $main2_final = $this->sub_formatter($main2,$date);
        //nl
        if($url_nl == "past"){
            //
        }else{
            $ch3 = curl_init($url_nl);
            curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch3, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch3, CURLOPT_CONNECTTIMEOUT, 2);
            $res3 = curl_exec($ch3);
            $main3 = json_decode($res3);
            $main1_final["NL"] = isset($main3) && isset($main3[0]) ? $main3[0]->fdData : null;
            $main1_final["NLJP"] = isset($main3) && isset($main3[1]) ? $main3[1]->jpData1 : null;
        }
        //bn
        $date_bn = date("Ymd", strtotime($date));
        $url_bn = "https://publicapi.ace4dv2.live/publicAPI/bt4?date=$date_bn";
        $ch4 = curl_init($url_bn);
        curl_setopt($ch4, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch4, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch4, CURLOPT_CONNECTTIMEOUT, 1);
        $res4 = curl_exec($ch4);
        $main4 = json_decode($res4);
        $main4_final = $this->bn_formatter($main4,$date);
        //sbjp
        $date_sb = date("Ymd", strtotime($date));
        $url_sb = "https://www.check4d.org/liveosx.json";
        $ch5 = curl_init($url_sb);
        curl_setopt($ch5, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch5, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch5, CURLOPT_CONNECTTIMEOUT, 1);
        $res5 = curl_exec($ch5);
        $main5f = json_decode($res5);
        $main5 = [$main5f];
        
        $sbjp_formatter = [
            "jpData1"=>!isset($main5[0]) && !isset($main5[0]->SB->JP1) ? null : $main5[0]->SB->JP1,
            "jpData2"=>!isset($main5[0]) && !isset($main5[0]->SB->JP2) ? null : $main5[0]->SB->JP2,
            "jpData56d"=>!isset($main5[0]) && !isset($main5[0]->SBLT) ? null : $main5[0]->SBLT,  
        ];

        //sjp
        $main6 = null;
        return $main1_final;
        if($date == "date" || $date >= $today){
            $main6 = !isset($main1_final['SGJP_6/45']) ? null : $main1_final['SGJP_6/45'];
        }else{
            $ch6 = curl_init("https://app-6.4dking.com.my/past_results_v23.php?t=SG&d=".$date);
            curl_setopt($ch6, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch6, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch6, CURLOPT_CONNECTTIMEOUT, 1);
            $res6 = curl_exec($ch6);
            $main6 = json_decode($res6);
            //format
            if (is_array($main6)) {
                $keys = array_column($main6, 'type');
                $index = array_search('SGJP', $keys);
                if(isset($index) && $index >= 0){
                    if(isset($main6[$index]->jpData)){
                        $fomatter6 = $main6[$index]->jpData;
                    }else{
                        $fomatter6 = null;
                    }
                }
            } else {
                $fomatter6 = null;
            }
            $sjpFinal=null;
            if(!isset($fomatter6)){
                if(!isset($formatMain['SGJP_6/45'])){
                    $sjpFinal=null;
                }else{
                    $sjpFinal=$formatMain['SGJP_6/45'];
                }
            }else{
                $sjpFinal=$fomatter6;
            }

        }
        //$main1_final main
        //$main2_final lhpn
        //$main4_final bn
        //$sbjp_formatter ee

        $final_array = [
            [
                "type"=> "M",
                "fdData"=>!isset($main1_final['M']) ? null :$main1_final['M'],
                "jpData"=>[
                    "gold"=>!isset($main1_final['MJP_GOLD']) ? null : $main1_final['MJP_GOLD'],
                    "life"=>!isset($main1_final['MJP_LIFE']) ? null : $main1_final['MJP_LIFE']
                ]
            ],
            [
                "type"=> "PMP",
                "fdData"=>!isset($main1_final['PMP']) ? null :$main1_final['PMP'],
                "jpData"=>!isset($main1_final['PMPJP1']) ? null : $main1_final['PMPJP1']
            ],
            [
                "type"=> "ST",
                "fdData"=>!isset($main1_final['ST']) ? null :$main1_final['ST'],
                "jpData"=>[
                    "jp1"=>!isset($main1_final['STJP1']) ? null : $main1_final['STJP1'],
                    "jp50"=>!isset($main1_final['STJP_6/50']) ? null : $main1_final['STJP_6/50'],
                    "jp55"=>!isset($main1_final['STJP_6/55']) ? null : $main1_final['STJP_6/55'],
                    "jp58"=>!isset($main1_final['STJP_6/58']) ? null : $main1_final['STJP_6/58']
                ]
            ],
            [
                "type"=> "SG",
                "fdData"=>!isset($main1_final['SG']) ? null :$main1_final['SG'],
                "jpData"=>$sjpFinal
            ],
            [
                "type"=> "CS",
                "fdData"=>!isset($main1_final['CS']) ? null :$main1_final['CS']
            ],
            [
                "type"=> "STC",
                "fdData"=>!isset($main1_final['STC']) ? null :$main1_final['STC']
            ],
            [
                "type"=> "EE",
                "fdData"=>!isset($main1_final['EE']) ? null :$main1_final['EE'],
                "jpData"=>!isset($formatSabahJP) ? null : $formatSabahJP
            ],
            [
                "type"=> "GD",
                "fdData"=>!isset($main1_final['GD']) ? null :$main1_final['GD'],
                "jpData"=>!isset($main1_final['GD6D']) ? null : $main1_final['GD6D']
            ],
            [
                "type"=> "NL",
                "fdData"=>!isset($main1_final["NL"]) ? null : $main1_final["NL"],
                "jpData"=>!isset($main1_final["NLJP"]) ? null : $main1_final["NLJP"]
            ],
            [
                "type"=> "PD",
                "fdData"=>!isset($main2_final["PD"]) ? null : $main2_final["PD"]
            ],
            [
                "type"=> "LH",
                "fdData"=>!isset($main2_final["LH"]) ? null : $main2_final["LH"]
            ],
            [
                "type"=> "BN",
                "fdData"=>!isset($main4_final[0]) ? null : $main4_final[0]
            ]
        ];
        foreach ($final_array as $key => $value) {
            if(isset($value["fdData"])){
                //
            }else{
                if($value["type"] == "SG" && $value["jpData"] !== null){
                    // 
                }else{
                    unset($final_array[$key]);
                }
            }
        }
        return array_values($final_array);
    }
    public function sub_formatter($array,$date){
        return [
            "PD"=>[
                "dd" => !isset($array->N->DrawDate) ? $date : $array->N->DrawDate,
                "dn" => !isset($array->N->DrawID) ? "" : $array->N->DrawID,
                "c1" => !isset($array->N->C[0]) ? "----" : $array->N->C[0],
                "c2" => !isset($array->N->C[1]) ? "----" : $array->N->C[1],
                "c3" => !isset($array->N->C[2]) ? "----" : $array->N->C[2],
                "c4" => !isset($array->N->C[3]) ? "----" : $array->N->C[3],
                "c5" => !isset($array->N->C[4]) ? "----" : $array->N->C[4],
                "c6" => !isset($array->N->C[5]) ? "----" : $array->N->C[5],
                "c7" => !isset($array->N->C[6]) ? "----" : $array->N->C[6],
                "c8" => !isset($array->N->C[7]) ? "----" : $array->N->C[7],
                "c9" => !isset($array->N->C[8]) ? "----" : $array->N->C[8],
                "c10" => !isset($array->N->C[9]) ? "----" : $array->N->C[9],
                "n1" => !isset($array->N->_1[0]) ? "----" : $array->N->_1[0],
                "n1_pos" => !isset($array->N->_1pos[0]) ? "" : $array->N->_1pos[0],
                "n2" => !isset($array->N->_2[0]) ? "----" : $array->N->_2[0],
                "n2_pos" => !isset($array->N->_2pos[0]) ? "" : $array->N->_2pos[0],
                "n3" => !isset($array->N->_3[0]) ? "----" : $array->N->_3[0],
                "n3_pos" => !isset($array->N->_3pos[0]) ? "" : $array->N->_3pos[0],
                "s1" => !isset($array->N->_P[0]) ? "----" : $array->N->_P[0],
                "s2" => !isset($array->N->_P[1]) ? "----" : $array->N->_P[1],
                "s3" => !isset($array->N->_P[2]) ? "----" : $array->N->_P[2],
                "s4" => !isset($array->N->_P[3]) ? "----" : $array->N->_P[3],
                "s5" => !isset($array->N->_P[4]) ? "----" : $array->N->_P[4],
                "s6" => !isset($array->N->_P[5]) ? "----" : $array->N->_P[5],
                "s7" => !isset($array->N->_P[6]) ? "----" : $array->N->_P[6],
                "s8" => !isset($array->N->_P[7]) ? "----" : $array->N->_P[7],
                "s9" => !isset($array->N->_P[8]) ? "----" : $array->N->_P[8],
                "s10" => !isset($array->N->_P[9]) ? "----" : $array->N->_P[9],
                "s11" => !isset($array->N->_P[10]) ? "----" : $array->N->_P[10],
                "s12" => !isset($array->N->_P[11]) ? "----" : $array->N->_P[11],
                "s13" => !isset($array->N->_P[12]) ? "----" : $array->N->_P[12],
                "jp1" => !isset($array->N6D->_1[0]) ? "------" : $array->N6D->_1[0],
                "jp2" => !isset($array->N6D->_2[0]) ? "-----" : $array->N6D->_2[0],
                "jp3" => !isset($array->N6D->_2[1]) ? "-----" : $array->N6D->_2[1],
                "jp4" => !isset($array->N6D->_3[0]) ? "----" : $array->N6D->_3[0],
                "jp5" => !isset($array->N6D->_3[1]) ? "----" : $array->N6D->_3[1],
                "jp7" => !isset($array->N6D->_4[0]) ? "---" : $array->N6D->_4[0],
                "jp8" => !isset($array->N6D->_4[1]) ? "---" : $array->N6D->_4[1],
                "jp9" => !isset($array->N6D->_5[0]) ? "--" : $array->N6D->_5[0],
                "jp10" => !isset($array->N6D->_5[1]) ? "--" : $array->N6D->_5[1],
                "videoLink" => "https://player.twitch.tv/?channel=perdana4d&enableExtensions=true&muted=false&parent=twitch.tv&player=popout&volume=0.5"
            ],
            "LH"=>[
                "dd" => !isset($array->R->DrawDate) ? $date : $array->R->DrawDate,
                "dn" => !isset($array->R->DrawID) ? "" : $array->R->DrawID,
                "c1" => !isset($array->R->C[0]) ? "----" : $array->R->C[0],
                "c2" => !isset($array->R->C[1]) ? "----" : $array->R->C[1],
                "c3" => !isset($array->R->C[2]) ? "----" : $array->R->C[2],
                "c4" => !isset($array->R->C[3]) ? "----" : $array->R->C[3],
                "c5" => !isset($array->R->C[4]) ? "----" : $array->R->C[4],
                "c6" => !isset($array->R->C[5]) ? "----" : $array->R->C[5],
                "c7" => !isset($array->R->C[6]) ? "----" : $array->R->C[6],
                "c8" => !isset($array->R->C[7]) ? "----" : $array->R->C[7],
                "c9" => !isset($array->R->C[8]) ? "----" : $array->R->C[8],
                "c10" => !isset($array->R->C[9]) ? "----" : $array->R->C[9],
                "n1" => !isset($array->R->_1[0]) ? "----" : $array->R->_1[0],
                "n1_pos" => !isset($array->R->_1pos[0]) ? "" : $array->R->_1pos[0],
                "n2" => !isset($array->R->_2[0]) ? "----" : $array->R->_2[0],
                "n2_pos" => !isset($array->R->_2pos[0]) ? "" : $array->R->_2pos[0],
                "n3" => !isset($array->R->_3[0]) ? "----" : $array->R->_3[0],
                "n3_pos" => !isset($array->R->_3pos[0]) ? "" : $array->R->_3pos[0],
                "s1" => !isset($array->R->_P[0]) ? "----" : $array->R->_P[0],
                "s2" => !isset($array->R->_P[1]) ? "----" : $array->R->_P[1],
                "s3" => !isset($array->R->_P[2]) ? "----" : $array->R->_P[2],
                "s4" => !isset($array->R->_P[3]) ? "----" : $array->R->_P[3],
                "s5" => !isset($array->R->_P[4]) ? "----" : $array->R->_P[4],
                "s6" => !isset($array->R->_P[5]) ? "----" : $array->R->_P[5],
                "s7" => !isset($array->R->_P[6]) ? "----" : $array->R->_P[6],
                "s8" => !isset($array->R->_P[7]) ? "----" : $array->R->_P[7],
                "s9" => !isset($array->R->_P[8]) ? "----" : $array->R->_P[8],
                "s10" => !isset($array->R->_P[9]) ? "----" : $array->R->_P[9],
                "s11" => !isset($array->R->_P[10]) ? "----" : $array->R->_P[10],
                "s12" => !isset($array->R->_P[11]) ? "----" : $array->R->_P[11],
                "s13" => !isset($array->R->_P[12]) ? "----" : $array->R->_P[12],
                "videoLink" => ""
            ]
        ];
    }
    public function bn_formatter($bn_arr,$date){
        $bnt=array(
            0=>array()
        );
        if($bn_arr && isset($bn_arr->P1)){
            $bnt[0]["dd"] = $date;
            $bnt[0]["dn"] = "";
            $bnt[0]["c1"] = $bn_arr->{14};
            $bnt[0]["c2"] = $bn_arr->{15};
            $bnt[0]["c3"] = $bn_arr->{16};
            $bnt[0]["c4"] = $bn_arr->{17};
            $bnt[0]["c5"] = $bn_arr->{18};
            $bnt[0]["c6"] = $bn_arr->{19};
            $bnt[0]["c7"] = $bn_arr->{20};
            $bnt[0]["c8"] = $bn_arr->{21};
            $bnt[0]["c9"] = $bn_arr->{22};
            $bnt[0]["c10"] = $bn_arr->{23};
            $bnt[0]["n1"] = $bn_arr->P1;
            $bnt[0]["n1_pos"] = $bn_arr->P1OriPosition;
            $bnt[0]["n2"] = $bn_arr->P2;
            $bnt[0]["n2_pos"] = $bn_arr->P2OriPosition;
            $bnt[0]["n3"] = $bn_arr->P3;
            $bnt[0]["n3_pos"] = $bn_arr->P3OriPosition;
            $bnt[0]["s1"] = $bn_arr->{1};
            $bnt[0]["s2"] = $bn_arr->{2};
            $bnt[0]["s3"] = $bn_arr->{3};
            $bnt[0]["s4"] = $bn_arr->{4};
            $bnt[0]["s5"] = $bn_arr->{5};
            $bnt[0]["s6"] = $bn_arr->{6};
            $bnt[0]["s7"] = $bn_arr->{7};
            $bnt[0]["s8"] = $bn_arr->{8};
            $bnt[0]["s9"] = $bn_arr->{9};
            $bnt[0]["s10"] = $bn_arr->{10};
            $bnt[0]["s11"] = $bn_arr->{11};
            $bnt[0]["s12"] = $bn_arr->{12};
            $bnt[0]["s13"] = $bn_arr->{13};
            $bnt[0]['jp5d1'] = $bn_arr->{'5D1'};
            $bnt[0]['jp5d2'] = $bn_arr->{'5D2'};
            $bnt[0]['jp5d3'] = $bn_arr->{'5D3'};
            $bnt[0]['jp6d1'] = $bn_arr->{'6D1'};
            $bnt[0]['jp7d1'] = $bn_arr->{'7D1'};
        }else{
            $bnt[0]["dd"] = $date;
            $bnt[0]["dn"] = "";
            $bnt[0]["c1"] = "----";
            $bnt[0]["c2"] = "----";
            $bnt[0]["c3"] = "----";
            $bnt[0]["c4"] = "----";
            $bnt[0]["c5"] = "----";
            $bnt[0]["c6"] = "----";
            $bnt[0]["c7"] = "----";
            $bnt[0]["c8"] = "----";
            $bnt[0]["c9"] = "----";
            $bnt[0]["c10"] = "----";
            $bnt[0]["n1"] = "----";
            $bnt[0]["n1_pos"] = "";
            $bnt[0]["n2"] = "----";
            $bnt[0]["n2_pos"] = "";
            $bnt[0]["n3"] = "----";
            $bnt[0]["n3_pos"] = "";
            $bnt[0]["s1"] = "----";
            $bnt[0]["s2"] = "----";
            $bnt[0]["s3"] = "----";
            $bnt[0]["s4"] = "----";
            $bnt[0]["s5"] = "----";
            $bnt[0]["s6"] = "----";
            $bnt[0]["s7"] = "----";
            $bnt[0]["s8"] = "----";
            $bnt[0]["s9"] = "----";
            $bnt[0]["s10"] = "----";
            $bnt[0]["s11"] = "----";
            $bnt[0]["s12"] = "----";
            $bnt[0]["s13"] = "----";
            $bnt[0]['jp5d1'] = "-----";
            $bnt[0]['jp5d2'] = "-----";
            $bnt[0]['jp5d3'] = "-----";
            $bnt[0]['jp6d1'] = "------";
            $bnt[0]['jp7d1'] = "-------";
        }
        
        return $bnt;
    }
    public function main1_formatter($data){
        $format_data = [];
        foreach ($data as $vars) {
            if(isset($vars->fdData)){
                $format_data[$vars->type] = $vars->fdData;
            }elseif(isset($vars->jpData)){
                if(isset($vars->jpData->jp_type)){
                    $format_data[$vars->type.$vars->jpData->jp_type] = $vars->jpData;
                }else{
                    $format_data[$vars->type] = $vars->jpData;
                }
                
            }elseif(isset($vars->jpData1)){
                $format_data[$vars->type] = $vars->jpData1;
            }
        }
        return $format_data;
    }
}
