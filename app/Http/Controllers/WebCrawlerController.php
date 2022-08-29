<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WebCrawlerController extends Controller
{
    public function crawl($number_of_pages = 6) : Response
    {
        try {
            $base_url = 'https://agencyanalytics.com';
            $data = [
                'status' => FALSE,
                'result' => null
            ];

            //Validate number of pages as a valid number between 4 and 6
            $validator = Validator::make([
                'number_of_pages' => $number_of_pages
            ], [
                'number_of_pages' => 'numeric|min:4|max:6'
            ]);

            if ($validator->fails()) {
                $data['message'] = "Number of pages should be between 4 and 6. Current value is " . $number_of_pages . ".";
            } else {
                $results_page = $this->curlRequest($base_url);
                if ($results_page['http_code'] === 200) { // check if the webpage is up and working
                    $links = $this->generatePageLinks($results_page['data'], $number_of_pages, $base_url); // get the required number of links from the webpage
                    $number_of_pages = count($links);
                    $page_load_time = $total_words = $title_length = 0;
                    $crawl_http_code = $images_array = $internal_links_array = $external_links_array = [];
                    foreach ($links as $url_key => $url) {
                        $next_result = $this->curlRequest($url); // one by one get html content of every webpage available
                        $crawl_http_code[$url_key]['url'] = $url;
                        $crawl_http_code[$url_key]['http_code'] = $next_result['http_code']; // capture the http code
                        $page_load_time += $next_result['load_time']; // calculate total page load time of all webpages

                        if ($next_result['http_code'] === 200) {
                            $total_words += str_word_count(strip_tags($next_result['data'])); // calculate the total number of words on all webpages
                            $dom = new \DOMDocument();
                            @$dom->loadHTML($next_result['data']);

                            // check for available images on the webpage
                            $images = $dom->getElementsByTagname('img');
                            foreach ($images as $value) {
                                $image_source = $value->getAttribute('src');
                                if (empty($image_source)) {
                                    $image_source = $value->getAttribute('data-src');
                                }
                                array_push($images_array, $image_source);
                            }

                            // check for internal and external links on the webpage
                            $all_links = $dom->getElementsByTagName('a');
                            foreach ($all_links as $value) {
                                $href = $value->getAttribute('href');
                                $check_href = parse_url($href);
                                if (empty($check_href['scheme']) || ($check_href['scheme'] . '://' . $check_href['host']) == $base_url)
                                    array_push($internal_links_array, $href);
                                else
                                    array_push($external_links_array, $href);
                            }

                            // check for the length of title on webpage
                            $page_title = $dom->getElementsByTagName('title');
                            if ($page_title->length > 0)
                                $title_length += strlen($page_title->item(0)->textContent);
                        }
                    }

                    // Prepare result data to be displayed
                    $data['status'] = TRUE;
                    $data['message'] = "Webpage (" . $base_url . ") successfully crawled.";
                    $data['result']['http_code'] = $crawl_http_code;
                    $data['result']['details'] = [
                        'Number of Pages Crawled' => $number_of_pages,
                        'Number of Unique Images' => count(array_unique($images_array, SORT_STRING )),
                        'Number of Unique Internal Links' => count(array_unique($internal_links_array, SORT_STRING )),
                        'Number of Unique External Links' => count(array_unique($external_links_array, SORT_STRING )),
                        'Average Page Load (s)' => round($page_load_time/$number_of_pages, 2),
                        'Average Word Count' => round($total_words/$number_of_pages),
                        'Average Title Length' => round($title_length/$number_of_pages)
                    ];
                } else {
                    $data['message'] = "Webpage (" . $base_url . ") temporary down or invalid.";
                }
            }
            return response()->view('crawler_result', $data);
        } catch (\Exception $e) {
            echo PHP_EOL . PHP_EOL . date('c') . " Web Crawler failed due to : ".$e->getMessage();
            Log::error('Web Crawler Failed');
            report($e);
            // We can also trigger email to concerned person
        }
    }


    private function generatePageLinks($html, $number_of_links, $base_url) : array {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $content = $dom->getElementsByTagname('a');
        $links = [$base_url];
        foreach ($content as $item) {
            if($number_of_links > 1) {
                $next_url = $item->getAttribute('href');

                // continue if the link is of the same page
                if (empty($next_url) || $next_url[0] === '#' || $next_url[0] === '?' || $next_url === '/' || $next_url === $base_url)
                    continue;

                // if link is internal without domain name, append domain name to make complete URL
                if (parse_url($next_url, PHP_URL_SCHEME) == '')
                    $next_url = $base_url . $next_url;

                array_push($links, $next_url);
                $number_of_links--;
            } else {
                break;
            }
        }
        return $links;
    }


    // cURL to get the contents of webpage
    private function curlRequest($url) : array {
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_AUTOREFERER => TRUE,
            CURLOPT_HEADER => TRUE,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT => 600,
            CURLOPT_MAXREDIRS => 10
        );

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $data['data'] = curl_exec($curl);
        $data['http_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $data['load_time'] = curl_getinfo($curl, CURLINFO_TOTAL_TIME);
        return $data;
    }
}
