<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

/**************************************************************************************************************/

class Address extends Model {
    const SESSION_ERROR = "AddressError";

    /**********************************************************************************************************/

    public static function getAddr($nrcep) {
        $nrcep = str_replace('-', '', $nrcep);
        $url = 'http://viacep.com.br/ws/' . $nrcep . '/json/';
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $addr = json_decode(curl_exec($ch), true);

        curl_close($ch);

        return $addr;
    }

    /**********************************************************************************************************/

    public function loadFromCEP($nrcep) {
        $addr = Address::getAddr($nrcep);

        if (isset($addr['logradouro']) && $addr['logradouro']) {
            $this->setdesaddress($addr['logradouro']);
            $this->setdescomplement($addr['complemento']);
            $this->setdesdistrict($addr['bairro']);
            $this->setdescity($addr['localidade']);
            $this->setdesstate($addr['uf']);
            $this->setdescountry('Brasil');
            $this->setdeszipcode(str_replace('-', '', $nrcep));
        }
    }

    /**********************************************************************************************************/

    public function save() {
        $sql = new Sql();

        $results = $sql->select("
            CALL sp_addresses_save(
                :idaddress,
                :idperson,
                :desaddress,
                :desnumber,
                :descomplement,
                :descity,
                :desstate,
                :descountry,
                :deszipcode,
                :desdistrict
            )
        ", [
            ':idaddress'        =>  $this->getidaddress(),
            ':idperson'         =>  $this->getidperson(),
            ':desaddress'       =>  utf8_decode($this->getdesaddress()),
            ':desnumber'        =>  $this->getdesnumber(),
            ':descomplement'    =>  utf8_decode($this->getdescomplement()),
            ':descity'          =>  utf8_decode($this->getdescity()),
            ':desstate'         =>  $this->getdesstate(),
            ':descountry'       =>  $this->getdescountry(),
            ':deszipcode'       =>  $this->getdeszipcode(),
            ':desdistrict'      =>  utf8_decode($this->getdesdistrict())
        ]);

        if (count($results) > 0) $this->setData($results[0]);
    }

    /**********************************************************************************************************/

    public static function getFieldName($dbName) {
        switch ($dbName) {
            case 'desaddress':
                return 'a rua';
                break;

            case 'descomplement':
                return 'o complemento';
                break;

            case 'descity':
                return 'a cidade';
                break;

            case 'desstate':
                return 'o estado';
                break;

            case 'descountry':
                return 'o país';
                break;

            case 'zipcode':
                return 'o CEP';
                break;

            case 'desdistrict':
                return 'o bairro';
                break;
        }
    }

    /**********************************************************************************************************/

    public static function setMsgError($msg) {
        $_SESSION[Address::SESSION_ERROR] = $msg;
    }

    /**********************************************************************************************************/

    public static function getMsgError() {
        $msg = (isset($_SESSION[Address::SESSION_ERROR]) && $_SESSION[Address::SESSION_ERROR]) ? $_SESSION[Address::SESSION_ERROR] : '';
        Address::clearMsgError();

        return $msg;
    }

    /**********************************************************************************************************/

    public static function clearMsgError() {
        $_SESSION[Address::SESSION_ERROR] = NULL;
    }
}