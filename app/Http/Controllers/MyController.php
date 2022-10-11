<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

 use Google_Client;
 use Google_Service_Analytics;

use App\Models\Myuser;



class MyController extends Controller
{
     public function redirectToGoogle()
     {        
        return Socialite::driver('google')
         ->scopes('https://www.googleapis.com/auth/analytics.readonly')
         ->redirect();
     }

     public function handleGoogleCallback()
     {
        try {

            $gettoken=Myuser::select('token')->first();
            
            if($gettoken['token']!='')
            {
                $olduser = Socialite::driver('google')->userFromToken($gettoken['token']);
                $access_token=$olduser->token;
            }
            else
            {
                dd('Create New Stop');
                $google_user = Socialite::driver('google')->user();
                $access_token=$google_user->token;                
            }
            // dd($google_user->token);

            // $savegoogleuser=new Myuser;
            // $savegoogleuser->token=$google_user->token;
            // $savegoogleuser->google_id=$google_user->id;
            // $savegoogleuser->save();

            $client = new Google_Client();
            $client->setAuthConfig(base_path('google/client_secret.json'));
            $client->addScope('https://www.googleapis.com/auth/analytics.readonly');
            $client->setAccessToken($access_token);
            
            $analytics = new Google_Service_Analytics($client);

            $profile = $this->getFirstProfileId($analytics);

            // Get the results from the Core Reporting API and print the results.
            $results = $this->getResults($analytics, $profile);

            $this->printResults($results);
                    

        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }

    function getFirstProfileId($analytics) 
    {
         // Get the user's first view (profile) ID.

          // Get the list of accounts for the authorized user.
              $accounts = $analytics->management_accounts->listManagementAccounts();

              if (count($accounts->getItems()) > 0) {
                $items = $accounts->getItems();
                $firstAccountId = $items[0]->getId();

            // Get the list of properties for the authorized user.
                $properties = $analytics->management_webproperties
                ->listManagementWebproperties($firstAccountId);                

                if (count($properties->getItems()) > 0) {
                  $items = $properties->getItems();
                  $firstPropertyId = $items[0]->getId();

              // Get the list of views (profiles) for the authorized user.
                  $profiles = $analytics->management_profiles
                  ->listManagementProfiles($firstAccountId, $firstPropertyId);

                  if (count($profiles->getItems()) > 0) {
                    $items = $profiles->getItems();

                // Return the first view (profile) ID.
                    return $items[0]->getId();

                } else {
                    throw new Exception('No views (profiles) found for this user.');
                }
            } else {
              throw new Exception('No properties found for this user.');
          }
        } else {
            throw new Exception('No accounts found for this user.');
        }
    }

    function getResults($analytics, $profileId)
    {  
        // for the last seven days.
        return $analytics->data_ga->get(
          'ga:' . $profileId,
          '7daysAgo',
          'today',
          'ga:users');
    }

    function printResults($results)
    {
        // Parses the response from the Core Reporting API and prints
        // the profile name and total sessions.
      if (count($results->getRows()) > 0) {

    // Get the profile name.
        $profileName = $results->getProfileInfo()->getProfileName();

    // Get the entry for the first entry in the first row.
        $rows = $results->getRows();
        $sessions = $rows[0][0];

    // Print the results.
        print "<p>First view (profile) found: $profileName</p>";
        print "<p>Total sessions: $sessions</p>";
        } else {
            print "<p>No results found.</p>";
        }
    }
}
