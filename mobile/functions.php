<?php
	define('TTRSS_SESSION_NAME', 'ttrss_m_sid');

	/* TODO replace with interface to db-prefs */

	function mobile_pref_toggled($link, $id) {
		if ($_SESSION["mobile-prefs"][$id]) return "true";

	}

	function mobile_get_pref($link, $id) {
		return $_SESSION["mobile-prefs"][$id];
	}

	function mobile_set_pref($link, $id, $value) {
		$_SESSION["mobile-prefs"][$id] = $value;
	}

	function mobile_feed_has_icon($id) {
		$filename = "../".ICONS_DIR."/$id.ico";

		return file_exists($filename) && filesize($filename) > 0;
	}

	function render_flat_feed_list($link) {
		$owner_uid = $_SESSION["uid"];

		if (mobile_get_pref($link, "SORT_FEEDS_UNREAD")) {
			$order_by = "unread DESC, title";
		} else {
			$order_by = "title";
		}

		$result = db_query($link, "SELECT id,
				title,
			(SELECT COUNT(id) FROM ttrss_entries,ttrss_user_entries
				WHERE feed_id = ttrss_feeds.id AND unread = true
					AND ttrss_user_entries.ref_id = ttrss_entries.id
					AND owner_uid = '$owner_uid') as unread
			FROM ttrss_feeds
			WHERE 
				ttrss_feeds.hidden = false AND
				ttrss_feeds.owner_uid = '$owner_uid' AND 
				parent_feed IS NULL
			ORDER BY $order_by"); 
	
		print '<ul id="home" title="Feeds" selected="true"
			myBackLabel="Logout" myBackHref="logout.php" myBackTarget="_self">';

	//		print "<li><a href='#cat-actions'>".__('Actions...')."</a></li>";
	
			while ($line = db_fetch_assoc($result)) {
				$id = $line["id"];
				$unread = $line["unread"];

	//			$unread = rand(0, 100);
	
				if ($unread > 0) {
					$line["title"] = $line["title"] . " ($unread)";
					$class = '';
				} else {
					$class = 'oldItem';
				}
	
				if (mobile_feed_has_icon($id)) {
					$icon_url = "../".ICONS_URL."/$id.ico";
				} else {
					$icon_url = "../images/blank_icon.gif";
				}

				if ($unread > 0 || !mobile_get_pref($link, "HIDE_READ")) {
					print "<li class='$class'><a href='feed.php?id=$id'>" . 
						"<img class='tinyIcon' src='$icon_url'/>".				
						$line["title"] . "</a></li>";
				}
			}
	
			print "</ul>";



	}

	function render_category($link, $cat_id) {
		$owner_uid = $_SESSION["uid"];

		if ($cat_id >= 0) {

			if ($cat_id != 0) {
				$cat_query = "cat_id = '$cat_id'";
			} else {
				$cat_query = "cat_id IS NULL";
			}

			if (mobile_get_pref($link, "SORT_FEEDS_UNREAD")) {
				$order_by = "unread DESC, title";
			} else {
				$order_by = "title";
			}

			$result = db_query($link, "SELECT id,
				title,
			(SELECT COUNT(id) FROM ttrss_entries,ttrss_user_entries
				WHERE feed_id = ttrss_feeds.id AND unread = true
					AND ttrss_user_entries.ref_id = ttrss_entries.id
					AND owner_uid = '$owner_uid') as unread
			FROM ttrss_feeds
			WHERE 
				ttrss_feeds.hidden = false AND
				ttrss_feeds.owner_uid = '$owner_uid' AND 
				parent_feed IS NULL AND
				$cat_query
			ORDER BY $order_by"); 
			
			$title = getCategoryTitle($link, $cat_id);
	
			print "<ul id='cat-$cat_id' title='$title' myBackLabel='Feeds'
				myBackHref='home.php'>";
	
	//		print "<li><a href='#cat-actions'>".__('Actions...')."</a></li>";
	
			while ($line = db_fetch_assoc($result)) {
				$id = $line["id"];
				$unread = $line["unread"];

	//			$unread = rand(0, 100);
	
				if ($unread > 0) {
					$line["title"] = $line["title"] . " ($unread)";
					$class = '';
				} else {
					$class = 'oldItem';
				}
	
				if (mobile_feed_has_icon($id)) {
					$icon_url = "../".ICONS_URL."/$id.ico";
				} else {
					$icon_url = "../images/blank_icon.gif";
				}

				if ($unread > 0 || !mobile_get_pref($link, "HIDE_READ")) {
					print "<li class='$class'><a href='feed.php?id=$id&cat=$cat_id'>" . 
						"<img class='tinyIcon' src='$icon_url'/>".				
						$line["title"] . "</a></li>";
				}
			}
	
			print "</ul>";
		} else if ($cat_id == -1) {

			$title = __('Special');

			print "<ul id='cat--1' title='$title' myBackLabel='Feeds'
				myBackHref='index.php' myBackTarget='_self'>";

			foreach (array(-4, -1,-2,-3) as $id) {
				$title = getFeedTitle($link, $id);
				$unread = getFeedUnread($link, $id, false);

				if ($unread > 0) {
					$title = $title . " ($unread)";
					$class = '';
				} else {
					$class = 'oldItem';
				}

				if ($unread > 0 || !mobile_get_pref($link, "HIDE_READ")) {
					print "<li class='$class'>
						<a href='feed.php?id=$id&cat_id=-1'>$title</a></li>";
				}
			}

			print "</ul>";
		} else if ($cat_id == -2) {

			$title = __('Labels');

			print "<ul id='cat--2' title='$title' myBackLabel='Feeds'
				myBackHref='index.php' myBackTarget='_self'>";

			$result = db_query($link, "SELECT id, caption FROM ttrss_labels2
				WHERE owner_uid = '$owner_uid'");

			$label_data = array();

			while ($line = db_fetch_assoc($result)) {

				$id = -$line["id"] - 11;

				$unread = getFeedUnread($link, $id);
				$title = $line["caption"];

				if ($unread > 0) {
					$title = $title . " ($unread)";
					$class = '';
				} else {
					$class = 'oldItem';
				}

				if ($unread > 0 || !mobile_get_pref($link, "HIDE_READ")) {
					print "<li class='$class'>
						<a href='feed.php?id=$id&cat=-2'>$title</a></li>";
				}
			}
			print "</ul>";
		}
	}

	function render_categories_list($link) {
		$owner_uid = $_SESSION["uid"];


		print '<ul id="home" title="'.__('Categories').'" selected="true"
			myBackLabel="Logout" myBackHref="logout.php" myBackTarget="_self">';

		foreach (array(-1, -2) as $id) {
			$title = getCategoryTitle($link, $id);
			$unread = getFeedUnread($link, $id, true);
			if ($unread > 0) { 
				$title = $title . " ($unread)";
				$class = '';
			} else {
				$class = 'oldItem';
			}

			print "<li class='$class'><a href='cat.php?id=$id'>$title</a></li>";
		}

		$result = db_query($link, "SELECT 
				ttrss_feed_categories.id, 
				ttrss_feed_categories.title, 
				COUNT(ttrss_feeds.id) AS num_feeds 
			FROM ttrss_feed_categories, ttrss_feeds
			WHERE ttrss_feed_categories.owner_uid = $owner_uid 
				AND ttrss_feed_categories.id = cat_id	
				AND hidden = false
				GROUP BY ttrss_feed_categories.id, 
					ttrss_feed_categories.title
				ORDER BY ttrss_feed_categories.title");

		while ($line = db_fetch_assoc($result)) {

			if ($line["num_feeds"] > 0) {

				$unread = getFeedUnread($link, $line["id"], true);
				$id = $line["id"];

				if ($unread > 0) {
					$line["title"] = $line["title"] . " ($unread)";
					$class = '';
				} else {
					$class = 'oldItem';
				}

				if ($unread > 0 || !mobile_get_pref($link, "HIDE_READ")) {
					print "<li class='$class'><a href='cat.php?id=$id'>" . 
						$line["title"] . "</a></li>";
				}
			}
		}


		$result = db_query($link, "SELECT COUNT(*) AS nf FROM ttrss_feeds WHERE
			cat_id IS NULL and owner_uid = '$owner_uid'");

		$num_feeds = db_fetch_result($result, 0, "nf");

		if ($num_feeds > 0) {
			$unread = getFeedUnread($link, 0, true);
			$title = "Uncategorized";

			if ($unread > 0) {
				$title = "$title ($unread)";
				$class = '';
			} else {
				$class = 'oldItem';
			}

			if ($unread > 0 || !mobile_get_pref($link, "HIDE_READ")) {
				print "<li class='$class'><a href='cat.php?id=0'>$title</a></li>";
			}
		}

		print "</ul>";
	}

	function render_headlines_list($link, $feed_id, $cat_id) {

		$feed_id = $feed_id;
		$limit = 30;
		$filter = '';
		$is_cat = false;
		$view_mode = 'adaptive';

		/* do not rely on params below */

		$search = '';
		$search_mode = '';
		$match_on = '';
			
		$qfh_ret = queryFeedHeadlines($link, $feed_id, $limit, 
			$view_mode, $is_cat, $search, $search_mode, $match_on);

		$result = $qfh_ret[0];
		$feed_title = $qfh_ret[1];

		if ($cat_id) {
			$cat_title = getCategoryTitle($link, $cat_id);

			print "<ul id=\"feed-$feed_id\" title=\"$feed_title\" selected=\"true\"
				myBackLabel='$cat_title' myBackHref='cat.php?id=$cat_id'>";
		} else {
			print "<ul id=\"feed-$feed_id\" title=\"$feed_title\" selected=\"true\"
				myBackLabel='Feeds' myBackHref='home.php'>";
		}

		while ($line = db_fetch_assoc($result)) {
			$id = $line["id"];
			$real_feed_id = $line["feed_id"];

			if (sql_bool_to_bool($line["unread"])) {
				$class = '';
			} else {
				$class = 'oldItem';
			}

			if (mobile_feed_has_icon($real_feed_id)) {
				$icon_url = "../".ICONS_URL."/$real_feed_id.ico";
			} else {
				$icon_url = "../images/blank_icon.gif";
			}

			print "<li class='$class'><a href='article.php?id=$id&feed=$feed_id&cat=$cat_id'>
				<img class='tinyIcon' src='$icon_url'>";
			print $line["title"];
			print "</a></li>";

		}

		print "</ul>";

	}

	function render_article($link, $id, $feed_id, $cat_id) {

		$query = "SELECT title,link,content,feed_id,comments,int_id,
			marked,unread,published,
			".SUBSTRING_FOR_DATE."(updated,1,16) as updated,
			author
			FROM ttrss_entries,ttrss_user_entries
			WHERE	id = '$id' AND ref_id = id AND owner_uid = " . 
				$_SESSION["uid"] ;

		$result = db_query($link, $query);

		if (db_num_rows($result) != 0) {

			$line = db_fetch_assoc($result);

			$tmp_result = db_query($link, "UPDATE ttrss_user_entries 
				SET unread = false,last_read = NOW() 
				WHERE ref_id = '$id'
				AND owner_uid = " . $_SESSION["uid"]);

			if (get_pref($link, 'HEADLINES_SMART_DATE')) {
				$updated_fmt = smart_date_time(strtotime($line["updated"]));
			} else {
				$short_date = get_pref($link, 'SHORT_DATE_FORMAT');
				$updated_fmt = date($short_date, strtotime($line["updated"]));
			}				
	
			$title = $line["title"];
			$article_link = $line["link"];
	
			$feed_title = getFeedTitle($link, $feed_id, false);
	
			print "<div class=\"panel\" id=\"article-$id\" title=\"$title\" 
				selected=\"true\"
				myBackLabel='$feed_title' myBackHref='feed.php?id=$feed_id&cat=$cat_id'>";
	
			print "<h2><a target='_blank' href='$link'>$title</a></h2>";
	
			print "<fieldset>";
	
/*			print "<div class=\"row\">";
			print "<label id='title'><a target='_blank' href='$article_link'>$title</a></label>";
			print "</div>"; */
	
			$is_starred = (sql_bool_to_bool($line["marked"])) ? "true" : "false";
			$is_published = (sql_bool_to_bool($line["published"])) ? "true" : "false";
	
			print "<div class=\"row\">";
			print "<label id='updated'>Updated:</label>";
			print "<input enabled='false' name='updated' disabled value='$updated_fmt'/>";
			print "</div>";
	
			print "</fieldset>";

			$content = sanitize_rss($link, $line["content"]);
			$content = preg_replace("/href=/i", "target=\"_blank\" href=", $content);

			if (!mobile_get_pref($link, "SHOW_IMAGES")) {
				$content = preg_replace('/<img[^>]+>/is', '', $content);
			}

			print "<p>$content</p>";

			print "<fieldset>";

			print "<div class=\"row\">
	                <label>Starred</label>
	                <div class=\"toggle\" onclick=\"toggleMarked($id, this)\" toggled=\"$is_starred\"><span class=\"thumb\"></span><span class=\"toggleOn\">ON</span><span class=\"toggleOff\">OFF</span></div>
	            </div>";
	
			print "<div class=\"row\">
	                <label>Published</label>
	                <div class=\"toggle\" onclick=\"togglePublished($id, this)\" toggled=\"$is_published\"><span class=\"thumb\"></span><span class=\"toggleOn\">ON</span><span class=\"toggleOff\">OFF</span></div>
	            </div>";

			print "</fieldset>";

			print "</div>";

		}
	}
?>
