#!/usr/bin/env python3
import csv
import os.path
import sys
import urllib.request
import urllib.parse
from bs4 import BeautifulSoup

def link_from_element(element, base_url):
    url = urllib.parse.urljoin(base_url, element['href'])
    return {
        'url': url,
        'title': element.get_text().strip(),
        'type': file_type_from_url(url),
    }

def file_type_from_url(url_string):
    url = urllib.parse.urlparse(url_string)
    if url.scheme == 'mailto':
        return 'email'
    else:
        extension = os.path.splitext(url.path)[1][1:]
        has_extension = len(extension) > 0
        if has_extension:
            file_type = file_type_from_extension(extension)
            return 'unknown' if file_type is None else file_type
        else:
            return 'page'

def file_type_from_extension(extension):
    type_from_extension = {
        'gif': 'image',
        'html': 'page',
        'jpeg': 'image',
        'jpg': 'image',
        'mp3': 'audio',
        'pdf': 'PDF',
        'png': 'image',
        'xml': 'XML file',
    }
    matches = [t for e, t in type_from_extension.items() if e == extension]
    return None if len(matches) == 0 else matches[0]

args = sys.argv[1:]
if len(args) != 1:
    print('Usage: python3 __main__.py <url>')
    sys.exit(1)

url = args[0]
with urllib.request.urlopen(url) as response:
    html = response.read()

doc = BeautifulSoup(html, 'html.parser')
link_elements = doc.find_all('a')
links = [link_from_element(e, url) for e in link_elements]
sorted_links = sorted(links, key=lambda link: link['title'])

csv_file = csv.DictWriter(sys.stdout, fieldnames=['title', 'url', 'type'])
for link in sorted_links:
    csv_file.writerow(link)
