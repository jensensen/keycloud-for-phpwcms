<?php
/******************************************************************
* KeyCloud v1.7 for phpwcms --> v.1.3.3 + and later
*
* Date: Mar. 30, 2009 by Jensensen // Copyright 2008–2017 Jensensen
* --> RTFM: http://forum.phpwcms.org/viewtopic.php?f=8&t=17884
*
* Script returns <div class>KeyWordCloud here</div> only on pages where
* the RT {KEYCLOUD:...} was found somewhere in the page code.
*
* CREDITS: Special thanks to all guys who have helped to realise TC
* and jsw_nz aka John for inspiration.
*
* USAGE:
*   -> Edit some variables --> see below
*   -> Add RT somewhere in your page code e.g. CP HTML or page template
*      e.g. {KEYCLOUD:I:0,2,17:S:9} or {KEYCLOUD:E:0,1,2,17,152:L:38}
*
*      Rendermode: analyze keywords of ALL articles which are
*      I ==> IN the categories as follow (include category)
*      E ==> NOT IN the categories as follow (exclude these category)
*
*      0,2,17 ==> IDs of site structure levels / categories to 'cloud'
*
*      Decide where cloud tags are linked to:
*      L ==> LANDING PAGE (separate page outside categories above)
*      S ==> phpwcms SEARCH PAGE
*      A ==> phpwcms STANDARD: index.php?ALIAS // {KEYCLOUD:I:0,4,56:A}
*
*      9 ==> Artikel ID of your LANDING PAGE or SEARCH PAGE!!!
*
*   -> Place this script into
*      /template/inc_script/frontend_render/
*	-> copy CSS
*      /template/inc_css/specific/keycloud.css
*
* LANDING PAGE: only necessary when you use e.g. {    L:7}
*   -> ADD a separate article/page (which must be manually prepared
*      with anchor tags) where tags are linked to and on which
*      further information to each cloud tag can be displayed.
*
*   Add CP HTML or any other and place an anchor on top of each CP
*   for each tag of the KeyCloud:
*   <a name="KEYWORD" id="KEYWORD"></a><p>Read more about KEYWORD...</p>
*
* TO DO
* any idea?
* ****************************************************************/
// obligate check for phpwcms constants
if (!defined('PHPWCMS_ROOT')) {
   die("You Cannot Access This Script Directly, Have a Nice Day.");
}
// ----------------------------------------------------------------

$content['kwcloud'] = array(

/******************************************************************
* !!!!!!!!!!!!! ### SET UP SOME VARIABLES HERE ### !!!!!!!!!!!! ###
******************************************************************/

	'kcl_min'			=> 1,		// Minimum occurrences for words to be displayed within cloud
	'kcl_min_chars'		=> 3,		// but only if word lenght is minimum --> characters: x
	'kcl_sort'			=> 'asc',	// can be: asc, desc, or random
	'kcl_showCount'		=> 1,		// display number next to tag --> can be 0=No or 1=Yes

	//charcters to delete out of the cloud
	'kcl_del_signs'		=> array(",","!","'s"),

	// FILTER IT BY USING EITHER MODE EXCLUDE OR INCLUDE
	'kcl_filter'		=> 0,		// --> can be 0=exclude or 1=include

	// keywords to EXCLUDE from the cloud. Example: array("this","that")
	'kcl_exclude'		=> array(),

	// keywords to INCLUDE to the cloud.
	// When you want to minimize all keywords to just the following ones.
	'kcl_include'		=> array("Lorem","jimbob","product"),

	// WHEN YOU USE REWRITE THEN SET THE ALIAS OF YOUR LANDING- or SEARCH PAGE!
	// !!! NOT USED ANYMORE {temp} !!!
	// 'kcl_SP_Alias'	=> 'page_alias',
	// 'kcl_SP_Alias'		=> '',


	// Style and CSS settings
	// class of div wrapped around the cloud
	'kcl_class'			=> 'keycloud',
	// ShowCount wrapper
	'kcl_SC_before'		=> '<span>(',
	'kcl_SC_after'		=> ')</span>'

/***************************************** !!! END SET UP !!! ***/

);

