<?php

namespace App\Services;

class EntityService
{
    public static function findEntity($s)
    {
        $res = array(null, 'undefined');
        $dic = array(
            array('/EDF/9651/9501/9620', '5060', 'EDF / DPNT / DP2D'),
            array('/EDF/9651/9501/9621', '5050', 'EDF / DPNT / DIPDE'),
            array('/EDF/9651/9501/9661', null, 'EDF / DPNT / DCN'),
            array('/EDF/9651/9501/9662', '5100', 'EDF / DPNT / DPN'),
            array('/EDF/9651/9501/9793', null, 'EDF / DPNT / DTEAM'),
            array('/EDF/9651/9502/9577', '3000', 'EDF / DIPNN'),
            array('/EDF/9651/9503/9703', '5500', 'EDF / PER / EDF HYDRO'),
            array('/EDF/9651/9525/9659', null, 'EDF / DSC / DIR RECH DEV'),
            array('/EDF/9651/9550/9550-2', null, 'EDF / DIR ASIE PACIF'),
            array('/EDF/9651/9652/9748', null, 'EDF / DELEGATION GENERALE CHINE'),
            array('/EDF/9651/9668/9745', null, 'EDF / POLE GAZ ITALIE '),
            array('/EDF/9651/9669/9746', null, 'EDF / DIR INTERNATIONALE'),
            array('/EDF/9651/9670/9747', null, 'EDF / DIRES'),
            array('/EDF/9651/9801/9768', null, 'EDF / SECRETARIAT GEN'),
            array('/EDF/9651/9802/9734', null, 'EDF / PRESIDENCE DG'),
            array('/EDF/9651/9802/9744', null, 'EDF / PRESIDENCE DG'),
            array('/EDF/9651/9802/9761', null, 'EDF / PRESIDENCE DG'),
            array('/EDF/9651/9803/9708', null, 'EDF / DTEO / DIR ACHATS GPE'),
            array('/EDF/9651/9803/9715', null, 'EDF / DTEO / DIG'),
            array('/EDF/9651/9803/9731', null, 'EDF / DTEO / DSP'),
            array('/EDF/9651/9803/9769', null, 'EDF / DTEO / DSI GROUPE'),
            array('/EDF/9651/9803/9771', null, 'EDF / DTEO'),
            array('/EDF/9651/9803/9772', null, 'EDF / DTEO / EDF CONSEIL'),
            array('/EDF/9651/9803/9773', null, 'EDF / DTEO / DO'),
            array('/EDF/9651/9803/9774', null, 'EDF / DTEO / SP'),
            array('/EDF/9651/9803/9775', null, 'EDF / DTEO / DS IT'),
            array('/EDF/9651/9803/9776', null, 'EDF / DTEO / DS T'),
            array('/EDF/9651/9803/9803-2', null, 'EDF / DTEO'),
            array('/EDF/9651/9804/9720', null, 'EDF / DRH GROUPE'),
            array('/EDF/9651/9804/9722', null, 'EDF / DRH GROUPE'),
            array('/EDF/9651/9804/9723', null, 'EDF / DRH GROUPE'),
            array('/EDF/9651/9804/9764', null, 'EDF / DRH GROUPE'),
            array('/EDF/9651/9804/9765', null, 'EDF / DRH GROUPE'),
            array('/EDF/9651/9804/9766', null, 'EDF / DRH GROUPE'),
            array('/EDF/9651/9804/9767', null, 'EDF / DRH GROUPE'),
            array('/EDF/9651/9804/9790', null, 'EDF / DRH GROUPE'),
            array('/EDF/9651/9804/9791', null, 'EDF / DRH GROUPE'),
            array('/EDF/9651/9804/9792', null, 'EDF / DRH GROUPE'),
            array('/EDF/9651/9804/9804-2', null, 'EDF / DRH GROUPE'),
            array('/EDF/9651/9806/9755', null, 'EDF / DIR COMMUNICATION'),
            array('/EDF/9651/9806/9756', null, 'EDF / DIR COMMUNICATION'),
            array('/EDF/9651/9806/9770', null, 'EDF / DIR COMMUNICATION'),
            array('/EDF/9651/9806/9806-2', null, 'EDF / DIR COMMUNICATION'),
            array('/EDF/9651/9808/9730', null, 'EDF / COTSEI / DIR SEI'),
            array('/EDF/9651/9808/9762', null, 'EDF / COTSEI / COMMERCE'),
            array('/EDF/9651/9808/9763', null, 'EDF / COTSEI / DOAAT'),
            array('/EDF/9651/9809/9809-2', null, 'EDF / EDF ENERGY BRA'),
            array('/EDF/9651/9810/9712', null, 'EDF / FINANCES'),
            array('/EDF/9651/9810/9713', null, 'EDF / FINANCES'),
            array('/EDF/9651/9810/9714', null, 'EDF / FINANCES'),
            array('/EDF/9651/9810/9719', null, 'EDF / FINANCES'),
            array('/EDF/9651/9810/9778', null, 'EDF / FINANCES'),
            array('/EDF/9651/9810/9779', null, 'EDF / FINANCES'),
            array('/EDF/9651/9810/9794', null, 'EDF / FINANCES'),
            array('/EDF/9651/9810/9795', null, 'EDF / FINANCES'),
            array('/EDF/9651/9810/9796', null, 'EDF / FINANCES'),
            array('/EDF/9651/9810/9810-2', null, 'EDF / FINANCES'),
            array('/EDF/9938/9838/9838-2', null, 'DK LNG SAS / DK LNG'),
            array('/EDF/9940/9840/9840-2', '0450', 'EDF PEI'),
            array('/EDF/9964', null, 'ENEDIS'),
            array('/EDF/9964/9513', null, 'ENEDIS'),
        );

        foreach ($dic as $kv) {
            if (substr($s, 0, strlen($kv[0])) == $kv[0]) {
                $res = array($kv[1], $kv[2]);
                break;
            }
        }

        return $res;
    }
}
