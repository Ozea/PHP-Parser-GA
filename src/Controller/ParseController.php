<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 7/25/18
 * Time: 1:36 PM
 */

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Class ParseController
 * @Route("/search")
 * @package App\Controller
 */
class ParseController extends AbstractController
{
    /**
     * @Route("/{searchParam}")
     */
    public function show($searchParam){
        $page = 1;
        $result = [];
        $idIndex = 0;
        $client = new \GuzzleHttp\Client();

        while($page < 2) {

            $clientResponse3_1 = $client->request('GET', 'http://www.benett-auto.md/search_by_code/_sqquery='.$searchParam.'/_sqsearchtype=like/');
            $html3_1 = '' . $clientResponse3_1->getBody();

            $crawler = new Crawler($html3_1);

            $all = $crawler->filter('.beforeproductslist > p > strong')->text();
            $allItems = str_replace(array(" товаров", " ") , "" , $all);

            $clientResponse3_0 = $client->request('GET', 'http://www.benett-auto.md/search_by_code/_sqquery='.$searchParam.'/_sqsearchtype=like/_searchmethod=code/_allitems='.$allItems.'/_page='.$page.'/_listtype=inner/');
            $html3_0 =''. $clientResponse3_0->getBody();

            $crawler = new Crawler($html3_0);

            $items3 = $crawler->filter('tr')->each(function (Crawler $node) use(&$idIndex){
                $link = $node->attr('data-href');
                $number = $node->filter('.image + td > a')->text();
                $name = $node->filter('.image + td + td + td > a')->text();
                $brand = $node->filter('.image + td + td')->text();
                $price = $node->filter('.image + td + td + td + td + td')->text();
                $price = htmlentities($price, null, 'utf-8');
                $price = str_replace(array(' ', ','), array('', '.'), $price);
                $price = html_entity_decode($price);
                $quantity = $node->filter('.image + td + td + td + td')->text();
                $item = [
                    'id' => $idIndex++,
                    'site' => 'http://www.benett-auto.md',
                    'number' => $number,
                    'name' => $name,
                    'brand' => $brand,
                    'price' => $price,
                    'quantity' => $quantity,
                    'link' => $link
                ];
                return $item;
            });

            $clientResponse2 = $client->request('GET', 'http://partner.olmosdon.md/xpdo/searchbyarticle.php?keywordpost='.$searchParam);
            $html2 ='' . $clientResponse2->getBody();



            $crawler = new Crawler($html2);

            $items2 = $crawler->filter('.products > tbody > .list')->each(function (Crawler $node) use (&$idIndex){
                $number = $node->filter('.articleNo > a > span')->text();
                $name = $node->filter('.hand > a')->text();
                $brand = $node->filter('.hand + td > a')->text();
                $price = $node->filter('.hand + .hand + td > a')->text();
                $quantity = $node->filter('.show-stocks')->text();
                $link = $node->filter('.hand > a')->attr('href');
                $item = [
                    'id' => $idIndex++,
                    'site' => 'http://partner.olmosdon.md/',
                    'number' => $number,
                    'name' => $name,
                    'brand' => $brand,
                    'price' => str_replace(' ', '', $price),
                    'quantity' => $quantity,
                    'link' => $link
                ];

                return $item;
            });

            $clientResponse = $client->request('GET', 'https://automall.md/Catalog/Search?currentPage=' . $page . '&view=0&number=' . $searchParam);
            $html = '' . $clientResponse->getBody();

            $crawler = new Crawler($html);

            $items = $crawler->filter('aside > table > tbody > tr')->each(function (Crawler $node) use (&$idIndex){
                $number = $node->filter('.number > a')->text();
                $link = $node->filter('.number > a')->attr('href');
                $name = $node->filter('.name > p > a')->text();
                $brand = $node->filter('.name + td > a')->text();
                $price = $node->filter('.price > a')->text();
                $price = htmlentities($price, null, 'utf-8');
                $price = str_replace(array("&nbsp;", ','), array('', '.'), $price);
                $price = html_entity_decode($price);
                $quantity = $node->filter('.price-retail + td>a')->text();
                $store = $node->filter('.cart-last > .cart-in')->text();
                $item = [
                    'id' => $idIndex++,
                    'site' => 'https://automall.md',
                    'number' => $number,
                    'link' => $link,
                    'name' => $name,
                    'brand' => $brand,
                    'price' => $price,
                    'quantity' => $quantity,
                    'store' => $store
                ];

                return $item;
            });
            $result = array_merge($result, $items, $items2, $items3);
            $page++;
        }

        $Json = new JsonResponse($result);

        return $Json;
    }
}