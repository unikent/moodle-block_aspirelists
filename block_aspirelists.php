<?php
// Copyright (c) Talis Education Limited, 2013
// Released under the LGPL Licence - http://www.gnu.org/licenses/lgpl.html. Anyone is free to change or redistribute this code.

class block_aspirelists extends block_base {
	function init() {
		$this->title = get_string('aspirelists', 'block_aspirelists');
	}

	function get_content() {
		global $CFG, $COURSE, $DB, $USER;

		if ($this->content !== NULL) {
		  return $this->content;
		}

		$sites = array();

		// Get config for the current target
		$site = get_config('aspirelists', 'targetAspire');
		if ($site) {
			$sites["Canterbury"] = array(
				"url"  => $site,
				"time" => get_config('aspirelists', 'timePeriod')
			);
		}

		// Get config for the alt target
		$altSite = get_config('aspirelists', 'altTargetAspire');
		if ($altSite) {
			$sites["Medway"] = array(
				"url"  => $altSite,
				"time" => get_config('aspirelists', 'altTimePeriod')
			);
		}

		// Die if we cant do this
		if (empty($sites)) {
			$this->content->text = "Talis Aspire base URL not configured. Contact the system administrator.";
			return $this->content;
		}

		$targetKG = get_config('aspirelists', 'targetKG');
		if (empty($targetKG)) {
			$targetKG = "modules";
		}

		$courseDets = $DB->get_record('connect_course_dets', array('course' => $COURSE->id));
		if (empty($courseDets)) {
			$campus = true;
		} else {
			$campus = in_array(strtolower($courseDets->campus), $CFG->aspirelist_campus_white_list) ? true : false;
		}

		$this->content = new stdClass();
		if ($COURSE->shortname && $campus) {
			// Get the code from the global course object, lowercasing it in the process
			$subject = strtolower($COURSE->shortname);
			preg_match_all("([a-z]{2,4}[0-9]{3,4})", $subject, $matches);

			$output = '';
			foreach ($matches[0] as $match) {
				$code = trim($match);

				foreach ($sites as $site => $siteConfig) {
					$output .= '<h3 style="margin-bottom: 2px;">'.$site.'</h3>';
					$output .= $this->curlList($siteConfig["url"], $siteConfig["time"], $targetKG, $code);
				}
			}

			if ($output == '') {
				if (!has_capability('moodle/course:update', context_course::instance($COURSE->id))) {
					$this->content->text = "<p>This Moodle course is not yet linked to the resource lists system.  You may be able to find your list through searching the resource lists system, or you can consult your Moodle module or lecturer for further information.</p>";    
				} else {
					$this->content->text = "<p>If your list is available on the <a href='http://resourcelists.kent.ac.uk'>resource list</a> system and you would like assistance in linking it to Moodle please contact <a href='mailto:readinglisthelp@kent.ac.uk'>Reading List Helpdesk</a>.</p>";
				}
			} else {
				$this->content->text = $output;
			}
		}

		return $this->content;
	}

	function has_config() {
		return true;
	}

