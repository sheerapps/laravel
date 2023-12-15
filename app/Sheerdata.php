<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sheerdata extends Model
{
    protected $table = "sheerdata";
    protected $fillable = ["type","dd","dn","n1","n2","n3","n1_pos","n2_pos","n3_pos","n11","n12","n13","s1","s2","s3","s4","s5","s6","s7","s8","s9","s10","s11","s12","s13","c1","c2","c3","c4","c5","c6","c7","c8","c9","c10","videoLink"];
}
