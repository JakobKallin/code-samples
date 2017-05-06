#!/usr/bin/env ruby
require 'csv'
require 'net/http'
require 'nokogiri'
require 'uri'

def link_from_element(element, base_url)
    url = URI::join(base_url, element['href'])
    return {
        url: url,
        title: element.text.strip,
        type: file_type_from_url(url),
    }
end

def file_type_from_url(url)
    if url.scheme == 'mailto'
        return 'email'
    else
        extension = File.extname(url.path.to_s)[1..-1]
        has_extension = extension != nil && extension.length > 0
        if has_extension
            file_type = file_type_from_extension(extension)
            return file_type || 'unknown'
        else
            return 'page'
        end
    end
end

def file_type_from_extension(extension)
    type_from_extension = {
        'gif' => 'image',
        'html' => 'page',
        'jpeg' => 'image',
        'jpg' => 'image',
        'mp3' => 'audio',
        'pdf' => 'PDF',
        'png' => 'image',
        'xml' => 'XML file',
    }
    return type_from_extension[extension]
end

def download(url_string)
    url = URI.parse(url_string)
    request = Net::HTTP::Get.new(url.to_s)
    response = Net::HTTP.start(url.host, url.port) {|http|
        http.request(request)
    }
    return response.body
end

url = ARGV[0]
if url == nil
    puts 'Usage: ruby main.rb <url>'
    exit 1
end

html = download(url)
document = Nokogiri::HTML(html)
link_elements = document.css('a')
links = link_elements.map{|e| link_from_element(e, url)}
sorted_links = links.sort_by{|l| l[:title].downcase}

puts CSV.generate {|csv|
    sorted_links.each do |link|
        csv << [link[:title], link[:url], link[:type]]
    end
}
