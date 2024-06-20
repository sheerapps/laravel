<?php

namespace App\Http\Controllers;

use App\Sheerdata;
use App\Sheerlive;

use Illuminate\Http\Request;
use DateTime;
use \DB;

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
    public function saveLive(){
        $dateNow = now()->toDateString(); // Get current date in YYYY-MM-DD format
        $timeNow = now()->format('H:i:s'); // Get current time in h:i:s format
        $data = json_encode([$timeNow]); // Encode current time as JSON array
        Sheerlive::updateOrInsert(
            ['date' => $dateNow],
            ['data' => $data]
        );
        echo "done";
    }

    public function saveData($date){
        $data = $this->getMainByDateV1_1_0($date);
        foreach ($data as $item) {
            if(isset($item['fdData'])){
                $fdData = $item['fdData'];
                // echo isset($fdData->dd) ? $fdData->dd : $item['type']."===";
                Sheerdata::updateOrInsert(
                    ['dd' => $fdData->dd, 'type' => $item['type']],
                    [
                        'type' => $item['type'],
                        'dd' => isset($fdData->dd) ? $fdData->dd : "",
                        'dn' => isset($fdData->dn) ? $fdData->dn : "",
                        'n1' => isset($fdData->n1) ? $fdData->n1 : "",
                        'n2' => isset($fdData->n2) ? $fdData->n2 : "",
                        'n3' => isset($fdData->n3) ? $fdData->n3 : "",
                        'n1_pos' => isset($fdData->n1_pos) ? $fdData->n1_pos : "",
                        'n2_pos' => isset($fdData->n2_pos) ? $fdData->n2_pos : "",
                        'n3_pos' => isset($fdData->n3_pos) ? $fdData->n3_pos : "",
                        'n11' => isset($fdData->n11) ? $fdData->n11 : "",
                        'n12' => isset($fdData->n12) ? $fdData->n12 : "",
                        'n13' => isset($fdData->n13) ? $fdData->n13 : "",
                        's1' => isset($fdData->s1) ? $fdData->s1 : "",
                        's2' => isset($fdData->s2) ? $fdData->s2 : "",
                        's3' => isset($fdData->s3) ? $fdData->s3 : "",
                        's4' => isset($fdData->s4) ? $fdData->s4 : "",
                        's5' => isset($fdData->s5) ? $fdData->s5 : "",
                        's6' => isset($fdData->s6) ? $fdData->s6 : "",
                        's7' => isset($fdData->s7) ? $fdData->s7 : "",
                        's8' => isset($fdData->s8) ? $fdData->s8 : "",
                        's9' => isset($fdData->s9) ? $fdData->s9 : "",
                        's10' => isset($fdData->s10) ? $fdData->s10 : "",
                        's11' => isset($fdData->s11) ? $fdData->s11 : "",
                        's12' => isset($fdData->s12) ? $fdData->s12 : "",
                        's13' => isset($fdData->s13) ? $fdData->s13 : "",
                        'c1' => isset($fdData->c1) ? $fdData->c1 : "",
                        'c2' => isset($fdData->c2) ? $fdData->c2 : "",
                        'c3' => isset($fdData->c3) ? $fdData->c3 : "",
                        'c4' => isset($fdData->c4) ? $fdData->c4 : "",
                        'c5' => isset($fdData->c5) ? $fdData->c5 : "",
                        'c6' => isset($fdData->c6) ? $fdData->c6 : "",
                        'c7' => isset($fdData->c7) ? $fdData->c7 : "",
                        'c8' => isset($fdData->c8) ? $fdData->c8 : "",
                        'c9' => isset($fdData->c9) ? $fdData->c9 : "",
                        'c10' => isset($fdData->c10) ? $fdData->c10 : ""
                    ]
                );
                echo $item['type']."dd=".$fdData->dd." done <p>";
            }
        }
    }
    public function encodeCH($string){
        if(preg_match('/[\x{4e00}}-\x{9fa5}]/u', $string)){
            $string = urlencode($string);
            return $string;
        }
        return $string;
    }
    public function getDicByData(Request $request)
    {
        $res1json = null;
        $res2json = null;
        $resp = null;
        $search = $request->search ? $request->search : "search";
        $search = str_replace(' ','%20',$search);
        $search = $this->encodeCH($search);
        $type = $request->type ? $request->type : "def";
        $page = $request->page ? $request->page : "1";
        // if (preg_match('/[\'^£$%&*()}{#@#~?!><>,|=_&+¬-]/', $request->search)) { $search = "search"; }
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
            $resp = array(
                "main"=>isset($res1json) ? $res1json : null,
                "m4d"=>isset($res2json) ? $res2json : null,
                "pic"=>array(
                    "qzt"=>"https://prddmccms1.blob.core.windows.net/number-dictionary/KEY_NO.jpg",
                    "m"=>"https://magnum4d.my/Magnum4d/media/4D-Dictionary/KEY_NO.gif",
                    "gym"=>"https://repo.4dmanager.com/qzt/gym/KEY_NO.png",
                    "tpk"=>"https://repo.4dmanager.com/qzt/tpk/KEY_NO.png",
                )
            );
            return $resp;
        }else{
            return null;
        }
    }
    public function getBookAll(Request $request){
        $page = $request->page;
        $ch1 = curl_init("https://api.4dmanager.com/api/qzt?t=wzt&p=$page");
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch1, CURLOPT_CONNECTTIMEOUT, 2);
        $res1 = curl_exec($ch1);
        $res1json = json_decode($res1);
        // if(isset($res1json) && isset($res1json->qzt[45])){
        //     if($res1json->qzt[45]->no == "0046"){
        //         $res1json->qzt[45]->no = "046";
        //     }
        // }
        $all = array(
            "main"=>isset($res1json) ? $res1json : null,
            "image"=>"https://prddmccms1.blob.core.windows.net/number-dictionary/KEY_NO.jpg"
        );
        return $all;
    }

    public function getDrawdateData(Request $request){
        $year = $request->year;
        $month = $request->month;
        $ch1 = curl_init("https://app-apdapi-prod-southeastasia-01.azurewebsites.net/draw-dates/past/$year/$month");
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch1, CURLOPT_CONNECTTIMEOUT, 2);
        $res1 = curl_exec($ch1);
        $res1json = json_decode($res1);
        
        $main = null;
        if(isset($res1json) && isset($res1json->PastDrawDates->Draw)){
            if($res1json && is_array($res1json->PastDrawDates->Draw)){
                $main = $res1json;
            }else if(!is_array($res1json->PastDrawDates->Draw)){
                $res1json->PastDrawDates->Draw = [$res1json->PastDrawDates->Draw];
                $main = $res1json;
            }
        }
        
        return array(
            "main"=>$main,
            "special"=>array(
                "sp"=>true,
                "dd"=>"2024-06-25"
            )
        );
    }
    function manipulateString($string) {
    // Remove any non-numeric characters
    $string = preg_replace("/[^0-9]/", "", $string);

    // If the length of the string is between 3 and 4 and is a number
    if (strlen($string) >= 3 && strlen($string) <= 4 && is_numeric($string)) {
        return $string;
    }
    // If the string is longer than 4, keep only the first 4 digits
    elseif (strlen($string) > 4) {
        return substr($string, 0, 4);
    }
    // If the string is less than 3, add leading zeros until the string has 4 digits
    elseif (strlen($string) < 3) {
        while (strlen($string) < 4) {
            $string = '0' . $string;
        }
        return $string;
    }
    return "1234";
}
    public function getDataBySearch(Request $request){
        $number = isset($request->no) && $request->no !== "...." && $request->no !== "----" ? $request->no : "7777";
        $number = $this->manipulateString($number);
        $permutation = isset($request->multi) ? "true" : "false";
        $select4D = "";
        // $selected4D = [];
        if(isset($request->service)){
            // foreach ($request->service as $k => $s){
                $select4D = $request->service;
                // $select4D .= $k.",";
                // $selected4D[$k] = true;
            // }
        }else{
            $select4D = "M,ST,PMP";
            // $selected4D["M"] = true;
            // $selected4D["PMP"] = true;
            // $selected4D["ST"] = true;
        }
        $hisjson = $this->historyData($permutation,$select4D,$number);
        // number pic API
        // $ch1 = curl_init("https://api.4dmanager.com/api/no_qzt?no=$number");
        // curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch1, CURLOPT_TIMEOUT, 2);
        // curl_setopt($ch1, CURLOPT_CONNECTTIMEOUT, 2);
        // $res1 = curl_exec($ch1);
        // $direcjson = json_decode($res1);
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
            "G" => 0,
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
                // if($v->prize == "首獎"){
                //     $v->prize = "First";
                //     $przCount["st"]+=1;
                // }elseif($v->prize == "二獎"){
                //     $v->prize = "Second";
                //     $przCount["nd"]+=1;
                // }elseif($v->prize == "三獎"){
                //     $v->prize = "Third";
                //     $przCount["rd"]+=1;
                // }elseif($v->prize == "特別獎"){
                //     $v->prize = "Sp";
                //     $przCount["sp"]+=1;
                // }elseif($v->prize == "安慰獎"){
                //     $v->prize = "Cp";
                //     $przCount["cp"]+=1;
                // }else
                if($v->prize == "First"){
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
            "no" => $number,
            "sites" => $select4D,
            "history" => $hisjson,
            "prize" => $przCount,
            "count" => $sitesCount
        ];
    }

    public function getDataByAdvanceSearch(Request $request){
        //add prize & number
        $number = isset($request->no) && $request->no !== "...." && $request->no !== "----" ? $request->no : "7777";
        $number = $this->manipulateString($number);
        $permutation = isset($request->multi) ? "true" : "false";
        $view4d = isset($request->no) ? $request->no : "1234";
        $prize = "First,Second,Third,Sp,Cp";
        if(isset($request->prize)){
            $prize = $request->prize;
        }
        if(isset($request->view4d)){
            $view4d = $request->view4d;
        }
        $select4D = ""; 
        // $selected4D = [];
        if(isset($request->service)){
            // foreach ($request->service as $k => $s){
                $select4D = $request->service;
                // $select4D .= $k.",";
                // $selected4D[$k] = true;
            // }
        }else{
            $select4D = "M,ST,PMP";
            // $selected4D["M"] = true;
            // $selected4D["PMP"] = true;
            // $selected4D["ST"] = true;
        }
        $hisjson = $this->historyAdvanceData($permutation,$select4D,$number,$prize,$view4d);

        // number pic API
        // $ch1 = curl_init("https://api.4dmanager.com/api/no_qzt?no=$number");
        // curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch1, CURLOPT_TIMEOUT, 2);
        // curl_setopt($ch1, CURLOPT_CONNECTTIMEOUT, 2);
        // $res1 = curl_exec($ch1);
        // $direcjson = json_decode($res1);
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
            "G" => 0,
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
                // if($v->prize == "首獎"){
                //     $v->prize = "First";
                //     $przCount["st"]+=1;
                // }elseif($v->prize == "二獎"){
                //     $v->prize = "Second";
                //     $przCount["nd"]+=1;
                // }elseif($v->prize == "三獎"){
                //     $v->prize = "Third";
                //     $przCount["rd"]+=1;
                // }elseif($v->prize == "特別獎"){
                //     $v->prize = "Sp";
                //     $przCount["sp"]+=1;
                // }elseif($v->prize == "安慰獎"){
                //     $v->prize = "Cp";
                //     $przCount["cp"]+=1;
                // }else
                if($v->prize == "First"){
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
            "no" => $number,
            "sites" => $select4D,
            "history" => $hisjson,//all
            "prize" => $przCount,//all
            "count" => $sitesCount//all
        ];
    }

    public function multiPermutation($arg) {
        $array = is_string($arg) ? str_split($arg) : $arg;
        if(1 === count($array)) {
            return $array;
        }
        $result = array();
        foreach($array as $key => $item) {
            foreach($this->multiPermutation(array_diff_key($array, array($key => $item))) as $p) {
                $result[] = $item . $p;
            }
        }
        sort($result);
        return array_values(array_unique($result));
    }
    public function historyData($permutation,$sites,$number){
        $sitesToArray = explode(",",$sites);
        $sitesFilter = "'" .implode("','",$sitesToArray). "'";
        // return $sitesFilter;
        if($permutation == 'true'){
            $number =  implode(",",$this->multiPermutation($number));
        }
        $sql = "select c.* from (";
        $sql.= "(select dd,type,n1 as num, 'First' as prize from sheerdata a where n1 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,n2 as num, 'Second' as prize from sheerdata b where n2 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,n3 as num, 'Third' as prize from sheerdata b where n3 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s1 as num, 'Sp' as prize from sheerdata b where s1 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s2 as num, 'Sp' as prize from sheerdata b where s2 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s3 as num, 'Sp' as prize from sheerdata b where s3 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s4 as num, 'Sp' as prize from sheerdata b where s4 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s5 as num, 'Sp' as prize from sheerdata b where s5 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s6 as num, 'Sp' as prize from sheerdata b where s6 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s7 as num, 'Sp' as prize from sheerdata b where s7 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s8 as num, 'Sp' as prize from sheerdata b where s8 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s9 as num, 'Sp' as prize from sheerdata b where s9 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s10 as num, 'Sp' as prize from sheerdata b where s10 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s11 as num, 'Sp' as prize from sheerdata b where s11 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s12 as num, 'Sp' as prize from sheerdata b where s12 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,s13 as num, 'Sp' as prize from sheerdata b where s13 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c1 as num, 'Cp' as prize from sheerdata b where c1 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c2 as num, 'Cp' as prize from sheerdata b where c2 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c3 as num, 'Cp' as prize from sheerdata b where c3 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c4 as num, 'Cp' as prize from sheerdata b where c4 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c5 as num, 'Cp' as prize from sheerdata b where c5 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c6 as num, 'Cp' as prize from sheerdata b where c6 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c7 as num, 'Cp' as prize from sheerdata b where c7 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c8 as num, 'Cp' as prize from sheerdata b where c8 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c9 as num, 'Cp' as prize from sheerdata b where c9 IN ($number) and type IN ($sitesFilter)) union ";
        $sql.= "(select dd,type,c10 as num, 'Cp' as prize from sheerdata b where c10 IN ($number) and type IN ($sitesFilter)))";
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
    public function historyAdvanceData($permutation,$sites,$number,$prize,$view4d){
        $sitesToArray = explode(",",$sites);
        $sitesFilter = "'" .implode("','",$sitesToArray). "'";
        // if($permutation == 'true'){
        //     $number =  implode(",",$this->multiPermutation($number));
        // }
        $number = $view4d;
        $prizeToArray  = explode(",",$prize);
        $sql = "select c.* from (";
        foreach ($prizeToArray as $key => $value) {
            if($value == "First"){
                $sql.= "(select dd,type,n1 as num, 'First' as prize from sheerdata a where n1 IN ($number) and type IN ($sitesFilter)) union ";

            }
            if($value == "Second"){
                $sql.= "(select dd,type,n2 as num, 'Second' as prize from sheerdata b where n2 IN ($number) and type IN ($sitesFilter)) union ";

            }
            if($value == "Third"){
                $sql.= "(select dd,type,n3 as num, 'Third' as prize from sheerdata b where n3 IN ($number) and type IN ($sitesFilter)) union ";

            }
            if($value == "Sp"){
                $sql.= "(select dd,type,s1 as num, 'Sp' as prize from sheerdata b where s1 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,s2 as num, 'Sp' as prize from sheerdata b where s2 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,s3 as num, 'Sp' as prize from sheerdata b where s3 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,s4 as num, 'Sp' as prize from sheerdata b where s4 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,s5 as num, 'Sp' as prize from sheerdata b where s5 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,s6 as num, 'Sp' as prize from sheerdata b where s6 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,s7 as num, 'Sp' as prize from sheerdata b where s7 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,s8 as num, 'Sp' as prize from sheerdata b where s8 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,s9 as num, 'Sp' as prize from sheerdata b where s9 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,s10 as num, 'Sp' as prize from sheerdata b where s10 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,s11 as num, 'Sp' as prize from sheerdata b where s11 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,s12 as num, 'Sp' as prize from sheerdata b where s12 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,s13 as num, 'Sp' as prize from sheerdata b where s13 IN ($number) and type IN ($sitesFilter)) union ";
            }
            if($value == "Cp"){
                $sql.= "(select dd,type,c1 as num, 'Cp' as prize from sheerdata b where c1 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,c2 as num, 'Cp' as prize from sheerdata b where c2 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,c3 as num, 'Cp' as prize from sheerdata b where c3 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,c4 as num, 'Cp' as prize from sheerdata b where c4 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,c5 as num, 'Cp' as prize from sheerdata b where c5 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,c6 as num, 'Cp' as prize from sheerdata b where c6 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,c7 as num, 'Cp' as prize from sheerdata b where c7 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,c8 as num, 'Cp' as prize from sheerdata b where c8 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,c9 as num, 'Cp' as prize from sheerdata b where c9 IN ($number) and type IN ($sitesFilter)) union ";
                $sql.= "(select dd,type,c10 as num, 'Cp' as prize from sheerdata b where c10 IN ($number) and type IN ($sitesFilter)) union ";
            }
        }
        // union  to )
        $sql = preg_replace('/(union)(?![\s\S]*\bunion\b)/', ')', $sql);
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
        date_default_timezone_set('Asia/Kuala_Lumpur');
        $today = date("Y-m-d");
        //live
        $url_main = "https://mapp.fast4dking.com/nocache/result_v23.json";
        $url_sub = "https://4dyes3.com/getLiveResult.php";
        $url_nl = "https://mobile.fast4dking.com/v2/nocache/result_nl_v24.json";
        //bydate
        if($date == "date" || $date >= $today){
            $date = $today;
        }else{
            //past
            $url_main = "https://mapp.fast4dking.com/past_results_v23.php?d=".$date;
            $url_sub = "https://4dyes3.com/getLiveResult.php?date=".$date;
            $url_nl = "past";
        }
        //is Live

        if($date == $today && date("Gi") <= 1829){
            $today_live = new DateTime($today);
            $today_live->modify('-1 days');
            $date = $today_live->format('Y-m-d');
            $url_sub = "https://4dyes3.com/getLiveResult.php?date=".$date;
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
        curl_setopt($ch2, CURLOPT_HTTPHEADER, ['referer: https://4dyes3.com/en/past-result']);
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
            $main1_final["NLJP1"] = isset($main3) && isset($main3[1]) ? $main3[1]->jpData1 : null;
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
        $sjpFinal  = null;
        if(!isset($main1_final['SGJP6/45'])){
            $ch6 = curl_init("https://app-6.4dking.com.my/past_results_v23.php?t=SG&d=".$date);
            curl_setopt($ch6, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch6, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch6, CURLOPT_CONNECTTIMEOUT, 1);
            $res6 = curl_exec($ch6);
            $main6 = json_decode($res6);
            //format
            if (isset($main6)) {
                $keys = array_column($main6, 'type');
                $index = array_search('SGJP', $keys);
                if(isset($index) && $index >= 0){
                    if(isset($main6[$index]->jpData)){
                        $sjpFinal = $main6[$index]->jpData;
                    }
                }
            }
        }else{
            $sjpFinal = $main1_final['SGJP6/45'];
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
                    "gold"=>!isset($main1_final['MJPGOLD']) ? null : $main1_final['MJPGOLD'],
                    "life"=>!isset($main1_final['MJPLIFE']) ? null : $main1_final['MJPLIFE']
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
                    "jp50"=>!isset($main1_final['STJP6/50']) ? null : $main1_final['STJP6/50'],
                    "jp55"=>!isset($main1_final['STJP6/55']) ? null : $main1_final['STJP6/55'],
                    "jp58"=>!isset($main1_final['STJP6/58']) ? null : $main1_final['STJP6/58']
                ]
            ],
            [
                "type"=> "SG",
                "fdData"=>!isset($main1_final['SG']) ? null :$main1_final['SG'],
                "jpData"=>$sjpFinal,
                "sweep"=>"https://lottery.nestia.com/sweep",
                "decode"=>"var classNames1 = ['adsbygoogle', 'adsbygoogle-noablate'];
                classNames1.forEach(function(className) {
                    var elements = document.querySelectorAll('.' + className);
                    elements.forEach(function(element) {
                        element.style.display = 'none';
                    });
                });
            
                document.body.style.padding = '0px';
            
                var classNames2 = ['n-header', 'result-header', 'resultHeader', 'adsbygoogle', 'FDTitleText', 'FDTitleText2', 'Disclaimer','sticky_bottom','tbl-next-up-mobile-position-bottom'];
                classNames2.forEach(function(className) {
                    var elements = document.querySelectorAll('.' + className);
                    elements.forEach(function(element) {
                        element.style.display = 'none';
                    });
                });
            
                var taboolaElement = document.getElementById('taboola-below-article-thumbnails');
                if (taboolaElement) {
                    taboolaElement.style.display = 'none';
                }
            
                var tblNextUpElement = document.getElementById('tbl-next-up');
                var tblNextUpMobilePositionBottomElements = document.querySelectorAll('.tbl-next-up-mobile-position-bottom');
                if (tblNextUpElement || tblNextUpMobilePositionBottomElements.length > 0) {
                    tblNextUpElement.style.display = 'none';
                    tblNextUpMobilePositionBottomElements.forEach(function(element) {
                        element.style.display = 'none';
                    });
                }"
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
                "jpData"=>!isset($sbjp_formatter) ? null : $sbjp_formatter
            ],
            [
                "type"=> "GD",
                "fdData"=>!isset($main1_final['GD']) ? null :$main1_final['GD'],
                "jpData"=>!isset($main1_final['GD6D']) ? null : $main1_final['GD6D']
            ],
            [
                "type"=> "NL",
                "fdData"=>!isset($main1_final["NL"]) ? null : $main1_final["NL"],
                "jpData"=>!isset($main1_final["NLJP1"]) ? null : $main1_final["NLJP1"]
            ],
            [
                "type"=> "PD",
                "fdData"=>!isset($main2_final["PD"]) ? null : (object)$main2_final["PD"],
            ],
            [
                "type"=> "LH",
                "fdData"=>!isset($main2_final["LH"]) ? null : (object)$main2_final["LH"]
            ],
            [
                "type"=> "BN",
                "fdData"=>!isset($main4_final[0]) ? null : (object)$main4_final[0],
                "bonus"=>"https://bt4dg.live/draw_result.html",
                "decode"=>"var elementsById = ['page-title' ,'header', 'footer', 'loadingVideoRow'];
                elementsById.forEach(function(id) {
                    var element = document.getElementById(id);
                    if (element) {
                        element.style.display = 'none';
                    }
                });
            
                // Hide elements by class
                var elementsByClass = document.querySelectorAll('.section-title');
                elementsByClass.forEach(function(element) {
                    element.style.display = 'none';
                });
            
                // Change body background color
                document.body.style.backgroundColor = '#710b09';
            
                // Change content-wrap padding
                var contentWrapElements = document.querySelectorAll('.content-wrap');
                contentWrapElements.forEach(function(element) {
                    element.style.padding = '0px';
                });"
            ],
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

    public function getMainByDateV1_1_0($date){
        date_default_timezone_set('Asia/Kuala_Lumpur');
        $today = date("Y-m-d");
        //live
        $url_main = "https://mapp.fast4dking.com/nocache/result_v23.json";
        $url_sub = "https://4dyes3.com/getLiveResult.php";
        $url_nl = "https://mobile.fast4dking.com/v2/nocache/result_nl_v24.json";
        //bydate
        if($date == "date" || $date >= $today){
            $date = $today;
        }else{
            //past
            $url_main = "https://mapp.fast4dking.com/past_results_v23.php?d=".$date;
            $url_sub = "https://4dyes3.com/getLiveResult.php?date=".$date;
            $url_nl = "past";
        }
        //is Live

        if($date == $today && date("Gi") <= 1829){
            $today_live = new DateTime($today);
            $today_live->modify('-1 days');
            $date = $today_live->format('Y-m-d');
            $url_sub = "https://4dyes3.com/getLiveResult.php?date=".$date;
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

        $ch2 = curl_init($url_sub);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, ['referer: https://4dyes3.com/en/past-result']);
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
            $main1_final["NLJP1"] = isset($main3) && isset($main3[1]) ? $main3[1]->jpData1 : null;
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
        $sjpFinal  = null;
        if(!isset($main1_final['SGJP6/45'])){
            $ch6 = curl_init("https://app-6.4dking.com.my/past_results_v23.php?t=SG&d=".$date);
            curl_setopt($ch6, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch6, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch6, CURLOPT_CONNECTTIMEOUT, 1);
            $res6 = curl_exec($ch6);
            $main6 = json_decode($res6);
            //format
            if (isset($main6)) {
                $keys = array_column($main6, 'type');
                $index = array_search('SGJP', $keys);
                if(isset($index) && $index >= 0){
                    if(isset($main6[$index]->jpData)){
                        $sjpFinal = $main6[$index]->jpData;
                    }
                }
            }
        }else{
            $sjpFinal = $main1_final['SGJP6/45'];
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
                    "gold"=>!isset($main1_final['MJPGOLD']) ? null : $main1_final['MJPGOLD'],
                    "life"=>!isset($main1_final['MJPLIFE']) ? null : $main1_final['MJPLIFE']
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
                    "jp50"=>!isset($main1_final['STJP6/50']) ? null : $main1_final['STJP6/50'],
                    "jp55"=>!isset($main1_final['STJP6/55']) ? null : $main1_final['STJP6/55'],
                    "jp58"=>!isset($main1_final['STJP6/58']) ? null : $main1_final['STJP6/58']
                ]
            ],
            [
                "type"=> "SG",
                "fdData"=>!isset($main1_final['SG']) ? null :$main1_final['SG'],
                "jpData"=>$sjpFinal,
                "sweep"=>"https://lottery.nestia.com/sweep",
                "decode"=>"var classNames1 = ['adsbygoogle', 'adsbygoogle-noablate'];
                classNames1.forEach(function(className) {
                    var elements = document.querySelectorAll('.' + className);
                    elements.forEach(function(element) {
                        element.style.display = 'none';
                    });
                });
            
                document.body.style.padding = '0px';
            
                var classNames2 = ['n-header', 'result-header', 'resultHeader', 'adsbygoogle', 'FDTitleText', 'FDTitleText2', 'Disclaimer','sticky_bottom','tbl-next-up-mobile-position-bottom'];
                classNames2.forEach(function(className) {
                    var elements = document.querySelectorAll('.' + className);
                    elements.forEach(function(element) {
                        element.style.display = 'none';
                    });
                });
            
                var taboolaElement = document.getElementById('taboola-below-article-thumbnails');
                if (taboolaElement) {
                    taboolaElement.style.display = 'none';
                }
            
                var tblNextUpElement = document.getElementById('tbl-next-up');
                var tblNextUpMobilePositionBottomElements = document.querySelectorAll('.tbl-next-up-mobile-position-bottom');
                if (tblNextUpElement || tblNextUpMobilePositionBottomElements.length > 0) {
                    tblNextUpElement.style.display = 'none';
                    tblNextUpMobilePositionBottomElements.forEach(function(element) {
                        element.style.display = 'none';
                    });
                }"
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
                "jpData"=>!isset($sbjp_formatter) ? null : $sbjp_formatter
            ],
            [
                "type"=> "GD",
                "fdData"=>!isset($main1_final['GD']) ? null :$main1_final['GD'],
                "jpData"=>!isset($main1_final['GD6D']) ? null : $main1_final['GD6D']
            ],
            [
                "type"=> "NL",
                "fdData"=>!isset($main1_final["NL"]) ? null : $main1_final["NL"],
                "jpData"=>!isset($main1_final["NLJP1"]) ? null : $main1_final["NLJP1"]
            ],
            [
                "type"=> "PD",
                "fdData"=>!isset($main2_final["PD"]) ? null : (object)$main2_final["PD"],
                "decode"=>"var classNames2 = ['mobile-navbar','marquee'];
                classNames2.forEach(function(className) {
                    var elements = document.querySelectorAll('.' + className);
                    elements.forEach(function(element) {
                        element.style.display = 'none';
                    });
                });
                var iframes = document.getElementsByTagName('iframe');

                // Loop through each <iframe> tag and set its display style property to 'none'
                for (var i = 0; i < iframes.length; i++) {
                    iframes[i].style.display = 'none';
                }"
            ],
            [
                "type"=> "LH",
                "fdData"=>!isset($main2_final["LH"]) ? null : (object)$main2_final["LH"],
                "decode"=>"var classNames2 = ['footer', 'navbar', 'carousel', 'draw-result-btn-group'];
                classNames2.forEach(function(className) {
                    var elements = document.querySelectorAll('.' + className);
                    elements.forEach(function(element) {
                        element.style.display = 'none';
                    });
                });"
            ],
            [
                "type"=> "BN",
                "fdData"=>!isset($main4_final[0]) ? null : (object)$main4_final[0],
                "bonus"=>"https://bt4dg.live/draw_result.html",
                "decode"=>"var elementsById = ['page-title' ,'header', 'footer', 'loadingVideoRow'];
                elementsById.forEach(function(id) {
                    var element = document.getElementById(id);
                    if (element) {
                        element.style.display = 'none';
                    }
                });
            
                // Hide elements by class
                var elementsByClass = document.querySelectorAll('.section-title');
                elementsByClass.forEach(function(element) {
                    element.style.display = 'none';
                });
            
                // Change body background color
                document.body.style.backgroundColor = '#710b09';
            
                // Change content-wrap padding
                var contentWrapElements = document.querySelectorAll('.content-wrap');
                contentWrapElements.forEach(function(element) {
                    element.style.padding = '0px';
                });"
            ],
            [
                "type"=> "G",
                "fdData"=>!isset($main2_final["G"]) ? null : (object)$main2_final["G"]
            ],
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
    public function format_main2($array){
        $format_array = [];
        foreach ($array as $key => $value) {
            if(isset($value->fdData)){
                $format_array[str_replace(":", "", $value->type)] = $value->fdData;
            }elseif(isset($value->jpData)){
                if(isset($value->jpData->jp_type)){
                    $format_array[$value->type."".$value->jpData->jp_type] = $value->jpData;
                }else{
                    $format_array[$value->type] = $value->jpData;
                }
                
            }elseif(isset($value->jpData1)){
                $format_array[$value->type] = $value->jpData1;
            }
        }
        return $format_array;
    }
    public function getMainByDateV1_2_0($date){
        date_default_timezone_set('Asia/Kuala_Lumpur');
        $today = date("Y-m-d");
        //live
        $url_main = "https://mapp.fast4dking.com/nocache/result_v23.json";
        $url_sub = "https://4dyes3.com/getLiveResult.php";
        $url_sub_2 = "https://backend.4dnum.com/api/v1/result/latest";
        $url_nl = "https://mobile.fast4dking.com/v2/nocache/result_nl_v24.json";
        //bydate
        if($date == "date" || $date >= $today){
            $date = $today;
        }else{
            //past
            $url_main = "https://mapp.fast4dking.com/past_results_v23.php?d=".$date;
            $url_sub = "https://4dyes3.com/getLiveResult.php?date=".$date;
            $url_sub_2 = "https://backend.4dnum.com/api/v1/result/".$date;
            $url_nl = "past";
        }
        //is Live

        if($date == $today && date("Gi") <= 1829){
            $today_live = new DateTime($today);
            $today_live->modify('-1 days');
            $date = $today_live->format('Y-m-d');
            $url_sub = "https://4dyes3.com/getLiveResult.php?date=".$date;
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
        // sub url 2
        $ch_url_sub_2 = curl_init($url_sub_2);
        curl_setopt($ch_url_sub_2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_url_sub_2, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch_url_sub_2, CURLOPT_CONNECTTIMEOUT, 2);
        $res_url_sub_2 = curl_exec($ch_url_sub_2);
        $main2_final = json_decode($res_url_sub_2);
        // 
        if(!isset($main2_final)){
            $main2_final = [];
        }
        $format_sub2 = $this->format_main2($main2_final);
        //sub
        $ch2 = curl_init($url_sub);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, ['referer: https://4dyes3.com/en/past-result']);
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
            $main1_final["NLJP1"] = isset($main3) && isset($main3[1]) ? $main3[1]->jpData1 : null;
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
        $sjpFinal  = null;
        if(!isset($main1_final['SGJP6/45'])){
            $ch6 = curl_init("https://app-6.4dking.com.my/past_results_v23.php?t=SG&d=".$date);
            curl_setopt($ch6, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch6, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch6, CURLOPT_CONNECTTIMEOUT, 1);
            $res6 = curl_exec($ch6);
            $main6 = json_decode($res6);
            //format
            if (isset($main6)) {
                $keys = array_column($main6, 'type');
                $index = array_search('SGJP', $keys);
                if(isset($index) && $index >= 0){
                    if(isset($main6[$index]->jpData)){
                        $sjpFinal = $main6[$index]->jpData;
                    }
                }
            }
        }else{
            $sjpFinal = $main1_final['SGJP6/45'];
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
                    "gold"=>!isset($main1_final['MJPGOLD']) ? null : $main1_final['MJPGOLD'],
                    "life"=>!isset($main1_final['MJPLIFE']) ? null : $main1_final['MJPLIFE']
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
                    "jp50"=>!isset($main1_final['STJP6/50']) ? null : $main1_final['STJP6/50'],
                    "jp55"=>!isset($main1_final['STJP6/55']) ? null : $main1_final['STJP6/55'],
                    "jp58"=>!isset($main1_final['STJP6/58']) ? null : $main1_final['STJP6/58']
                ]
            ],
            [
                "type"=> "SG",
                "fdData"=>!isset($main1_final['SG']) ? null :$main1_final['SG'],
                "jpData"=>$sjpFinal,
                "sweep"=>"https://lottery.nestia.com/sweep",
                "decode"=>"var classNames1 = ['adsbygoogle', 'adsbygoogle-noablate'];
                classNames1.forEach(function(className) {
                    var elements = document.querySelectorAll('.' + className);
                    elements.forEach(function(element) {
                        element.style.display = 'none';
                    });
                });
            
                document.body.style.padding = '0px';
            
                var classNames2 = ['n-header', 'result-header', 'resultHeader', 'adsbygoogle', 'FDTitleText', 'FDTitleText2', 'Disclaimer','sticky_bottom','tbl-next-up-mobile-position-bottom'];
                classNames2.forEach(function(className) {
                    var elements = document.querySelectorAll('.' + className);
                    elements.forEach(function(element) {
                        element.style.display = 'none';
                    });
                });
            
                var taboolaElement = document.getElementById('taboola-below-article-thumbnails');
                if (taboolaElement) {
                    taboolaElement.style.display = 'none';
                }
            
                var tblNextUpElement = document.getElementById('tbl-next-up');
                var tblNextUpMobilePositionBottomElements = document.querySelectorAll('.tbl-next-up-mobile-position-bottom');
                if (tblNextUpElement || tblNextUpMobilePositionBottomElements.length > 0) {
                    tblNextUpElement.style.display = 'none';
                    tblNextUpMobilePositionBottomElements.forEach(function(element) {
                        element.style.display = 'none';
                    });
                }"
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
                "jpData"=>!isset($sbjp_formatter) ? null : $sbjp_formatter
            ],
            [
                "type"=> "GD",
                "fdData"=>!isset($main1_final['GD']) ? null :$main1_final['GD'],
                "jpData"=>!isset($main1_final['GD6D']) ? null : $main1_final['GD6D']
            ],
            [
                "type"=> "NL",
                "fdData"=>!isset($main1_final["NL"]) ? null : $main1_final["NL"],
                "jpData"=>!isset($main1_final["NLJP1"]) ? null : $main1_final["NLJP1"]
            ],
            [
                "type"=> "PD",
                "fdData"=>!isset($main2_final["PD"]) ? null : (object)$main2_final["PD"],
                "jpData"=>!isset($main2_final["PDJP"]) ? null : (object)$main2_final["PDJP"],
                "fdData330"=>!isset($format_sub2["PT1530"]) ? null : $format_sub2["PT1530"],
                
                "decode"=>"var classNames2 = ['mobile-navbar','marquee'];
                classNames2.forEach(function(className) {
                    var elements = document.querySelectorAll('.' + className);
                    elements.forEach(function(element) {
                        element.style.display = 'none';
                    });
                });
                var iframes = document.getElementsByTagName('iframe');

                // Loop through each <iframe> tag and set its display style property to 'none'
                for (var i = 0; i < iframes.length; i++) {
                    iframes[i].style.display = 'none';
                }"
            ],
            [
                "type"=> "LH",
                // "fdData"=>!isset($main2_final["LH"]) ? null : (object)$main2_final["LH"],
                "fdData"=>!isset($format_sub2["HT1930"]) ? null : $format_sub2["HT1930"],
                "fdData330"=>!isset($format_sub2["HT1530"]) ? null : $format_sub2["HT1530"],
                "jpData"=>!isset($format_sub2["HJPT1930"]) ? null : $format_sub2["HJPT1930"],
                "jpData330"=>!isset($format_sub2["HJPT1530"]) ? null : $format_sub2["HJPT1530"],
                "decode"=>"var classNames2 = ['footer', 'navbar', 'carousel', 'draw-result-btn-group'];
                classNames2.forEach(function(className) {
                    var elements = document.querySelectorAll('.' + className);
                    elements.forEach(function(element) {
                        element.style.display = 'none';
                    });
                });"
            ],
            [
                "type"=> "BN",
                "fdData"=>!isset($main4_final[0]) ? null : (object)$main4_final[0],
                "bonus"=>"https://bt4dg.live/draw_result.html",
                "decode"=>"var elementsById = ['page-title' ,'header', 'footer', 'loadingVideoRow'];
                elementsById.forEach(function(id) {
                    var element = document.getElementById(id);
                    if (element) {
                        element.style.display = 'none';
                    }
                });
            
                // Hide elements by class
                var elementsByClass = document.querySelectorAll('.section-title');
                elementsByClass.forEach(function(element) {
                    element.style.display = 'none';
                });
            
                // Change body background color
                document.body.style.backgroundColor = '#710b09';
            
                // Change content-wrap padding
                var contentWrapElements = document.querySelectorAll('.content-wrap');
                contentWrapElements.forEach(function(element) {
                    element.style.padding = '0px';
                });"
            ],
            [
                "type"=> "G",
                "fdData"=>!isset($main2_final["G"]) ? null : (object)$main2_final["G"]
            ],
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
    //tsheer
    public function getTMainByDate($date){
        date_default_timezone_set('Asia/Kuala_Lumpur');
        $today = date("Y-m-d");
        //live
        $url_main = "https://mapp.fast4dking.com/nocache/result_v23.json";
        $url_sub = "https://4dyes3.com/getLiveResult.php";
        $url_nl = "https://mobile.fast4dking.com/v2/nocache/result_nl_v24.json";
        //bydate
        if($date == "date" || $date >= $today){
            $date = $today;
        }else{
            //past
            $url_main = "https://mapp.fast4dking.com/past_results_v23.php?d=".$date;
            $url_sub = "https://4dyes3.com/getLiveResult.php?date=".$date;
            $url_nl = "past";
        }
        //is Live

        if($date == $today && date("Gi") <= 1829){
            $today_live = new DateTime($today);
            $today_live->modify('-1 days');
            $date = $today_live->format('Y-m-d');
            $url_sub = "https://4dyes3.com/getLiveResult.php?date=".$date;
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
        curl_setopt($ch2, CURLOPT_HTTPHEADER, ['referer: https://4dyes3.com/en/past-result']);
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
            $main1_final["NLJP1"] = isset($main3) && isset($main3[1]) ? $main3[1]->jpData1 : null;
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
        $sjpFinal  = null;
        if(!isset($main1_final['SGJP6/45'])){
            $ch6 = curl_init("https://app-6.4dking.com.my/past_results_v23.php?t=SG&d=".$date);
            curl_setopt($ch6, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch6, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch6, CURLOPT_CONNECTTIMEOUT, 1);
            $res6 = curl_exec($ch6);
            $main6 = json_decode($res6);
            //format
            if (isset($main6)) {
                $keys = array_column($main6, 'type');
                $index = array_search('SGJP', $keys);
                if(isset($index) && $index >= 0){
                    if(isset($main6[$index]->jpData)){
                        $sjpFinal = $main6[$index]->jpData;
                    }
                }
            }
        }else{
            $sjpFinal = $main1_final['SGJP6/45'];
        }

        //$main1_final main
        //$main2_final lhpn
        //$main4_final bn
        //$sbjp_formatter ee

        $final_array = [
            [
                "type"=> "ST",
                "fdData"=>!isset($main1_final['ST']) ? null :$main1_final['ST'],
                "jpData"=>[
                    "jp1"=>!isset($main1_final['STJP1']) ? null : $main1_final['STJP1'],
                    "jp50"=>!isset($main1_final['STJP6/50']) ? null : $main1_final['STJP6/50'],
                    "jp55"=>!isset($main1_final['STJP6/55']) ? null : $main1_final['STJP6/55'],
                    "jp58"=>!isset($main1_final['STJP6/58']) ? null : $main1_final['STJP6/58']
                ]
            ],
            [
                "type"=> "M",
                "fdData"=>!isset($main1_final['M']) ? null :$main1_final['M'],
                "jpData"=>[
                    "gold"=>!isset($main1_final['MJPGOLD']) ? null : $main1_final['MJPGOLD'],
                    "life"=>!isset($main1_final['MJPLIFE']) ? null : $main1_final['MJPLIFE']
                ]
            ],
            [
                "type"=> "PMP",
                "fdData"=>!isset($main1_final['PMP']) ? null :$main1_final['PMP'],
                "jpData"=>!isset($main1_final['PMPJP1']) ? null : $main1_final['PMPJP1']
            ],
            [
                "type"=> "SG",
                "fdData"=>!isset($main1_final['SG']) ? null :$main1_final['SG'],
                "jpData"=>$sjpFinal,
                "sweep"=>"https://lottery.nestia.com/sweep",
                "decode"=>"var classNames1 = ['adsbygoogle', 'adsbygoogle-noablate'];
                classNames1.forEach(function(className) {
                    var elements = document.querySelectorAll('.' + className);
                    elements.forEach(function(element) {
                        element.style.display = 'none';
                    });
                });
            
                document.body.style.padding = '0px';
            
                var classNames2 = ['n-header', 'result-header', 'resultHeader', 'adsbygoogle', 'FDTitleText', 'FDTitleText2', 'Disclaimer', 'sticky_bottom','tbl-next-up-mobile-position-bottom'];
                classNames2.forEach(function(className) {
                    var elements = document.querySelectorAll('.' + className);
                    elements.forEach(function(element) {
                        element.style.display = 'none';
                    });
                });
            
                var taboolaElement = document.getElementById('taboola-below-article-thumbnails');
                if (taboolaElement) {
                    taboolaElement.style.display = 'none';
                }
            
                var tblNextUpElement = document.getElementById('tbl-next-up');
                var tblNextUpMobilePositionBottomElements = document.querySelectorAll('.tbl-next-up-mobile-position-bottom');
                if (tblNextUpElement || tblNextUpMobilePositionBottomElements.length > 0) {
                    tblNextUpElement.style.display = 'none';
                    tblNextUpMobilePositionBottomElements.forEach(function(element) {
                        element.style.display = 'none';
                    });
                }"
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
                "jpData"=>!isset($sbjp_formatter) ? null : $sbjp_formatter
            ],
            [
                "type"=> "GD",
                "fdData"=>!isset($main1_final['GD']) ? null :$main1_final['GD'],
                "jpData"=>!isset($main1_final['GD6D']) ? null : $main1_final['GD6D']
            ],
            [
                "type"=> "NL",
                "fdData"=>!isset($main1_final["NL"]) ? null : $main1_final["NL"],
                "jpData"=>!isset($main1_final["NLJP1"]) ? null : $main1_final["NLJP1"]
            ],
            [
                "type"=> "PD",
                "fdData"=>!isset($main2_final["PD"]) ? null : (object)$main2_final["PD"]
            ],
            [
                "type"=> "LH",
                "fdData"=>!isset($main2_final["LH"]) ? null : (object)$main2_final["LH"]
            ],
            [
                "type"=> "BN",
                "fdData"=>!isset($main4_final[0]) ? null : (object)$main4_final[0],
                "bonus"=>"https://bt4dg.live/draw_result.html",
                "decode"=>"var elementsById = ['page-title' ,'header', 'footer', 'loadingVideoRow'];
                elementsById.forEach(function(id) {
                    var element = document.getElementById(id);
                    if (element) {
                        element.style.display = 'none';
                    }
                });
            
                // Hide elements by class
                var elementsByClass = document.querySelectorAll('.section-title');
                elementsByClass.forEach(function(element) {
                    element.style.display = 'none';
                });
            
                // Change body background color
                document.body.style.backgroundColor = '#710b09';
            
                // Change content-wrap padding
                var contentWrapElements = document.querySelectorAll('.content-wrap');
                contentWrapElements.forEach(function(element) {
                    element.style.padding = '0px';
                });"
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
            "PDJP"=>[
                "dd" => !isset($array->N6D->DrawDate) ? $date : $array->N6D->DrawDate,
                "dn" => !isset($array->N6D->DrawID) ? "" : $array->N6D->DrawID,
                "c1" => !isset($array->N6D->_1[0]) ? "------" : $array->N6D->_1[0],
                "c2" => !isset($array->N6D->_2[0]) ? "-----" : $array->N6D->_2[0],
                "c3" => !isset($array->N6D->_2[1]) ? "-----" : $array->N6D->_2[1],
                "c4" => !isset($array->N6D->_3[0]) ? "----" : $array->N6D->_3[0],
                "c5" => !isset($array->N6D->_3[1]) ? "----" : $array->N6D->_3[1],
                "c6" => !isset($array->N6D->_4[0]) ? "---" : $array->N6D->_4[0],
                "c7" => !isset($array->N6D->_4[1]) ? "---" : $array->N6D->_4[1],
                "c8" => !isset($array->N6D->_5[0]) ? "--" : $array->N6D->_5[0],
                "c9" => !isset($array->N6D->_5[1]) ? "--" : $array->N6D->_5[1],
            ],
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
                "pm330" => "https://www.perdana4d.net",
                "video" => "https://player.twitch.tv/?channel=perdana4d&enableExtensions=true&muted=false&parent=twitch.tv&player=popout&volume=0.5"
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
                // "jp1" => !isset($array->R6D->_1[0]) ? "------" : $array->R6D->_1[0],
                // "jp2" => !isset($array->R6D->_2[0]) ? "-----" : $array->R6D->_2[0],
                // "jp3" => !isset($array->R6D->_2[1]) ? "-----" : $array->R6D->_2[1],
                // "jp4" => !isset($array->R6D->_3[0]) ? "----" : $array->R6D->_3[0],
                // "jp5" => !isset($array->R6D->_3[1]) ? "----" : $array->R6D->_3[1],
                // "jp7" => !isset($array->R6D->_4[0]) ? "---" : $array->R6D->_4[0],
                // "jp8" => !isset($array->R6D->_4[1]) ? "---" : $array->R6D->_4[1],
                // "jp9" => !isset($array->R6D->_5[0]) ? "--" : $array->R6D->_5[0],
                // "jp10" => !isset($array->R6D->_5[1]) ? "--" : $array->R6D->_5[1],
                "pm330" => "https://hari4d.com/draw-result.php",
                "video" => "https://www.youtube.com/@HARIHARILUCKY4D/streams"
            ],
            "G"=>[
                "dd" => !isset($array->G->DrawDate) ? $date : $array->G->DrawDate,
                "dn" => !isset($array->G->DrawID) ? "" : $array->G->DrawID,
                "c1" => !isset($array->G->C[0]) ? "----" : $array->G->C[0],
                "c2" => !isset($array->G->C[1]) ? "----" : $array->G->C[1],
                "c3" => !isset($array->G->C[2]) ? "----" : $array->G->C[2],
                "c4" => !isset($array->G->C[3]) ? "----" : $array->G->C[3],
                "c5" => !isset($array->G->C[4]) ? "----" : $array->G->C[4],
                "c6" => !isset($array->G->C[5]) ? "----" : $array->G->C[5],
                "c7" => !isset($array->G->C[6]) ? "----" : $array->G->C[6],
                "c8" => !isset($array->G->C[7]) ? "----" : $array->G->C[7],
                "c9" => !isset($array->G->C[8]) ? "----" : $array->G->C[8],
                "c10" => !isset($array->G->C[9]) ? "----" : $array->G->C[9],
                "n1" => !isset($array->G->_1[0]) ? "----" : $array->G->_1[0],
                "n1_pos" => !isset($array->G->_1pos[0]) ? "" : $array->G->_1pos[0],
                "n2" => !isset($array->G->_2[0]) ? "----" : $array->G->_2[0],
                "n2_pos" => !isset($array->G->_2pos[0]) ? "" : $array->G->_2pos[0],
                "n3" => !isset($array->G->_3[0]) ? "----" : $array->G->_3[0],
                "n3_pos" => !isset($array->G->_3pos[0]) ? "" : $array->G->_3pos[0],
                "s1" => !isset($array->G->_P[0]) ? "----" : $array->G->_P[0],
                "s2" => !isset($array->G->_P[1]) ? "----" : $array->G->_P[1],
                "s3" => !isset($array->G->_P[2]) ? "----" : $array->G->_P[2],
                "s4" => !isset($array->G->_P[3]) ? "----" : $array->G->_P[3],
                "s5" => !isset($array->G->_P[4]) ? "----" : $array->G->_P[4],
                "s6" => !isset($array->G->_P[5]) ? "----" : $array->G->_P[5],
                "s7" => !isset($array->G->_P[6]) ? "----" : $array->G->_P[6],
                "s8" => !isset($array->G->_P[7]) ? "----" : $array->G->_P[7],
                "s9" => !isset($array->G->_P[8]) ? "----" : $array->G->_P[8],
                "s10" => !isset($array->G->_P[9]) ? "----" : $array->G->_P[9],
                "s11" => !isset($array->G->_P[10]) ? "----" : $array->G->_P[10],
                "s12" => !isset($array->G->_P[11]) ? "----" : $array->G->_P[11],
                "s13" => !isset($array->G->_P[12]) ? "----" : $array->G->_P[12],
                "jp1" => !isset($array->G6D->_1[0]) ? "------" : $array->G6D->_1[0],
                "jp2" => !isset($array->G6D->_2[0]) ? "-----" : $array->G6D->_2[0],
                "jp3" => !isset($array->G6D->_2[1]) ? "-----" : $array->G6D->_2[1],
                "jp4" => !isset($array->G6D->_3[0]) ? "----" : $array->G6D->_3[0],
                "jp5" => !isset($array->G6D->_3[1]) ? "----" : $array->G6D->_3[1],
                "jp7" => !isset($array->G6D->_4[0]) ? "---" : $array->G6D->_4[0],
                "jp8" => !isset($array->G6D->_4[1]) ? "---" : $array->G6D->_4[1],
                "jp9" => !isset($array->G6D->_5[0]) ? "--" : $array->G6D->_5[0],
                "jp10" => !isset($array->G6D->_5[1]) ? "--" : $array->G6D->_5[1],
                "pm330" => "https://good4d.net/en/home",
            ]
        ];
    }
    public function bn_formatter($bn_arr,$date){
        $bn=array(
            0=>array()
        );
        if($bn_arr && isset($bn_arr->P1)){
            $bn[0]["dd"] = $date;
            $bn[0]["dn"] = "";
            $bn[0]["c1"] = $bn_arr->{14};
            $bn[0]["c2"] = $bn_arr->{15};
            $bn[0]["c3"] = $bn_arr->{16};
            $bn[0]["c4"] = $bn_arr->{17};
            $bn[0]["c5"] = $bn_arr->{18};
            $bn[0]["c6"] = $bn_arr->{19};
            $bn[0]["c7"] = $bn_arr->{20};
            $bn[0]["c8"] = $bn_arr->{21};
            $bn[0]["c9"] = $bn_arr->{22};
            $bn[0]["c10"] = $bn_arr->{23};
            $bn[0]["n1"] = $bn_arr->P1;
            $bn[0]["n1_pos"] = $bn_arr->P1OriPosition;
            $bn[0]["n2"] = $bn_arr->P2;
            $bn[0]["n2_pos"] = $bn_arr->P2OriPosition;
            $bn[0]["n3"] = $bn_arr->P3;
            $bn[0]["n3_pos"] = $bn_arr->P3OriPosition;
            $bn[0]["s1"] = $bn_arr->{1};
            $bn[0]["s2"] = $bn_arr->{2};
            $bn[0]["s3"] = $bn_arr->{3};
            $bn[0]["s4"] = $bn_arr->{4};
            $bn[0]["s5"] = $bn_arr->{5};
            $bn[0]["s6"] = $bn_arr->{6};
            $bn[0]["s7"] = $bn_arr->{7};
            $bn[0]["s8"] = $bn_arr->{8};
            $bn[0]["s9"] = $bn_arr->{9};
            $bn[0]["s10"] = $bn_arr->{10};
            $bn[0]["s11"] = $bn_arr->{11};
            $bn[0]["s12"] = $bn_arr->{12};
            $bn[0]["s13"] = $bn_arr->{13};
            $bn[0]['jp5d1'] = $bn_arr->{'5D1'};
            $bn[0]['jp5d2'] = $bn_arr->{'5D2'};
            $bn[0]['jp5d3'] = $bn_arr->{'5D3'};
            $bn[0]['jp6d1'] = $bn_arr->{'6D1'};
            $bn[0]['jp7d1'] = $bn_arr->{'7D1'};
        }else{
            $bn[0]["dd"] = $date;
            $bn[0]["dn"] = "";
            $bn[0]["c1"] = "----";
            $bn[0]["c2"] = "----";
            $bn[0]["c3"] = "----";
            $bn[0]["c4"] = "----";
            $bn[0]["c5"] = "----";
            $bn[0]["c6"] = "----";
            $bn[0]["c7"] = "----";
            $bn[0]["c8"] = "----";
            $bn[0]["c9"] = "----";
            $bn[0]["c10"] = "----";
            $bn[0]["n1"] = "----";
            $bn[0]["n1_pos"] = "";
            $bn[0]["n2"] = "----";
            $bn[0]["n2_pos"] = "";
            $bn[0]["n3"] = "----";
            $bn[0]["n3_pos"] = "";
            $bn[0]["s1"] = "----";
            $bn[0]["s2"] = "----";
            $bn[0]["s3"] = "----";
            $bn[0]["s4"] = "----";
            $bn[0]["s5"] = "----";
            $bn[0]["s6"] = "----";
            $bn[0]["s7"] = "----";
            $bn[0]["s8"] = "----";
            $bn[0]["s9"] = "----";
            $bn[0]["s10"] = "----";
            $bn[0]["s11"] = "----";
            $bn[0]["s12"] = "----";
            $bn[0]["s13"] = "----";
            $bn[0]['jp5d1'] = "-----";
            $bn[0]['jp5d2'] = "-----";
            $bn[0]['jp5d3'] = "-----";
            $bn[0]['jp6d1'] = "------";
            $bn[0]['jp7d1'] = "-------";
        }
        
        return $bn;
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
