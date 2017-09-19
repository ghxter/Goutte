<?php
/**
 * Taobao
 *
 * @author     rock <rock_guo@hexu.org>
 * @copyright  Copyright (c) 2010-2014 hexu Org. (http://www.hexu.org )
 * @license    http://www.hexu.org/license/
 * @version    CVS: $Id: taobao.php,v 1.1 2017/09/18 11:50:11 rock_guo Exp $
 */


require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/Logger.php";

use Goutte\Client;

//
//$qqs = ["新手淘交流q群602548967",
//    "新手淘交流qq群602548967",
//    "新手淘交流qq群:602548961新手淘交流qq群:602548967新手淘交流qq群:602548962新手淘交流qq群:602548963新手淘交流qq群:602548964",
//    "新手淘交流qq群：602548967",
//    "新手淘交流qq群:602548961新手淘交流新手淘交流qq群：602548962新手淘交流qq群新手淘交流qq群：602548963新手淘交流qq群",
//    "新手淘交流qq群：602548967新手淘交流qq群新手淘交流qq群：602548962新手淘交流qq群新手淘交流qq群：602548963新手淘交流qq群"
//];
//foreach($qqs as $q) {
//    echo $q . PHP_EOL;
//    preg_match_all("/((q群|qq群|qq|q)[^0-9]*([0-9]+))/i", $q, $qqs);
//    print_r($qqs);
//
//}
//
//exit;

$client = new Client();
//$logger = Tracking_Logger::getInstance()->logGroups();

for ($i = 1; $i < 5000; $i++) {
    // $listUrl = "https://maijia.bbs.taobao.com/search.html?spm=a210m.7699124.0.0.5626320fBQ2iHL&keyword=qq%E7%BE%A4&page=" . $i;
    $listUrl = "https://maijia.bbs.taobao.com/search.html?spm=a210m.7699124.0.0.5626320fMBLf8V&keyword=%E6%97%BA%E6%97%BA%E7%BE%A4&page=" . $i;
    echo $listUrl . PHP_EOL;
    $crawler = $client->request("GET", $listUrl);
    $html = $crawler->html();

    preg_match_all("/<a.*href=\"(\/detail[^\"]+)\"[^>]+>(.*)<\/a>/i", $html, $matches);
    $html = $crawler = null;

    if (empty($matches[1])) {
        continue;
    }
    foreach ($matches[1] as $detailUrl) {
        $detailUrl = "https://maijia.bbs.taobao.com" . $detailUrl;
        /**
         * check is crawler-ed
         */
        if(Tracking_Logger::getInstance()->isExist(['detail_url' => $detailUrl])) {
            echo "[Notice]: detail url is crawler-ed `{$detailUrl}` \n";
            continue;
        } else {
            echo "[Notice]: Start url is `{$detailUrl}`\n";
        }

        $crawler = $client->request("GET", $detailUrl);
        $html = $crawler->text();

        preg_match_all("/.*((旺旺群号|旺旺|旺旺)[^\d\w]*([0-9]{7,15})).*/i", $html, $qqs);
        //preg_match_all("/((q群|qq群|qq|q)[^0-9]*([0-9]+))/i", $q, $qqs);

        if (!empty($qqs[3])) {
            $qqGroups = array_count_values($qqs[3]);
            $qqDescriptions = str_replace(["\t", "\n", " "], "", implode("#####", array_keys(array_count_values($qqs[0]))));

            foreach ($qqGroups as $group => $times) {
                /**
                 * insert into db
                 */
                $logGroupsParams = [
                    'list_url' => $listUrl,
                    'detail_url' => $detailUrl,
                    'group_num' => $group,
                    'group_times' => $times,
                    "description" => $qqDescriptions,
                ];
                print_r($logGroupsParams);
                try {
                    Tracking_Logger::getInstance()->logGroups($logGroupsParams);
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            }
        } else {
            /**
             * insert into db
             */
            $logGroupsParams = [
                'list_url' => $listUrl,
                'detail_url' => $detailUrl,
                'group_num' => '',
                'group_times' => 0,
                "description" => '',
            ];
            try {
                Tracking_Logger::getInstance()->logGroups($logGroupsParams);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
        sleep(mt_rand(1, 5));

    }

    sleep(mt_rand(1, 5));
}


