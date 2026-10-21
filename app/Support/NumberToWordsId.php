<?php
namespace App\Support;

class NumberToWordsId
{
    public function convert($number)
    {
        $number = abs(intval($number));
        $u = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
        if ($number < 12) {
            return $u[$number];
        }
        if ($number < 20) {
            return $this->convert($number - 10) . " belas";
        }
        if ($number < 100) {
            return $this->convert(intval($number / 10)) . " puluh " . $this->convert($number % 10);
        }
        if ($number < 200) {
            return "seratus " . $this->convert($number - 100);
        }
        if ($number < 1000) {
            return $this->convert(intval($number / 100)) . " ratus " . $this->convert($number % 100);
        }
        if ($number < 2000) {
            return "seribu " . $this->convert($number - 1000);
        }
        if ($number < 1000000) {
            return $this->convert(intval($number / 1000)) . " ribu " . $this->convert($number % 1000);
        }
        if ($number < 1000000000) {
            return $this->convert(intval($number / 1000000)) . " juta " . $this->convert($number % 1000000);
        }
        if ($number < 1000000000000) {
            return $this->convert(intval($number / 1000000000)) . " miliar " . $this->convert($number % 1000000000);
        }
        return $this->convert(intval($number / 1000000000000)) . " triliun " . $this->convert($number % 1000000000000);
    }
}