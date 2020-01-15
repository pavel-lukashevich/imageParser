<?php

ini_set('error_reporting', E_ALL & ~E_NOTICE);
set_time_limit(0);

header('content-type:text/html; charset=UTF-8');


require_once 'vendor/autoload.php';

if (!empty($_POST) && !empty($_POST['url'])) {
    $url = $_POST['url'];
//$url = 'https://3ax-xyz.000webhostapp.com/';
//$url = 'https://qna.habr.com/';
//$url = 'https://seranking.com';
//$url = 'https://pavel-lukashevich.github.io';

    $pageLinks = [];
    $message = '';
    $savePath = $_SERVER['DOCUMENT_ROOT'] .'/files';

    $customParser = new \App\CustomParser($url);
    if (!empty($customParser->getError())) {
        viewPage('message.html', ['message' => $customParser->getError()]);
    }
    $fileSaver = new \App\FileSaver($savePath, $url);
//$fileSaver->saveImage(
//    'new page',
//    [
//        "https://mc.yandex.ru/watch/37182605",
//        "//googleads.g.doubleclick.net/pagead/viewthroughconversion/848607658/?guid=ON&script=0",
//        "https://nota.by/assets/templates/index_new/img/fair.png"
//    ],
//    'zzz'
//);
//die('end');
    switch ($_POST['sitemap']) {
        case 'check-sitemap':
            $customParser->createImagesSitemap();
            $pageLinks = $customParser->getPageLinks();
            break;
        case 'do-not-check-sitemap':
            $customParser->createPageLinks();
            $pageLinks = $customParser->getPageLinks();
            break;
        case 'use-saved-sitemap':
            $pageLinks = $fileSaver->readSavedSitemap();
            if (empty($pageLinks)) {
                $message = !empty($fileSaver->getError()) ? $fileSaver->getError() : 'Sitemap is empty.';
                viewPage('message.html', ['message' => $message]);
            }
            break;
        default:
            $customParser->createImagesSitemap();
            $pageLinks = $customParser->getPageLinks();
            break;
    }

    if (!empty($customParser->getError())) {
        viewPage('message.html', ['message' => $customParser->getError()]);
    }

    if (empty($_POST['do-not-save-sitemap'])) {
        if(!$fileSaver->saveImageMap($pageLinks)) {
            viewPage('message.html', ['message' => $fileSaver->getError()]);
        }
        $message .= 'Sitemap saved successfully.';
    }

    if (empty($_POST['do-not-save-image'])) {
        if (!$fileSaver->saveImages($pageLinks, $customParser->getHomeUrl())) {
            $message .= '<br>' . $fileSaver->getError() . '<br>';
            $message .= 'Other images saved successfully.' . PHP_EOL;
        } else {
            $message = empty($message) ? '' : $message . '<br>';
            $message .= 'Images saved successfully.';
        }
    }

    $message = empty($message) ? var_export($pageLinks, true) : $message;
    viewPage('message.html', ['message' => $message]);
}

viewPage('form.html', ['message' => '']);