	private function curlList($site, $timep, $targetKG, $code) {
		global $COURSE;

		$aconfig = get_config('aspirelists');

		$ch = curl_init();
		$options = array(
			CURLOPT_URL             => "$site/$targetKG/$code/lists.json",
			CURLOPT_HEADER          => false,
			CURLOPT_RETURNTRANSFER  => true,
			CURLOPT_CONNECTTIMEOUT  => $aconfig->timeout,
			CURLOPT_TIMEOUT         => $aconfig->timeout,
			CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1
		);
		curl_setopt_array($ch, $options);
		$response = curl_exec($ch);

		$lists = array();
		if ($response) {
			// Decode the returned JSON data
			$data = json_decode($response, true);
			if (isset($data["$site/$targetKG/$code"]) && isset($data["$site/$targetKG/$code"]['http://purl.org/vocab/resourcelist/schema#usesList'])) {
				foreach ($data["$site/$targetKG/$code"]['http://purl.org/vocab/resourcelist/schema#usesList'] as $usesList) {
					$tp = strrev($data[$usesList["value"]]['http://lists.talis.com/schema/temp#hasTimePeriod'][0]['value']);
					if ($tp[0] === $timep) {
						$list = array();
						$list["url"] = $usesList["value"]; // extract the list URL
						$list["name"] = $data[$list["url"]]['http://rdfs.org/sioc/spec/name'][0]['value']; // extract the list name

						// Let's try and get a last updated date
						if (isset($data[$list["url"]]['http://purl.org/vocab/resourcelist/schema#lastUpdated'])) {
							// ..and extract the date in a friendly, human readable format...
							$time = strtotime($data[$list["url"]]['http://purl.org/vocab/resourcelist/schema#lastUpdated'][0]['value']);
							$list['lastUpdatedDate'] = date('l j F Y', $time);
						}

						// Now let's count the number of items
						$itemCount = 0;
						if (isset($data[$list["url"]]['http://purl.org/vocab/resourcelist/schema#contains'])) {
							foreach ($data[$list["url"]]['http://purl.org/vocab/resourcelist/schema#contains'] as $things) {
								if (preg_match('/\/items\//', $things['value'])) {
									$itemCount++;
								}
							}
						}
						$list['count'] = $itemCount;
						$lists[$list["url"]] = $list;
					}
				}

				// Sort the list
				usort($lists, function($a, $b) {
					return strcmp($a["name"], $b["name"]);
				});
			}
		} else {
			// If we had no response from the CURL request, then set a suitable message.
			return "<p>Could not communicate with reading list system for $COURSE->fullname.  Please check again later.</p>";
		}

		$output = '';

		if (!empty($lists)) {
			foreach ($lists as $list) {
				// Get a friendly, human readable noun for the items
				$itemNoun = ($list['count'] == 1) ? "item" : "items";

				// Finally, we're ready to output information to the browser
				$output .= "<p><a href='".$list['url']."'>".$list['name']."</a>";

				if ($list['count'] > 0) {
					$output .= " (".$list['count']." $itemNoun)";
				}

				if (isset($list["lastUpdatedDate"])) {
					$output .= ', last updated ' . $this->contextualTime(strtotime($list["lastUpdatedDate"])); 
				}

				$output .= "</p>\n";
			}
		} else {
			return null;
		}

		return $output;
	}

	private function contextualTime($small_ts, $large_ts = false) {
		if(!$large_ts) $large_ts = time();
		$n = $large_ts - $small_ts;
		if($n <= 1) return 'less than 1 second ago';
		if($n < (60)) return $n . ' seconds ago';
		if($n < (60*60)) { $minutes = round($n/60); return 'about ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago'; }
		if($n < (60*60*16)) { $hours = round($n/(60*60)); return 'about ' . $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago'; }
		if($n < (time() - strtotime('yesterday'))) return 'yesterday';
		if($n < (60*60*24)) { $hours = round($n/(60*60)); return 'about ' . $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago'; }
		if($n < (60*60*24*6.5)) return 'about ' . round($n/(60*60*24)) . ' days ago';
		if($n < (time() - strtotime('last week'))) return 'last week';
		if(round($n/(60*60*24*7))  == 1) return 'about a week ago';
		if($n < (60*60*24*7*3.5)) return 'about ' . round($n/(60*60*24*7)) . ' weeks ago';
		if($n < (time() - strtotime('last month'))) return 'last month';
		if(round($n/(60*60*24*7*4))  == 1) return 'about a month ago';
		if($n < (60*60*24*7*4*11.5)) return 'about ' . round($n/(60*60*24*7*4)) . ' months ago';
		if($n < (time() - strtotime('last year'))) return 'last year';
		if(round($n/(60*60*24*7*52)) == 1) return 'about a year ago';
		if($n >= (60*60*24*7*4*12)) return 'about ' . round($n/(60*60*24*7*52)) . ' years ago'; 
		return false;
	}
}
