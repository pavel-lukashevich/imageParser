<?php

namespace App;

class FileSaver
{
    public $error = '';
    public $savePath = '';
    public $dirName = 'new';

    /**
     * FileSaver constructor.
     * @param string $savePath
     * @param string $url
     */
    public function __construct(string $savePath, string $url)
    {
        $this->savePath = $savePath;
        $domain = substr($url, strpos($url, '//') + 2);
        $this->dirName = str_replace('/', '__', rtrim($domain, '/'));
    }

    /**
     * @param array $pageLinks
     * @return bool
     */
    public function saveImageMap(array $pageLinks)
    {
        $dirName = $this->savePath . '/' . $this->dirName;
        if (!file_exists($dirName) && !mkdir($dirName)) {
            $this->error = 'invalid dirname: ' . $dirName;
            return false;
        }
        if (!file_put_contents($dirName .'/map.txt', json_encode($pageLinks))) {
            $this->error = 'map.txt don\'t save';
            return false;
        }
        return true;
    }

    public function readSavedSitemap()
    {
        $dirPath = $this->savePath . '/' . $this->dirName;
        if (file_exists($dirPath) && file_exists($dirPath . '/map.txt')) {
            $map = file_get_contents($dirPath . '/map.txt');
            return (array) json_decode($map);
        } else {
            $this->error = 'Sitemap not found.';
        }
        return false;
    }

    /**
     * @param array $pageLinks
     * @param string $homeUrl
     * @return bool|string
     */
    public function saveImages(array $pageLinks, string $homeUrl)
    {
        foreach ($pageLinks as $pageLink => $imagesLinks) {
            if (empty($imagesLinks)) {
                continue;
            }
            $this->saveImage($pageLink, $imagesLinks, $homeUrl);
        }
        return empty($this->error) ? true : false;
    }

    /**
     * @param string $pageLink
     * @param array $imagesLinks
     * @param string $homeUrl
     * @return bool
     */
    public function saveImage(string $pageLink, array $imagesLinks, string $homeUrl)
    {
        if (empty($imagesLinks)) {
            return false;
        }
        $pagePath = $this->savePath . '/' . $this->dirName . str_replace($homeUrl, '', $pageLink);
        $pagePath = rtrim($pagePath, '/');
        if (!file_exists($pagePath)) {
            mkdir($pagePath, 0777, true);
        }

        foreach ($imagesLinks as $imagesLink) {
            $extension = substr($imagesLink, strrpos($imagesLink, '.'));
            $imgName = preg_replace('#\W#', '-', rtrim($imagesLink, $extension));
            $image = @file_get_contents($imagesLink);
            if ($image !== false) {
                $fileName = $pagePath . '/' . $imgName . $extension;
                $fileSave = @file_put_contents($fileName, $image);
            }
            if (empty($fileSave)) {
                $this->error .= 'File don\'t save: ' . $imagesLink . $extension . PHP_EOL ;
            }
        }
        return true;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }
}