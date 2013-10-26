<?php

/* Example usage:

# Load required libraries
require_once ('camUniData.php');

# Valid CRSID check
$result = camUniData::validCrsid ('abc01');
echo $result;

# Get lookup data - can accept an array or string
$person = camUniData::getLookupData ('sqpr1');
print_r ($person);
$people = camUniData::getLookupData (array ('xyz12', 'sqpr1'));
print_r ($people);

*/


/*
	# Note for future
	# If adding a regexp for matching a Cambridge University e-mail, bear in mind that the following formats listed below are also supported.
	# See e-mail from fanf2 dated Wed, 22 Jul 2009 20:26:37 +0100, Message-ID: <alpine.LSU.2.00.0907222009070.17246@hermes-2.csi.cam.ac.uk> describing this
	crsid+detail@cam.ac.uk
	crsid--detail@cam.ac.uk
	crsid+detail@ucs.cam.ac.uk
	crsid--detail@ucs.cam.ac.uk
	forename.surname+detail@ucs.cam.ac.uk
	forename.surname--detail@ucs.cam.ac.uk
*/


# Version 1.2.1

# Class containing Cambridge University -specific data-orientated functions
class camUniData
{
	# Function to check a valid CRSID - checks syntax ONLY, not whether the CRSID is active or exists
	public static function validCrsid ($crsid, $mustBeLowerCase = false)
	{
		# Get the regexp
		$regexp = self::crsidRegexp ($mustBeLowerCase);
		
		# Return the result as a boolean
		return (preg_match ('/' . $regexp . '/', $crsid));
	}
	
	
	# Function to return the regexp for a CRSID
	public static function crsidRegexp ($mustBeLowerCase = false)
	{
		# Define the letter part
		$letters = ($mustBeLowerCase ? 'a-z' : 'a-zA-Z');
		
		# Define the regexp - as defined by fanf2 in Message-ID: <cEj*NC0dr@news.chiark.greenend.org.uk> to ucam.comp.misc on 060412
		# NB: ^([a-z]{2,5})([1-9])([0-9]{0,4})$ doesn't deal with the few people with simply four letter CRSIDs
		$regexp = '^[' . $letters . '][' . $letters . '0-9]{1,7}$';
		
		# Return the regexp
		return $regexp;
	}
	
	
	# Function to get user details
	public static function getLookupData ($crsids = false, $dumpData = false, $institution = false, $fields = array ('uid', 'cn', 'displayname', 'labeleduri', 'mail', 'sn', 'telephonenumber', 'title', 'dn', 'ou'))
	{
		# Ensure the LDAP functionality exists in PHP
		if (!function_exists ('ldap_connect')) {
			return NULL;
		}
		
		# Connect to the lookup server
		if (!$ds = ldap_connect ('ldap.lookup.cam.ac.uk')) {
			return NULL;
		}
		
		# Bind the connection
		$r = ldap_bind ($ds);    // this is an "anonymous" bind, typically read-only access
		
		# Ensure all are lower-cased
		if (is_array ($crsids)) {
			foreach ($crsids as $key => $crsid) {
				$crsids[$key] = strtolower ($crsid);
			}
		} else {
			$crsids = strtolower ($crsids);
		}
		
		# Define the search string, imploding an array if in array format
		if ($crsids) {
			$searchString = (!is_array ($crsids) ? "uid={$crsids}" : '(|(uid=' . implode (')(uid=', $crsids) . '))');
		} else if ($institution) {
			$searchString = "(&(instID={$institution})(objectClass=camAcUkPerson))";
		}
		
		# End if no search string
		if (!isSet ($searchString)) {return false;}
		
		# Obtain the data
		$sr = ldap_search ($ds, 'ou=people,o=University of Cambridge,dc=cam,dc=ac,dc=uk', $searchString, $fields);  
		$data = ldap_get_entries ($ds, $sr);
		
		# Close the connection
		ldap_close ($ds);
		
		# End by returning false if no info or if the number of results is greater than the number supplied
		if (!$data || !$data['count'] || ($crsids && ($data['count'] > count ($crsids)))) {
			return false;
		}
		
		# Dump data to screen if requested
		if ($dumpData) {
			require_once ('application.php');
			application::dumpData ($data);
		}
		
		# Arrange the data
		foreach ($data as $index => $person) {
			
			# Skip the count index
			if ($index === 'count') {continue;}
			
			# Get the CRSID first
			$crsid = $person['uid'][0];
			
			# Arrange the data
			$people[$crsid] = array (
				'name' => (isSet ($person['displayname']) ? $person['displayname'][0] : (isSet ($person['cn']) ? $person['cn'][0] : false)),
				'email' => (isSet ($person['mail']) ? $person['mail'][0] : "{$crsid}@cam.ac.uk"),
				'department' => (isSet ($person['ou']) ? $person['ou'][0] : false),
				'college' => ((isSet ($person['ou']) && isSet ($person['ou'][1])) ? $person['ou'][1] : false),
				'title' => (isSet ($person['title']) ? $person['title'][0] : false),
				'website' => (isSet ($person['labeleduri']) ? $person['labeleduri'][0] : false),
				
				'username' => $crsid,
				'surname' => (isSet ($person['sn']) ? $person['sn'][0] : false),
				'telephone' => (isSet ($person['telephonenumber']) ? $person['telephonenumber'][0] : false),
				// 'dn' => (isSet ($person['dn']) ? $person['dn'][0] : false),
			);
			
			# Trim
			foreach ($people[$crsid] as $key => $value) {
				$people[$crsid][$key] = trim ($value);
			}
			
			# Compute the forename by chopping off the surname
			if ($people[$crsid]['name'] && $people[$crsid]['surname']) {
				$delimiter = '/';
				$people[$crsid]['forename'] = trim (preg_replace ($delimiter . preg_quote ($people[$crsid]['surname'], $delimiter) . '$' . $delimiter, '', $people[$crsid]['name']));
			}
		}
		
		# Sort the list
		ksort ($people);
		
		# Return the data, in the same format as supplied, i.e. string/array
		return (($crsids && !is_array ($crsids)) ? $people[$crsids] : $people);
	}
	
	
	# Function to get a user list formatted for search-as-you-type from lookup; see: http://www.ucs.cam.ac.uk/lookup/ws and the 'search' method at http://www.lookup.cam.ac.uk/doc/ws-javadocs/uk/ac/cam/ucs/ibis/methods/PersonMethods.html
	public static function lookupUsers ($term, $autocompleteFormat = false, $indexByUsername = false)
	{
		# Define the URL format, with %s placeholder
		$urlFormat = 'https://anonymous:@www.lookup.cam.ac.uk/api/v1/person/search?attributes=displayName,registeredName,surname&limit=10&orderBy=identifier&format=json&query=%s';
		$url = sprintf ($urlFormat, $term);
		
		# Get the data
		if (!$json = file_get_contents ($url)) {return array ();}
		
		# Decode the JSON
		$json = json_decode ($json, true);
		
		# Find the results
		if (!isSet ($json['result']) || !array_key_exists ('people', $json['result'])) {return array ();}		// Should only happen if the format has changed - an empty result will still have this structure
		$people = $json['result']['people'];
		
		# End if none
		if (!$people) {return array ();}
		
		# Arrange as array(username=>name,...)
		$data = array ();
		foreach ($people as $person) {
			$key = $person['identifier']['value'];
			$value = $key . ' (' . $person['visibleName'] . ')';
			$data[$key] = $value;
		}
		
		# For autocomplete format, arrange the data; see http://af-design.com/blog/2010/05/12/using-jquery-uis-autocomplete-to-populate-a-form/ which documents this
		if ($autocompleteFormat) {
			$dataAutocompleteFormat = array ();
			$isTokenisedFormat = ($autocompleteFormat === 'tokenised');	// Older format
			foreach ($data as $value => $label) {
				if ($isTokenisedFormat) {	// Older format
					$dataAutocompleteFormat[$value] = array ('id' => $value, 'name' => $value);	// q=searchterm&tokenised=true
				} else {
					$dataAutocompleteFormat[$value] = array ('label' => $label, 'value' => $value);	// term=searchterm
				}
			}
			$data = $dataAutocompleteFormat;
		}
		
		# Strip keys if required
		if (!$indexByUsername) {
			$data = array_values ($data);
		}
		
		# Return the data
		return $data;
	}
	
	
	# Autocomplete wrapper
	public static function autocompleteNamesUrlSource ()
	{
		#!# Needs to be generalised
		return 'http://intranet.geog.cam.ac.uk/contacts/database/data.html?source=localstaff,lookup';
	}
}

?>