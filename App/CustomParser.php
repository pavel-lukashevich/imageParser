<?php

namespace App;

use phpQuery;
use phpQueryObject;

class CustomParser
{
    public $homeUrl = '';
    public $pageLinks = [];
    public $error = '';
    public $userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36';

    /**
     * CustomParser constructor.
     * @param string $homeUrl
     */
    public function __construct(string $homeUrl)
    {
        $url = rtrim(trim($homeUrl), '/');
        $curlResponse = $this->getCurlResponse($url);

        if ($curlResponse['info']['http_code'] == 200) {
            $this->homeUrl = $url;
        } else {
            $this->error = 'Page not available, enter the correct link.';
        }
    }

    public function createImagesSitemap()
    {
        $this->fillPageLinksBySitemap();
        if (empty($this->pageLinks)) {
            $this->createPageLinks();
        }
    }

    public function fillPageLinksBySitemap()
    {
        $sitemapLink = $this->getSitemapLink();
        $this->addPageLinksBySitemap($sitemapLink);

        if (!empty($this->pageLinks)) {
            $this->addImagesLinks();
        }
    }

    public function getSitemapLink()
    {
        $url = $this->homeUrl;
        $robotsUrl = $url . '/robots.txt';

        $curlResponse = $this->getCurlResponse($robotsUrl);

        $sitemapUrl = $url . '/sitemap.xml';
        if ($curlResponse['info']['http_code'] == 200) {
            preg_match("#Sitemap:\s*([^\s]*)#is", $curlResponse['html'], $match);
            if (isset($match[1]) && !empty($match[1])) {
                $sitemapUrl = $match[1];
            }
        }
        return $sitemapUrl;
    }

    /**
     * @param string $url
     * @return bool
     */
    public function addPageLinksBySitemap(string $url)
    {
        $curlResponse = $this->getCurlResponse($url);

        if ($curlResponse['info']['http_code'] == 200) {
            preg_match_all("#<loc>([^<]*)</loc>#is", $curlResponse['html'], $match);
            if (isset($match[1]) && !empty($match[1])) {
                $links = $match[1];
                foreach ($links as $link) {
                    if (strpos($link, '.xml')) {
                        $this->addPageLinksBySitemap($link);
                    } else {
                        $this->pageLinks[$link] = false;
                    }
                }
            }
        }
        return true;
    }

    public function addImagesLinks()
    {
        foreach ($this->pageLinks as $pageLink => $imagesLinks) {
            $this->getImagesLinksByPage($pageLink);
        }
    }

    /**
     * @param string $pageLink
     */
    public function getImagesLinksByPage(string $pageLink)
    {
        if ($this->pageLinks[$pageLink] === false) {
            $curlResponse = $this->getCurlResponse($pageLink);
            $document = phpQuery::newDocument($curlResponse['html']);
            $this->fillImagesLinks($document, $pageLink);

            phpQuery::unloadDocuments($document);
        }
    }

    /**
     * @param phpQueryObject $document
     * @param string $pageLink
     */
    public function fillImagesLinks(phpQueryObject $document, string $pageLink)
    {
        $this->pageLinks[$pageLink] = [];
        $imageTags = $document->find('img');

        foreach ($imageTags as $imageTag) {
            $src = pq($imageTag)->attr('src');
            if (strpos($src, 'data:') === 0) {
                $dataSrc = pq($imageTag)->attr('data-src');
                $src = empty($dataSrc) ? $src : $dataSrc;
            }
            if (strpos($src, 'http') === 0 || strpos($src, '//') === 0) {
                //
            } elseif (strpos($src, '/') === 0) {
                $src = $pageLink . '/' . ltrim($src, '/');
            } else {
                $src = $this->homeUrl . '/' . $src;
            }
            if (!in_array($src, $this->pageLinks[$pageLink])) {
                $this->pageLinks[$pageLink][] = $src;
            }
        }
    }

    /**
     * @param null|string $pageLink
     * @return bool
     */
    public function createPageLinks(?string $pageLink = null)
    {
        $pageLink = $pageLink ?? $this->homeUrl;
        if (array_key_exists($pageLink, $this->pageLinks) && $this->pageLinks[$pageLink] !== false) {
            return false;
        }

        $newLinks = [];

        $curlResponse = $this->getCurlResponse($pageLink);

        $document = phpQuery::newDocument($curlResponse['html']);
        $this->fillImagesLinks($document, $pageLink);
        $aTags = $document->find('a');
        foreach ($aTags as $aTag) {
            $link = pq($aTag)->attr('href');
            if (empty($link)) {
                continue;
            }
            $link = $this->preparePageLink($link);
            if (empty($link)) {
                continue;
            }

            if (!array_key_exists($link, $this->pageLinks)) {
                $newLinks[$link] = false;
                $this->pageLinks[$link] = false;
            }
        }

        phpQuery::unloadDocuments($document);

        if(!empty($newLinks)) {
            foreach ($newLinks as $key => $value) {
                $this->createPageLinks($key);
            }
        }
        return true;
    }

    /**
     * @return array
     */
    public function getPageLinks(): array
    {
        return $this->pageLinks;
    }

    /**
     * @return string
     */
    public function getHomeUrl(): string
    {
        return $this->homeUrl;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @param string $link
     * @return bool|string
     */
    private function preparePageLink(string $link)
    {
        $link = rtrim(trim($link), '/');
        if (strrpos($link, 'tel:') === 0 || strrpos($link, 'mailto:') === 0) {
            return false;
        }
        // trim get parameters
        $startGetParameters = strpos($link, '?');
        if ($startGetParameters !== false) {
            $link = substr($link, 0, $startGetParameters);
        }
        // trim anchor parameters
        $startAnchorParameters = strpos($link, '#');
        if ($startAnchorParameters !== false) {
            $link = substr($link, 0, $startAnchorParameters);
        }

        if ($link == '') {
            $link = $this->homeUrl;
        } elseif (strpos($link, 'http') === 0 || strpos($link, '//') === 0) {
            if (strpos($link, $this->homeUrl) === false) {
                return false;
            }
        } elseif (strpos($link, '/') === 0) {
            $link = $this->homeUrl . '/' . ltrim($link, '/');
        } else {
            return false;
        }

        return $link;
    }

    /**
     * @param string $url
     * @return array
     */
    private function getCurlResponse(string $url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_REFERER, $this->homeUrl);

        $html = curl_exec($ch);
        $info = curl_getinfo($ch);
        $this->error = curl_error($ch);
        curl_close($ch);

        return ['html' => $html, 'info' => $info];
    }
}