/*********************** !!! WARNING !!! *************************
* ### ************************** ### ************************* ###
* ### !!!!!!!!!!! ### NO NEED TO EDIT BELOW ### !!!!!!!!!!!!!! ###
* ### *************** +++++++++++++++++++++ ****************** ###
*****************************************************************/
function make_kwcloud($kwmatches) {
	
	global $phpwcms, $content;
	
	$kwremo		= trim($kwmatches[1]);
	$kwhich_ID	= trim($kwmatches[2]);
	$kw_setLP	= trim($kwmatches[3]);
	$keyland	= isset($kwmatches[4]) ? intval($kwmatches[4]) : 0;

	$kwconf = & $content['kwcloud'];

	// check integrity of user_settings --- else use defaults +++ OG new style
	if(empty($kwconf['kcl_min'])) {
		$kwconf['kcl_min'] = 1;
	}
	if(empty($kwconf['kcl_min_chars'])) {
		$kwconf['kcl_min_chars'] = 3;
	}
	if(!isset($kwconf['kcl_sort'])) {
		$kwconf['kcl_sort'] = false;
	}
	if(empty($kwconf['kcl_filter'])) {
		$kwconf['kcl_filter'] = 0;
	}


	// NOW, FINALLY IT'S TIME TO LET A FRESH BREEZE BLOWING UP A PRETTY KEYWORD CLOUD
	$kcl_target = 'index.php#'; // set default exit, a pretty link

	if(!empty($kw_setLP)) {

		if($kw_setLP == 'A') {
			$kcl_target = 'index.php?';
			$keyland = true;
		}

		if(!empty($keyland)) {
		  switch ($kw_setLP) {
			 case 'L':
			 	$keyaliasfound = kcl_fetchalias($keyland);
				if(!empty($keyaliasfound)) {
					$kcl_target = 'index.php?'. $keyaliasfound .'#';
				} else {
					$kcl_target = 'index.php?aid='. $keyland .'#'; //fallback for older versions
//old_style		$kcl_target = 'index.php?id=0,'. $keyland . ',0,0,1,0#'; //much older versions
				}
			 break;
			 
			 case 'S':
			 	$keyaliasfound = kcl_fetchalias($keyland);

				if(!empty($keyaliasfound)) {
					$kcl_target = 'index.php?' . $keyaliasfound . '&amp;searchwords=';
				} else {			 
					$kcl_target = 'index.php?aid='. $keyland . '&amp;searchwords='; //fallback for older versions
//old_style		$kcl_target = 'index.php?id=0,'. $keyland . ',0,0,1,0&amp;searchwords='; //much older versions
				}
			 break;
		  }
		} else {
			echo "KeyCloud ERROR: --- missing ARTICLE_ID of (L) = Landing Page or (S) = Search Page.";
		}
	} else {
		echo "KeyCloud ERROR! Wrong setup of the RT: MISSING parameter --> A / L / S";
	}


// now go ahead
	switch ($kwremo) {
	   case 'E':
		  //exclude array stuff by marcus@localhorst
		  $excludeid = explode(',',$kwhich_ID);
		  $struct = array_keys($GLOBALS['content']['struct']);
		  $only_cat_id = array_diff($struct,$excludeid);
		  break;
	   case 'I':
		  $only_cat_id = explode(',',$kwhich_ID);
		  break;
	   default: echo "ERROR: Rendermode not defined (I) = match all KEYWORDS of articles withIN named categories or vice versa (E) = exclude categories!";
		  break;
	}
	
	if(is_array($only_cat_id)) {
	   foreach ($only_cat_id as $slid) {
	   $sql = "SELECT SQL_CACHE article_keyword";
	   $sql .= " FROM ".DB_PREPEND."phpwcms_article WHERE article_cid=$slid";
	   $sql .= " AND article_public=1 AND article_aktiv=1 AND article_deleted=0";
	   $sql .= " AND article_begin < NOW() AND article_end > NOW()";
	
	   $result = _dbQuery($sql);
	
		  foreach($result as $row) {
		  // $art_key = $row['0'];
		  $art_key = $row['article_keyword'];
		  $allkeywds .= $art_key.' ';
		  }
	   }

	/*****************************************************************
	and do some convertions
	*****************************************************************/
	$allkeywds = clean_replacement_tags($allkeywds);
	$allkeywds = stripped_cache_content($allkeywds);

	//delete not wantend and then str_all to lower
	if(phpwcms_seems_utf8($allkeywds)) {
		$allkeywds = strtolower_utf8_keywd(str_replace($kwconf['kcl_del_signs'],'',$allkeywds));
	} else {
		$allkeywds = strtolower(str_replace($kwconf['kcl_del_signs'],'',$allkeywds));
	}

	$allkeywds = explode(' ',$allkeywds); //split in separate words
	$kwnum = array_count_values($allkeywds); //count the words -- into new array
	$kwtags = array();
	
	switch ($kwconf['kcl_filter']) {
		case '0':
			foreach($kwnum as $key => $word) {
				if($word >= $kwconf['kcl_min'] && (!in_array($key,$kwconf['kcl_exclude']))) { //look if the word counts the required minimum and is not in the exclude list
					if (strlen($key) >= $kwconf['kcl_min_chars']) { //ignore keywords that are NOT longer as defined in: var kcl_min_chars
						$kwtags[$key] = $word; //put them in a new array
					} // else { $this_word_out[$key] = $word; }
				}
			}
		break;
	
		case '1':
			foreach($kwnum as $key => $word) {
				if($word >= $kwconf['kcl_min'] && (in_array($key,$kwconf['kcl_include']))) { //look if the word counts the required minimum and is not in the exclude list
					if (strlen($key) >= $kwconf['kcl_min_chars']) { //ignore keywords that are NOT longer as defined in: var kcl_min_chars
						$kwtags[$key] = $word; //put them in a new array
					} // else { $this_word_out[$key] = $word; }
				}
			}
		break;
	
		default:
		break;
	}
	
	
	if(!empty($kwtags)){
	   $max_hits = max($kwtags); //keyword with most hits
		 if(!empty($max_hits)) {
	
		  switch ($kwconf['kcl_sort']) {
			 case 'asc':
				ksort($kwtags); //sort them alphabetically
			 break;
			 
			 case 'desc':
				krsort($kwtags); //sort them reverse alphabetically
			 break;
			 
			 case 'random':
				$keys = array_keys($kwtags);
				shuffle($keys);
				$random_words  = array();
				foreach ($keys as $key) {
				  $random_words[$key] = $kwtags[$key];
				}
				$kwtags = $random_words;
			 break;
			 
			 default:
			 break;
		  }
	
		  $key_cloud = '<div class="'.$kwconf['kcl_class'].'">';
			 foreach($kwtags as $key => $word) {
				$key = html_specialchars($key);
				// new maths by Heiko H.
				$percent = round(100 * $word / $max_hits,0);
				$size = ceil($percent/10);
				// to prepare TC font size workin now with CSS
				$key_cloud .= '<a class="kcfs' . $size . '" href="' . PHPWCMS_URL . $kcl_target . urlencode($key) . '">' . $key . '</a>';
				if ($kwconf['kcl_showCount']) {
				   $key_cloud .= $kwconf['kcl_SC_before'].$word.$kwconf['kcl_SC_after'];
				}
				$key_cloud .= " \r\n";
			 }
		  $key_cloud .= '</div>';
		 }
	  }
	}
	return $key_cloud;
}

function strtolower_utf8_keywd($tempString_in) {
	$tempString_out = utf8_decode($tempString_in);
	$tempString_out = strtolower($tempString_out);
	$tempString_out = utf8_encode($tempString_out);
	return $tempString_out;
}

function kcl_fetchalias($ofthisid) {
	// check if landing page has an article alias ( only versions > 1.3.9  else use fallback )
	$checkalias = _dbGet('phpwcms_article', 'article_alias', "article_id=".$ofthisid." AND article_alias != ''");
	if(!empty($checkalias[0]['article_alias'])) {
		$checked = $checkalias[0]['article_alias'];
	} else {
		$checked = ''; // bin hier nicht sicher, ob wirklich notwendig. besser isses allemal.
	}
	return $checked;
}

// fulfillment finally
if(!empty($content["all"]) && !(strpos($content["all"],'{KEYCLOUD:')===false)) {
	$content["all"] = preg_replace_callback('/\{KEYCLOUD:(.*?):(.*?):(.*?):{0,1}(\d+){0,1}\}/', 'make_kwcloud' ,$content['all']);
	$block['css']['keycloud'] = 'specific/keycloud.css';
}

?>
