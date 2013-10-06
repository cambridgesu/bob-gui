<?php

/*

	This class implements a unified directory access service.
	Calling classes can use this class to obtain details about organisations.
	
	It contains a set of functions for retrieving data
		getProviders
		getProviderTabs
		getOrganisationsOfUser
		userIsManager
		getOrganisationDetails
		
	Each directory must implement the following:
		->organisationsOfUser ($username, $includeTestOrganisations = false)
			which must return the following fields:
				array (
					'organisationId' => array (
						[id] => test
						[type] => society
						[prefix] => Cambridge University
						[name] => Test Society
						[abbreviation] => CUTEST
						[categoryId] => cusu
						[categoryName] => CUSU
						[organisationName] => TEST Society [CUTEST] - use this for testing
						[organisationNameUnabbreviated] => Cambridge University Test Society
						[baseUrl] => /societies/directory/test
						[profileBaseUrl] => /societies/directory/test
						[eventsBaseUrl] => /societies/directory/test/events
						[logoHtml] => <img alt="Logo" src="/societies/directory/images/test.jpg?672" width="130" height="149" class="right" />
					),
					'anotherOrganisationId' => ...
				);
		
		
		->organisationDetails ($organisationIds)
			which must return:
				
		
	




*/




# Class to provide organisation data
class providers
{
	# Register the directory providers
	private $providers = array ();
	
	
	# Protected provider names, which the Provider API should not be presenting
	private $protectedProviderNames = array ('bob', 'bobgui', 'images', 'openstv', 'style', );
	
	
	# Constructor
	function __construct ()
	{
		# Compute the imageStoreRoot
		foreach ($this->providers as $provider => $attributes) {
			$this->providers[$provider]['imageStoreRoot'] = $_SERVER['DOCUMENT_ROOT'] . $attributes['baseUrl'] . $attributes['imageSubfolder'];
		}
		
	}
	
	
	/*
	 *	getProviders
	 */
	
	# Public accessor
	public function getProviders ()
	{
		return $this->providers;
	}
	
	
	# Public accessor
	public function getProviderTabs ()
	{
		# Add tabs for each of the providers
		$actions = array ();
		foreach ($this->providers as $providerId => $provider) {
			if ($provider['disableTab']) {continue;}	// Skip if tab disabled
			$key = strtolower ($providerId);
			$actions[$key] = array (
				'tab' => '&raquo; ' . $provider['tabText'],
				'description' => $provider['tabDescription'],
				'url' => $provider['baseUrl'] . '/',
			);
		}
		
		# Return the actions
		return $actions;
	}
	
	
	# Function to get a user's organisations
	public function getOrganisationsOfUser ($username, $limitToFields, $limitToProviderId = false, $includeTestOrganisations = false)
	{
		# Return false if no username supplied (e.g. not logged in)
		if (!$username) {return array ();}
		
		# Ask each provider for the organisations of this user
		$organisationsOfUser = array ();
		foreach ($this->providers as $providerId => $provider) {
			
			# Skip if a specific provider has been requested, and this is not it
			if ($limitToProviderId && ($providerId != $limitToProviderId)) {continue;}
			
			# Get the organisations of that user, and add them to the list
			require_once ($provider['classFile']);
			$providerInstance = new $providerId (array (), true);
			$organisationsOfUser[$providerId] = $providerInstance->organisationsOfUser ($username, $includeTestOrganisations);
			
			# Ensure providers do not emit a protected name (which each provider should enforce anyway)
			foreach ($organisationsOfUser[$providerId] as $organisationId => $organisation) {
				if (in_array ($organisationId, $this->protectedProviderNames)) {
					unset ($organisationsOfUser[$providerId][$organisationId]);
				}
			}
			
			# Limit to fields, to avoid leaking data
			/*
			bobguiAdminister requests: logoLocation, organisationName, profileBaseUrl
			eventsPortal requests:     logoLocation, organisationName, eventsBaseUrl
			*/
			foreach ($organisationsOfUser[$providerId] as $organisationId => $organisation) {
				foreach ($organisation as $field => $value) {
					if (!in_array ($field, $limitToFields)) {
						unset ($organisationsOfUser[$providerId][$organisationId][$field]);
					}
				}
			}
		}
		
		# Return the list
		return $organisationsOfUser;
	}
	
	
	# Function to determine a manager match for a supplied user+provider+organisation
	public function userIsManager ($providerId, $organisationId, $username)
	{
		# Get the data
		$organisationsOfUser = $this->getOrganisationsOfUser ($username, $providerId);
		
		# Look up the registry for provider>organisation
		return ($organisationsOfUser && isSet ($organisationsOfUser[$providerId]) && isSet ($organisationsOfUser[$providerId][$organisationId]));
	}
	
	
	# Function to get an organisation's details
	public function getOrganisationDetails ($providerId, $organisationIds /* or string for single item */)
	{
		# Load the provider's class
		require_once ($this->providers[$providerId]['classFile']);
		$providerInstance = new $providerId (array (), true);
		
		# Get the list of results or a single result
		if (is_array ($organisationIds)) {
			$result = array ();
			foreach ($organisationIds as $organisationId) {
				$result[$organisationId] = $providerInstance->organisationDetails ($organisationId);
			}
		} else {
			$result = $providerInstance->organisationDetails ($organisationIds);
		}
		
		# Return the organisation or list of organisations
		return $result;
	}
}

?>
