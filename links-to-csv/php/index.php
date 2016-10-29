#!/usr/bin/env php
<?php

function document_from_html(string $html):DOMDocument {
    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    return $doc;
}

function array_from_nodelist(DOMNodelist $nodelist):array {
    $array = [];
    foreach ($nodelist as $node) {
        $array[] = $node;
    }
    return $array;
}

// An incomplete implementation. For more robustness, we should use a suitable
// library function.
function create_url(array $array):string {
    return
        $array['scheme'] . '://' .
        $array['host'] . $array['path'] .
        (empty($array['query']) ? '' : '?' . $array['query']) .
        (empty($array['fragment']) ? '' : '#' . $array['fragment']);
}

function link_from_element(DOMElement $element, string $base_url):array {
    // If any part of the URL is missing, fill it in from the base URL. Note
    // that in case of conflict, the plus operator on arrays keeps the values
    // from the lefthand array.
    $unresolved_url = $element->getAttribute('href');
    $url_array = parse_url($unresolved_url);
    if (isset($url_array['scheme']) && $url_array['scheme'] === 'mailto') {
        $url = $unresolved_url;
    }
    else {
        $base_url_array = parse_url($base_url);
        $url = create_url($url_array + $base_url_array);
    }
    return [
        'url' => $url,
        'title' => trim($element->textContent),
        'type' => file_type_from_url($url),
    ];
}

function file_type_from_url(string $url_string):string {
    $url = parse_url($url_string);
    if ($url['scheme'] == 'mailto') {
        return 'email';
    }
    else {
        $pathinfo = pathinfo($url['path']);
        if (isset($pathinfo['extension'])) {
            $file_type = file_type_from_extension($pathinfo['extension']);
            return $file_type === null ? 'unknown' : $file_type;
        }
        else {
            return 'page';
        }
    }
}

function file_type_from_extension(string $extension):string {
    $type_from_extension = [
        'gif' => 'image',
        'html' => 'page',
        'jpeg' => 'image',
        'jpg' => 'image',
        'mp3' => 'audio',
        'pdf' => 'pdf',
        'png' => 'image',
        'xml' => 'XML file',
    ];

    foreach ($type_from_extension as $e => $t) {
        if ($e === $extension) return $t;
    }
    return null;
}

$args = array_slice($argv, 1);
if (empty($args)) {
    print "Usage: php index.php <url>\n";
    exit(1);
}

$url = $args[0];
$html = file_get_contents($url);
$doc = document_from_html($html);
$link_elements = array_from_nodelist($doc->getElementsByTagName('a'));
$links = array_map(function($element) use($url) {
    return link_from_element($element, $url);
}, $link_elements);
usort($links, function($a, $b) {
    return $a['title'] <=> $b['title'];
});

$stdout = fopen('php://stdout', 'w');
foreach ($links as $link) {
    fputcsv($stdout, [$link['title'], $link['url'], $link['type']]);
}
