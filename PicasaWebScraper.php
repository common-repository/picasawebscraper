<?php
	/*
	Plugin Name: PicasaWebScraper
	Plugin URI: http://www.kennycarlile.com/PicasaWebScraper/
	Description: This Wordpress 2.x plugin scrapes an RSS feed from Google's PicasaWeb and displays the images within a page or post. 
	Version: 1.1
	Author: Kenny Carlile
	Author URI: http://www.kennycarlile.com
	
	--------------------------------------------------------------------------------
	
	PicasaWebScraper
	Copyright (C) 2007  Kenny Carlile

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
	
	--------------------------------------------------------------------------------
	
	References:
		- http://codex.wordpress.org/Function_Reference/fetch_rss
		- http://bluesome.net/post/2005/08/18/50/
	
	--------------------------------------------------------------------------------
		
	Install:
		1. Download, install, activate EXEC-PHP (http://bluesome.net/post/2005/08/18/50/)
		2. Upload PicasaWebScraper.php to ~/wp_contents/plugins/
		3. Activate PicasaWebScraper plugin
	
	--------------------------------------------------------------------------------
	
	Usage:
		1. Temporarily disable the visual editor: Users -> Your Profile -> uncheck "Use the visual editor when writing", click Update Profile
		2. On the post or page that you'd like to add the Picasa Web Gallery to, click on the Code tab and add the following code:
			<?php getPicasaWebAlbum('http://path.to.your.picasa.web.feed'); ?>
		3. Save/Publish the post or page.
		4. Enable the visual editor: Users -> Your Profile -> check "Use the visual editor when writing", click Update Profile
		
	Required Parameters:
		feedURL
			Required
			This is the URL of the base RSS feed that you'd like to include in your blog. To get this RSS feed URL, go to the gallery
				that you would like to include and click the RSS link. The URL in your browser's address bar is what you should pass to
				the function.
	
	Optional Parameters:
		showHeader
			Default: true
			If true, show's album title and link to PicasaWeb
		
		secrets
			Default: ''
			Comma separated list of the gallery names (i.e. http://picasaweb.google.com/username/THISPARTOFTHEURL)
				that you would like to hide from display.
			Example:
				If you have two galleries that you'd like to hide and their URLs are http://picasaweb.google.com/username/gallery1 and
				http://picasaweb.google.com/username/someothergallery, then you would pass 'gallery1,someothergallery' as the parameter.
			
		preHTML
			Default: '<div>'
			Allows styling of each item in the RSS feed; this needs to compliment the postHTML value.
			
		postHTML
			Default: '</div>'
			Allows styling of each item in the RSS feed; this needs to compliment the preHTML value.
			
		newWindow
			Default: false
			Allows specification of whether or not Picasa links open in a new window.
		
		--------------------------------------------------------------------------------
	*/

	function getPicasaWebAlbum($feedURL, $showHeader = true, $secrets = '', $preHTML = '<div>', $postHTML = '</div>', $newWindow = false)
	{
		include_once(ABSPATH . WPINC . '/rss.php');
		
		if(array_key_exists('gallery', $_GET))
		{
			$feedURL = rawurldecode($_GET['gallery']);
		} // end if test
		
		// make sure that links include language
		$langParam = ''; // &hl=de for German, etc.
		
		// not needed for English_US
		if(strstr($feedURL, '&hl=') && !strstr($feedURL, '&hl=en_US'))
		{
			preg_match('/hl=.[^&$]/', $feedURL, $matches);
			$langParam = $matches[0];
		} // end if test		
		
		$rss = fetch_rss($feedURL);
		$items = array_slice($rss->items, 0);
		
		if(empty($items))
		{
			echo 'This gallery is empty.';
		} // end if test
		else
		{
			// check for need for ?
			if(strstr($rss->channel['link'], '?'))
			{
				$langParam = '&' . $langParam;
			} // end if test
			else if($langParam != '')
			{
				$langParam = '?' . $langParam;
			} // end else test		
		
			if($showHeader)
			{
		?>
			<h3><?php echo $rss->channel['title']; ?></h3>
			<h4><a href="<?php echo $rss->channel['link'] . $langParam; ?>" target="_blank"><?php echo $rss->channel['link'] . $langParam; ?></a></h4>
		<?php
			} // end if test
			
			foreach($items as $item)
			{
				$hide = false;
				
				if($secrets != '')
				{
					// check for secrets
					if(strstr($secrets, ','))
					{
						$secretsArr = explode(',', $secrets);
					} // end if test
					else
					{
						$secretsArr = array();
						$secretsArr[] = $secrets;
					} // end else test
					
					foreach($secretsArr as $secret)
					{
						if(strstr($item['link'], $secret))
						{
							$hide = true;
						} // end if test
					} // end for each loop
				} // end if test
				
				if($hide == false)
				{
					$description = $item['description'];
					$pageURL = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
					
					if($_SERVER['QUERY_STRING'] == '')
					{
						$pageURL = $pageURL . '?';				
					} // end else test				
					
						//print_r(split('&', $feedURL));
					// check to see if this is a link to a photo or an album
					if(!strstr($description, 'photo#'))
					{					
						// replace links with link: thispage?gallery=link&kind=photo
						$guid = preg_replace('/\/entry\//is','/feed/', $item['guid'] . '&kind=photo');
						$description = preg_replace('/(<a[^>]+href="?)([^>" ]+)/is','\\1' . $pageURL . '&gallery=' . rawurlencode($guid), $description);
					} // end if test
					else
					{
						// include language parameter if needed
						if($langParam != '')
						{							
							$description = preg_replace('/href="([^"]+)photo([^"]+)"/is','href="\\1photo' . $langParam . '\\2"', $description);
						} // end if test
						
						// open new window if desired
						if($newWindow)
						{							
							$description = preg_replace('/(<a[^>]+href="?[^>]+)(>)/is', '\\1' . ' target="_blank"' . '\\2', $description);
						} // end if test
						
					} // end else test
					
					echo $preHTML . $description . $postHTML;
				} // end if test
			} // end foreach loop
		} // end else test
	} // end getPicasaWebAlbum function
	
	// equivalent to hello world for testing to see if the plugin is working
	function testPicasaWebScraper()
	{
		echo 'TESTING';	
	} // end testPicasaWebScraper function
?>