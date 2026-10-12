<?php

require( __DIR__ . '/db.php' );
require( __DIR__ . '/edit.php' );

$queries = [
	'3d' => [ 'query' => "select page_id from image inner join page on page_namespace =6 and page_title = img_name where img_media_type = '3D' order by page_id asc" ],
	'assessed' => [
		'query' => "select page_id from templatelinks inner join page on  tl_from = page_id where  tl_target_id in ( select lt_id from linktarget where lt_namespace= 10 and lt_title in ( 'Assessments', 'Quality_image', 'Valued_image', 'Featured picture', 'DelistedPicture', 'Photo_challenge_winner' ) ) and tl_from_namespace = 6 and page_title rlike '(?i)\\\.(webp|xcf|tiff?|a?png|jpg|jpeg?|svg)$' order by md5(page_id) asc",
		'chunks' => 3,
		'db' => 'links'
	],
	// Might be interesting to work in files used on wiki, but have to exclude pronounciation, spoken wikipedia, etc.
	'audio' => [ 'query' => "(select page_id from templatelinks inner join page on  tl_from = page_id inner join image on img_name = page_title where  tl_target_id in ( select lt_id from linktarget where lt_namespace = 10 and lt_title in ( 'Assessments', 'Quality_image', 'Valued_image', 'Featured_picture', 'DelistedPicture', 'Photo_challenge_winner', 'Media_of_the_day', 'FM_promoted' ) ) and tl_from_namespace = 6 and (img_media_type = 'AUDIO') ) UNION ( select page_id from image inner join page on img_name = page_title and page_namespace = 6 inner join globalimagelinks g1 on g1.gil_to = img_name left join globalimagelinks g2 on g2.gil_to = img_name and g2.gil_page = 126609798 and g2.gil_wiki = 'wikidatawiki' where img_media_type = 'AUDIO' and g1.gil_page_namespace_id  = 0 and img_metadata not like '%\"length\":_.%' and img_metadata not like '%\"length\":_,%' and img_metadata not like '%\"length\";d:_.%' and img_metadata not like '%\"length\";i:_;%' and img_metadata not like '%\"playtime_seconds\":_.%' and img_metadata not like '%\"playtime_seconds\";d:_.%' and g1.gil_wiki not like 'wikimania%' and g1.gil_wiki not like '%wikimedia' and g1.gil_wiki != 'metawiki' and g1.gil_wiki != 'outreachwiki' and g1.gil_wiki != 'mediawikiwiki' and g2.gil_to is null group by img_name order by count(g1.gil_page) desc limit 6000 ) order by page_id asc" ],
	'document' => [
		'query' => "select page_id from page inner join globalimagelinks on page_title = gil_to and gil_page_namespace_id = 0 and gil_wiki not like 'wikimania%' and gil_wiki not like '%wikimedia' and gil_wiki != 'metawiki' and gil_wiki != 'outreachwiki' and gil_wiki != 'mediawikiwiki' and gil_wiki != 'wikidatawiki'  where page_namespace = 6 and page_title rlike '(?i)\\\.(djvu?|pdf)$'  group by 1 order by page_id asc",
		'db' => 'links'
	],
	// Featured by colour handled separately
	'featured' => [
		'query' => "select page_id from categorylinks inner join page on cl_from = page_id inner join linktarget on lt_id = cl_target_id  where lt_namespace=14 and lt_title = 'Featured_pictures_on_Wikimedia_Commons' and cl_type = 'file' and page_namespace = 6 and page_title rlike '(?i)\\\.(webp|xcf|tiff?|a?png|jpg|jpeg?|svg)$' order by page_id asc",
		'db' => 'links'
	],
	'video' => [ 'query' => "(select page_id from templatelinks inner join page on  tl_from = page_id inner join image on img_name = page_title where  tl_target_id in ( select lt_id from linktarget where lt_namespace= 10 and lt_title in ( 'Assessments', 'Quality_image', 'Valued_image', 'Featured_picture', 'DelistedPicture', 'Photo_challenge_winner', 'Media_of_the_day', 'FM_promoted' ) ) and tl_from_namespace = 6 and ((img_media_type = 'VIDEO') or (img_media_type = 'BITMAP' and img_major_mime = 'image' and img_minor_mime = 'gif'))) UNION (select page_id from image inner join page on page_namespace=6 and page_title=img_name  inner join globalimagelinks on img_name = gil_to and gil_page_namespace_id = 0 and gil_wiki not like 'wikimania%' and gil_wiki not like '%wikimedia' and gil_wiki != 'metawiki' and gil_wiki != 'outreachwiki' and gil_wiki != 'mediawikiwiki'  where img_media_type = 'VIDEO' ) order by page_id asc;" ]
];

function doStuff() {
	global $queries;
	$db = getCommonsDB();
	$linksDB = getLinksDB();

	foreach( $queries as $name => $info ) {
		if ( ( $info['db'] ?? 'normal' ) === 'links' ) {
			$res = $linksDB->query( $info['query'] );
		} else {
			$res = $db->query( $info['query'] );
		}
		if ( !$res || $res->num_rows < 2 ) {
			die( "Query error: " . $db->error );
		}
		$list = [];
		while( $id = $res->fetch_column() ) {
			$list[] = (int)$id;
		}

		if ( isset( $info['chunks'] ) ) {
			$num = ceil( count( $list ) / $info['chunks'] );
			$chunks = array_chunk( $list, $num );
			foreach( $chunks as $i => $chunk ) {
				$json = json_encode( $chunk, JSON_UNESCAPED_UNICODE );
				savePage( "Module:Explore/$name/" . ($i+1) . ".json", $json );
			}
		} else {
			$json = json_encode( $list, JSON_UNESCAPED_UNICODE );
			savePage( "Module:Explore/$name.json", $json );
		}
	}
}
doStuff();
