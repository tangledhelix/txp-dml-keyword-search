<?php

// run this file at the command line to produce a plugin for distribution:
// $ php dml_keyword_search.php > dml_keyword_search-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Uncomment and edit this line to override:
# $plugin['name'] = 'abc_plugin';

$plugin 'version']     = '0.1';
$plugin['author']      = 'Dan Lowe';
$plugin['author_uri']  = 'http://tangledhelix.com/';
$plugin['description'] = 'Extend the search function to allow keyword searching';

// Plugin types:
// 0 = regular plugin; loaded on the public web side only
// 1 = admin plugin; loaded on both the public and admin side
// 2 = library; loaded only when include_plugin() or require_plugin() is called
$plugin['type'] = 0; 

@include_once('zem_tpl.php');

// {{{ plugin help

if (0) {
?>
# --- BEGIN PLUGIN HELP ---

<p><strong>txp:dml_keyword_search</strong></p>

<p>This plugin makes it possible to do a search against keywords in addition to
the usual title and body.</p>

<p>I'll write help someday.</p>

# --- END PLUGIN HELP ---
<?php
}

// }}}

# --- BEGIN PLUGIN CODE ---

// {{{ _dml_keyword_search_do_search()

function _dml_keyword_search_do_search($atts, $count_only) {
	global $q;

	extract(lAtts(array(
		'limit' => 500
	) ,$atts));

	if ($q != '') {
		// prevent searches in sections where search is disabled
		$s_filter = '';
		$rs = safe_column('name', 'txp_section', "searchable != '1'");
		if ($rs) {
			foreach ($rs as $name) {
				$filters[] = "and Section != '$name'";
			}
			$s_filter = join(' ', $filters);
		}

		// construct query
		$select = '*, unix_timestamp(Posted) as uPosted,'
			. " match (Title,Body,Keywords) against ('$q') as score";

		$where = "(Title rlike '$q' or Body rlike '$q' or Keywords rlike '$q')"
			. " $s_filter and Status = 4 and Posted <= now()"
			. " order by score desc limit $limit";

		$rs = safe_rows($select, 'textpattern', $where);

		if ($count_only) {
			return count($rs);
		} else {
			return $rs;
		}
	}

	return '';
}

// }}}

// {{{ dml_keyword_search()

function dml_keyword_search($atts) {
	$rs = _dml_keyword_search_do_search($atts);

	if ($rs) {
		$articles = array();

		foreach ($rs as $a) {
			populateArticleData($a);
			$form = gAtt($atts, 'searchform', 'search_results');
			$article = fetch_form($form);
			$articles[] = parse($article);
		}

		return join ('',$articles);
	}
	return '';
}

// }}}

// {{{ dml_keyword_search_count()

function dml_keyword_search_count($atts) {
	extract(lAtts(array(
		text => 'Found %s matches',
		single => 'Found %s match',
		zero => 'Sorry, there were no matches for your query'
	) ,$atts));

	$count = _dml_keyword_search_do_search($atts, 'count_only');

	if ($count == 1) {
		$ret = $single;
	} elseif ($count === 0) {
		$ret = $zero;
	} else {
		$ret = $text;
	}

	return str_replace('%s', $count, $ret);
}

// }}}

# --- END PLUGIN CODE ---

?>
