<?php
function xz($data, $useHtmlSpecialChars = false)
{
    if (!empty($useHtmlSpecialChars)) {
        $data = htmlspecialchars($data);
    }
    echo '<pre>';
    var_dump($data);
    echo '<pre>';
    die;
}

function viewPage(string $contentPath, ?array $parameters = [])
{
    $parameters['content'] = '{{ content }}';
    $layout = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/view/layout.html');
    $content = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/view/' . $contentPath);
    preg_match_all("#\{\{([^\}\}]*)\}\}#is", $layout . $content, $match);

    $replases = [];
    foreach ($match[0] as $key => $value) {
        $parametersKey = trim($match[1][$key]);
        $replases[$value] = $parameters[$parametersKey] ?? '>>>_invalid_value_<<<';
    }
    $content = strtr($content, $replases);
    $view = strtr($layout, [$parameters['content'] => $content ]);
    echo $view;
    exit;
}
