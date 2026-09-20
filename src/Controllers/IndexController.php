<?php

namespace App\Controllers;

use Symfony\Component\Yaml\Yaml;
use Slim\Views\Twig;

class IndexController 
{
	const NEWS_LIMIT = 6;

	private $view;

	public function __construct()
	{
		$this->view = new \Slim\Views\Twig(
			__DIR__ . '/../../templates',
			[
				'cache' => false
			]
		);
	}

	public function getAllNews($arrXml, $currentSite = 'all')
	{
		$result = [];

		foreach ($arrXml as $site => $sections) {
			foreach ($sections as $section => $feedUrl) {
				$result[] = [
					'site' => $site,
					'section' => $section,
					'headline' => $this->readXML($feedUrl)
				];
			}
		}

		return $this->view->fetch('midias/midia.html.twig', [
			'news' => $result,
			'hour' => $this->getHourNow(),
			'current_site' => $currentSite
		]);
	}

	public function getNewsBySitename($sitename, $arrXml)
	{
		if (!isset($arrXml[$sitename])) {
			return $this->getAllNews($arrXml, 'all');
		}

		return $this->getAllNews([
			$sitename => $arrXml[$sitename]
		], $sitename);
	}

	public function readXML($feedUrl)
	{
		libxml_use_internal_errors(true);

		$feedUrl = preg_replace('#^http://#', 'https://', $feedUrl);

		$curl = curl_init($feedUrl);

		curl_setopt_array($curl, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
			CURLOPT_USERAGENT => 'Mozilla/5.0',
			CURLOPT_HTTPHEADER => [
				'Accept: application/rss+xml, application/xml, text/xml',
				'Accept-Language: pt-BR,pt;q=0.9'
			],
			CURLOPT_ENCODING => ''
		]);

		$content = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		curl_close($curl);

		if ($content === false || $status >= 400) {
			return [];
		}

		$xml = @simplexml_load_string(
			$content,
			'SimpleXMLElement',
			LIBXML_NOCDATA
		);

		if ($xml === false) {
			return [];
		}

		$items = isset($xml->channel->item)
			? $xml->channel->item
			: $xml->entry;

		$list = [];

		foreach ($items as $item) {
			if (count($list) >= self::NEWS_LIMIT) {
				break;
			}

			$title = (string) $item->title;

			$description = isset($item->description)
				? (string) $item->description
				: (isset($item->content) ? (string) $item->content : (string) $item->summary);

			$link = isset($item->link['href'])
				? (string) $item->link['href']
				: (string) $item->link;

			$list[] = [
				'title' => $title,
				'description' => strip_tags($description),
				'link' => $link
			];
		}

		return $list;
	}

	public function getAllMidias($request)
	{
		$yaml = Yaml::parse(
			file_get_contents(__DIR__ . '/../../rss/rss.yml')
		);

		$feeds = array_shift($yaml);
		$sitename = $request->getQueryParam('site', 'all');

		if ($sitename === 'all') {
			return $this->getAllNews($feeds, 'all');
		}

		return $this->getNewsBySitename($sitename, $feeds);
	}

	public function getHourNow()
	{
		date_default_timezone_set("America/Sao_Paulo");
		return date('H:i');
	}
}