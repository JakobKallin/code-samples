<?php

function http_get($url) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

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
    elseif (isset($url['path'])) {
        $pathinfo = pathinfo($url['path']);
        if (isset($pathinfo['extension'])) {
            $file_type = file_type_from_extension($pathinfo['extension']);
            return $file_type === null ? 'unknown' : $file_type;
        }
        else return 'page';
    }
    else return 'page';
}

function file_type_from_extension(string $extension) {
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

if (!isset($_GET['url'])) {
    http_response_code(400);
    print 'You must provide a URL with the "URL" query parameter.';
    exit(1);
}

$url = $_GET['url'];
$sort_by = isset($_GET['sort-by']) && in_array($_GET['sort-by'], ['url', 'title', 'type'])
    ? $_GET['sort-by']
    : 'title';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

$html = http_get($url);
$doc = document_from_html($html);
$link_elements = array_from_nodelist($doc->getElementsByTagName('a'));
$links = array_map(function($element) use($url) {
    return link_from_element($element, $url);
}, $link_elements);

$links = array_filter($links, function($link) use($filter) {
    return
        $filter === ''
        || stripos($link['url'], $filter) !== FALSE
        || stripos($link['title'], $filter) !== FALSE
        || stripos($link['type'], $filter) !== FALSE;
});

usort($links, function($a, $b) use($sort_by) {
    return $a[$sort_by] <=> $b[$sort_by];
});

print json_encode($links, JSON_PRETTY_PRINT);
