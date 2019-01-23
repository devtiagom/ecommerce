<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

/**************************************************************************************************************/

class Product extends Model {
    
    /**********************************************************************************************************/

    public static function listAll() {
        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");
    }

    /**********************************************************************************************************/

    public static function checkProductsList($productsList) {
        foreach ($productsList as &$row) {
            $p = new Product();
            $p->setData($row);
            $row = $p->getValues();
        }

        return $productsList;
    }

    /**********************************************************************************************************/

    public function save() {
        $sql = new Sql();

        $values = $this->getValues();

        $results = $sql->select(
            "CALL sp_products_save (
                :idproduct,
                :desproduct,
                :vlprice,
                :vlwidth,
                :vlheight,
                :vllength,
                :vlweight,
                :desurl
            )",
            array(
                ":idproduct"    =>  $values["idproduct"] ?? NULL,
                ":desproduct"   =>  $values["desproduct"],
                ":vlprice"      =>  $values["vlprice"],
                ":vlwidth"      =>  $values["vlwidth"],
                ":vlheight"     =>  $values["vlheight"],
                ":vllength"     =>  $values["vllength"],
                ":vlweight"     =>  $values["vlweight"],
                ":desurl"       =>  $values["desurl"]
            )
        );

        $this->setData($results[0]);
    }

    /**********************************************************************************************************/

    public function get($idProduct) {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct", array(
            ":idproduct"   =>  $idProduct
        ));

        $this->setData($results[0]);
    }

    /**********************************************************************************************************/

    public function delete() {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct", array(
            ":idproduct"   =>  $this->getidproduct()
        ));
    }

    /**********************************************************************************************************/

    public function checkPhoto() {
        if (file_exists(
            $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR .
            "res" . DIRECTORY_SEPARATOR .
            "site" . DIRECTORY_SEPARATOR .
            "img" . DIRECTORY_SEPARATOR .
            "products" . DIRECTORY_SEPARATOR .
            $this->getidproduct() . ".jpg"
        )) {
            $url = "/res/site/img/products/" . $this->getidproduct() . ".jpg";
        } else {
            $url = "/res/site/img/product.jpg";
        }

        return $this->setdesphoto($url);
    }

    /**********************************************************************************************************/

    public function getValues() {
        $this->checkPhoto();

        return parent::getValues();
    }

    /**********************************************************************************************************/

    public function setPhoto($file) {
        $imgType = explode(".", $file["name"]);
        $imgType = end($imgType);

        switch ($imgType) {
            case "jpg":
            case "jpeg":
                $img = imagecreatefromjpeg($file["tmp_name"]);
            break;

            case "gif":
                $img = imagecreatefromgif($file["tmp_name"]);
            break;

            case "png":
                $img = imagecreatefrompng($file["tmp_name"]);
            break;
        }

        $imgDst = $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR .
            "res" . DIRECTORY_SEPARATOR .
            "site" . DIRECTORY_SEPARATOR .
            "img" . DIRECTORY_SEPARATOR .
            "products" . DIRECTORY_SEPARATOR .
            $this->getidproduct() . ".jpg";
        
        imagejpeg($img, $imgDst);
        imagedestroy($img);

        $this->checkPhoto();
    }

    /**********************************************************************************************************/

    public function getFromURL($desurl) {
        $sql = new Sql();

        $rows = $sql->select("SELECT * FROM tb_products WHERE desurl = :desurl LIMIT 1", [
            ":desurl"   =>  $desurl
        ]);

        $this->setData($rows[0]);
    }

    /**********************************************************************************************************/

    public function getCategories() {
        $sql = new Sql();

        return $sql->select("
            SELECT * FROM tb_categories a
            INNER JOIN tb_productscategories b ON a.idcategory = b.idcategory
            WHERE b.idproduct = :idproduct
        ", [
            ":idproduct"    =>  $this->getidproduct()
        ]);
    }

    /**********************************************************************************************************/

    public static function getPage($page = 1, $itensPerPage = 10) {
        $start = ($page - 1) * $itensPerPage;

        $sql = new Sql();

        $results = $sql->select(
            "   SELECT      SQL_CALC_FOUND_ROWS *
                FROM        tb_products
                ORDER BY    desproduct
                LIMIT       $start, $itensPerPage;
            "
        );

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

        return array(
            "data"  =>  $results,
            "total" =>  (int)$resultTotal[0]["nrtotal"],
            "pages" =>  ceil($resultTotal[0]["nrtotal"] / $itensPerPage)
        );
    }

    /**********************************************************************************************************/

    public static function getPageSearch($search, $page = 1, $itensPerPage = 10) {
        $start = ($page - 1) * $itensPerPage;

        $sql = new Sql();

        $results = $sql->select(
            "   SELECT      SQL_CALC_FOUND_ROWS *
                FROM        tb_products
                WHERE       desproduct LIKE :search
                ORDER BY    desproduct
                LIMIT       $start, $itensPerPage;
            ", [
                ':search'   =>  '%' . $search . '%'
            ]
        );

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

        return array(
            "data"  =>  $results,
            "total" =>  (int)$resultTotal[0]["nrtotal"],
            "pages" =>  ceil($resultTotal[0]["nrtotal"] / $itensPerPage)
        );
    }

}