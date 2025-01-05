<?php

namespace App\Helpers;

Class HyperpayResponseCodesHelper {
    /*
      |--------------------------------------------------------------------------
      | HyperpayResponseCodesHelper that contains payment related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use payment processes
      |
     */

    /**
     * Description of HyperpayResponseCodesHelper
     *
     * @author ILSA Interactive
     */
    public static $pending_success_codes = [
//        Result codes for pending transactions
        '000.200.000',
        '000.200.001',
        '000.200.100',
        '000.200.101',
        '000.200.102',
        '000.200.103',
        '000.200.200',
        '100.400.500',
        '800.400.500',
        '800.400.501',
        '800.400.502',
    ];
    public static $success_codes = [
//        Result codes for successfully processed transactions
        '000.000.000',
        '000.000.100',
        '000.100.110',
        '000.100.111',
        '000.100.112',
        '000.300.000',
        '000.300.100',
        '000.300.101',
        '000.300.102',
        '000.310.100',
        '000.310.101',
        '000.310.110',
        '000.600.000',
//        Result codes for successfully processed transactions that should be manually reviewed
        '000.400.000',
        '000.400.010',
        '000.400.020',
        '000.400.040',
        '000.400.050',
        '000.400.060',
        '000.400.070',
        '000.400.080',
        '000.400.081',
        '000.400.082',
        '000.400.090',
        '000.400.100',
//        Result codes for pending transactions
        '000.200.000',
        '000.200.001',
        '000.200.100',
        '000.200.101',
        '000.200.102',
        '000.200.103',
        '000.200.200',
        '100.400.500',
        '800.400.500',
        '800.400.501',
        '800.400.502',
    ];

}

?>