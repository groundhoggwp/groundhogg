=== Groundhogg ===
Contributors: trainingbusinesspros, groundhogg, dhrumit
Tags: marketing, email, contacts, contact, CRM, marketing automation, email automation, funnels, marketing funnels, marketing campaigns, campaigns, broadcast, contacts, contact management
Donate link: https://groundhogg.io
Requires at least: 4.9
Tested up to: 5.1
Requires PHP: 5.6
Stable tag: trunk
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl.md

The first WordPress powered open source marketing automation plugin that requires no external, monthly paid, expensive platform that is too complicated to use anyway.

== Description ==

Groundhogg provides the essential tools for you to be able to communicate with your list effectively.

Use Benchmarks (interactions with your site) to trigger Actions with your list to provide a more meaningful conversation.

Groundhogg provides tools that allow you to create and implement high converting email marketing funnels in a matter of minutes. If you're new to marketing, Groundhogg also comes with pre-built funnel and email templates to speed up the process.

Our [extensions library](https://groundhogg.io/downloads/) will allow you to connect WordPress' most powerful plugins, adding more trigger points and useful interaction tools.

= How Does It Work? =

Groundhogg combines the basics of your run of the mill CRM with a simple yet highly versatile funnel builder.

The funnel builder allows you to use benchmarks and actions in any combination to design the perfect customer journey which brings them from State A to State B. (See the screenshots to see an example of our visual builder)

= Benchmark Triggers Include: =

* When an account is created.
* When a contact's email is confirmed.
* Whenever a form is filled out.
* A page is visited.
* A User's role is changed.
* A tag is applied
* OR if a tag is removed.

= Any of those triggers can launch these Actions: =

* Send an email
* Create an admin notification
* Apply a note
* Apply a tag
* Remove a tag
* Apply a contact Owner
* Wait till a specific date
* Wait for a certain amount of time
* Create a user account
* Edit contact details
* OR send an HTTP POST

With our simple visual editing experience, you can use any combination of the above to create a funnel that will ensure your potential customers are paying customers.

= By Marketers For... None Marketers? =

We understand that not everyone is born with epic writing abilities and that not everyone knows how to put together a killer sales funnel.

So you don't have to, we did it for you! Groundhogg comes with a suite of default funnels with pre-written emails and preset settings so that you don't have to know the "How," just the "What."

If you're struggling with your current CRM or marketing platform, then ditch it. Export your mailing list and import it into Groundhogg and start having more meaningful conversations that creates a bigger impact.

== Screenshots ==
1. The funnel builder in all its glory, Benchmarks show on right.
2. The Funnel builder in reporting mode
3. The funnel builder in all its glory, Actions show on right.
4. Our HTML Email editor
5. Use Superlinks to dynamically send contacts to different places
6. Segment contacts using custom tags
7. Import or export Funnels across multiple sites.
8. Ditch your current CRM and import your list TODAY!
9. Start with awesome funnel templates designed by digital marketers.
10. Pre-written email templates provide inspiration for you next literary masterpiece.

== Installation ==
= Plugin Repo =

* Install
* Activate
* Complete the guided setup
* Start Marketing

= FTP =

* Upload the zip archive
* unzip
* activate through the plugins manager
* Complete guided setup
* Start Marketing

== Frequently Asked Questions ==
= Do I need any additional marketing software like mailchimp? =
No, Groundhogg is a completely standalone, lightweight marketing system designed to keep you in the black, even when it comes to your monthly software bill.

= Is there any monthly fee or required upgrade? =
No, you can use Groundhogg for free forever. Groundhogg is a Open Source project designed to democratize email marketing and provide simple easy to use tools to new and none tech savvy business owners.

= Will it connect to Woocommerce or other plugins? =
Yes, you can find our Official [Woocommerce](https://groundhogg.io/downloads/woocommerce) extension on our site among other extensions for other popular plugins.

= Is it secure? =
We follow all best WordPress security practices. Plus it's open source, so we have a large community looking out for potential weaknesses when it comes to your data.

= Is it GDPR, CANSPAM, or CASL compliant? =
Yes, we have tools available to meet the criteria of all the worlds ANTI-SPAM & privacy legislation.

== Changelog ==

= 1.2.2 =
* ADDED Schedule broadcasts at time in subscribers' timezone.
* ADDED Schedule timers to run in subscribers' timezones.
* ADDED Schedule SMS Broadcasts to run in subscribers' timezones.
* ADDED Geographic reports to dashboard
* ADDED Pie chart of optin status report
* TWEAKED Funnel Breakdown will now also feature a line chart of contact progress
* TWEAKED Emails sent using the Groundhogg Sending Service will not longer use wp_mail as backup for error reporting purposes
* TWEAKED The Groundhogg Sending Service url is now aws.groundhogg.io to improve the speed of requests.

= 1.2.1 =
* ADDED Automatic GEO location via geoplugin.net when a form is submitted and IP is collected.
* ADDED Extrapolate location from IP via contact record.
* ADDED Timezone and IP field to location section of contact record
* TWEAKED changed "Address" to "Location" in contact record
* TWEAKED excluded protected meta data from custom info tab

= 1.2 =
* ADDED Gravatar image to contact profile.
* ADDED Ability to unlink contact records from user accounts.
* ADDED Guided Setup UI for new installations.
* ADDED Central database for SMS so it can be used throughout plugin.
* ADDED UI for managing SMS globally
* ADDED Error reporting for failed events in the events screen.
* ADDED Process events direct from contact activity tab.
* ADDED Support for manually confirming a contact's email address.
* ADDED Login abandonment funnel template
* ADDED SMS API endpoint.
* ADDED IMAP Test connection UI and more Bounce checker settings.
* TWEAKED improved email and instruction copy in the funnel templates.
* TWEAKED Add note UI rather than edit notes directly
* TWEAKED form fields now default to required false, so no need to add required false into the form shortcode. Explicit required="true" now required.
* TWEAKED removed SMS character limit.
* TWEAKED removed 5 second check preventing emails sent in succession, no longer needed.
* FIXED Email content centered if not explicitly given left align
* FIXED Emails sent if in draft mode.
* FIXED Non required fields are being required by the submission handler.

= 1.1.4 =
* ADDED Search form to welcome page for docs help
* ADDED Distinct helper methods to Funnel Parent Class
* TWEAKED Welcome page now queries docs direct from documentation site
* TWEAKED Welcome page now queries extensions direct from store
* TWEAKED Stats collection will retrieve display_name

= 1.1.3 =
* FIXED First & Last being set to null if not present in form.
* TWEAKED Form spam verification is now more strict.
* TWEAKED Is submission fails information is saved so you do not need to re-enter it all.

= 1.1.2 =
* FIXED File upload file type specification not working
* FIXED File upload not working if files not specified

= 1.1.1 =
* ADDED New admin form submit action accessible from the contact record
* TWEAKED Added spacing around button block & fixed centering issue for improved usability in outlook.
* TWEAKED converted spacer to table format for outlook compat
* TWEAKED Default email template for improved outlook compat
* TWEAKED Image block now also uses width attribute for compatibility across platforms.
* TWEAKED New filter and action hook guide
* TWEAKED Form builder content area now sizes to the content automatically
* TWEAKED Form builder content is auto formatted
* TWEAKED Long form country is now converted to country code when importing
* FIXED Email report wrongly included queued email statistics
* FIXED Reporting month time ranges are now more specific

= 1.1 =
* ADDED Prompt to allow anonymous usage tracking.
* ADDED Logged in benchmark
* ADDED Ability to deactivate a license from the licenses tab in the settings.
* ADDED Ability to add tags based on radio button, dropdown selection, or checkbox enabling in the form builder.
* TWEAKED Improved localization all around.
* TWEAKED Quick UI improvement for creating emails from the broadcast view.
* TWEAKED added URL params for pages with dynamic content to help excluding in various caching plugins.
* TWEAKED API for ordering sub menu items
* TWEAKED Renamed Groundhogg submenu item to Welcome
* TWEAKED Various CSS fixes
* TWEAKED SMTP check now checks for MOST WP SMTP plugins as well as Groundhogg service before publishing notice.
* TWEAKED Referral prompt is now removed if the installation has active extensions.
* TWEAKED Form builder is a little bit more specific when including fields to be added
* FIXED z-index issue causing admin bar in editors to display above the admin bar.

= 1.0.24 =
* ADDED Elementor Forms Integration.
* TWEAKED Upped limit of contacts per request during contact import from 25 to 100 to limit the number of requests.
* FIXED Import script making concurrent requests causing 503 errors for really large lists. Requests are now consecutive instead.

= 1.0.23.1 =
* ADDED AR_es locale
* TWEAKED Import contacts efficiency.
* FIXED pass by reference notice in contact edit screen.

= 1.0.23 =
* ADDED Tabbed contact record sections to cut down on scrolling.
* FIXED Var dump $_FILES in contact record update.
* FIXED Modal defaults only being loaded in funnel builder causing the modal not to work

= 1.0.22 =
* ADDED Quick export button to contacts view
* ADDED Import link to contact page
* ADDED Actions for registering new From builder shortcodes
* ADDED much better way of handling form errors.
* UPDATED New REST API method for GH Email & SMS service that significantly improves usage performance and provides superior error reporting.
* FIXED Some funnel builder assets loading on every page.

= 1.0.21.1 =
* ADDED Failed status to Events table
* FIXED Rest API function typo.

= 1.0.21 =
* FIXED fatal error on multisite conversion of users to contacts.
* FIXED email preferences center not being installed on multisite subsites.

= 1.0.20.5 =
* ADDED WP Bakery block support
* REMOVED Semaphore usage in the event queue as it was causing problems.
* FIXED Call to deprecated function get_user_by_email()

= 1.0.20.4 =
* FIXED Fatal error when adding users or updating user roles.

= 1.0.20.3 =
* ADDED Translations for es_ES, fr_FR, fr_CA, ja, pt_BR, ro_RO
* ADDED Translations
* FIXED PHP Fatal Error when activating for the first time.

= 1.0.20.2 =
* TWEAKED Base API Class
* FIXED PHP error from direct accessing unresolved array.
* FIXED PHP Fatal error when activating.

= 1.0.20.1 =
* ADDED Beaver Builder form block.
* TWEAKED Test Email selection will now default to the current user.
* TWEAKED More proper error messages when now test email is selected.
* FIXED Contact => Account linking was not working properly.

= 1.0.20 =
* ADDED Iframe support for Forms! You can now paste forms into your CRM on any site anywhere.
* ADDED Support for custom page builder blocks. Elementor is the first to be added. Others will come later.

= 1.0.19.5 =
* ADDED Raw email confirmation link that can be put in buttons.
* TWEAKED Create user step will now update a users level if the user already exists instead of adding another action to do so.
* TWEAKED Renamed the cron job to wpgh_process_queue for better semantics.
* TWEAKED Checking bounces now occurs hourly rather than with the queue process to avoid anything breaking being buggy.
* TWEAKED Some benchmarks process the queue instantly. The following benchmarks are Forms, Link Clicks, Page Views & Email Confirmations
* FIXED when creating a new email in a funnel you had to select the email after creating it which was annoying. The email is now auto updated as expected.
* FIXED When editing an email and clicking "Save Changes" in the funnel builder not working.

= 1.0.19.4 =
* ADDED Option to change the queue interval time.
* ADDED plugin API to the multisite options page.
* TWEAKED CSS for inputs & text areas hardened to not be overwritten by WC.

= 1.0.19.3 =
* ADDED UTM Reporting
* ADDED Preview modal for the form builder.
* ADDED White labelling support
* TWEAKED better tracking support for UTM variables
* TWEAKED events belonging to a deleted step get move forward to the next available action.
* FIXED partial match URL not accepting partial match strings.
* FIXED wrong verbage for reporting toggle caused by altered css ID

= 1.0.19.2 =
* ADDED Source Page report to dashboard.
* FIXED Bug where empty title showing on contact record with no first or last name.
* FIXED Settings page setup so that extensions can add option easier.

= 1.0.19.1 =
* FIXED Create user step setting optin status to unconfirmed by accident.

= 1.0.19 =
* ADDED New Contacts By Social Media Source Report
* ADDED New Contacts By Search Engine Source Report
* TWEAKED Added new reporting times to funnel reporting view in correlation with the dashboard
* FIXED Collapse status of steps saved.
* FIXED Bugs where collapsed report widgets caused JS Errors.

= 1.0.18.2 =
* ADDED time to next queue run in the events table
* FIXED manually process events button CSS

= 1.0.18.1 =
* ADDED Export ability to reporting widgets
* ADDED several new default reporting time ranges.
* ADDED funnel breakdown report widget
* ADDED Active Funnels report widget
* ADDED Lead Source Activity report
* ADDED reporting capabilities for admin and marketers
* ADDED Compare option for META_QUERY in WPGH_CONTACT_QUERY class
* FIXED could not collapse step if newly added.
* FIXED could not delete all tags from a contact
* FIXED file upload error on contact editor page when no files uploaded
* TWEAKED moved dashboard reporting to widgets in the WP admin dashboard
* TWEAKED the delay time between consecutive events have been changed from 10 seconds to 0 to allow for recursive and immediate queue iteration.
* TWEAKED Cron schedule changed from every 10 minutes to every 5 minutes.
* REMOVED Dashboard page

= 1.0.18 =
* ADDED replacement codes {owner_first_name} & {owner_last_name}
* ADDED date_picker generator to the HTML helper class
* TWEAKED steps are now collapsible for better ui
* TWEAKED Form layout CSS for better compatibility
* TWEAKED Ignore tracking link replacement on mailto: links
* TWEAKED Removed the ajax process functionality for queue events altogether because it was eating up too many resources and causing server timeouts in edge cases, implementation has been left included in the event someone comes up with a better way to tackle it. The best way to ensure events go out on time is to set up the server scheduler
* TWEAKED Queue will run recursively until all successive scheduled events are completed.
* FIXED Textarea in form builder not showing options

= 1.0.17.1 =
* Fixed Contact Scripts breaking quick edit links in admin area

= 1.0.17 =
* ADDED Contact File Management. Upload files from both admin and Frontend to contact record.
* ADDED email management and sending via the REST API.
* ADDED Date and Time form fields
* FIXED grammer and spelling mistakes returned via the REST API.
* FIXED Bug where user role tags were not being applied properly.

= 1.0.16.3 =
* ADDED Account Creation button in contact record if the contact does not have an associated user account.
* TWEAKED API V2 Improvements in json responses
* TWEAKED default method for hooking into the user_register hook that in turn triggers the Account Created benchmark

= 1.0.16.2 =
* FIXED Nested function loop causing 500 level error

= 1.0.16.1 =
* TWEAKD MAX Ajax queue calls is 5 per page view to limit server load.
* FIXED Upgrader class causing 500 error in admin
* FIXED Form Shortcode calling class constructor with to many arguments.

= 1.0.16 =
* ADDED introduction of the Marketplace
* ADDED user roles re auto converted to tags and applies to the contact so sending broadcasts to specific users becomes easier
* ADDED the standard create_contact_from_user function that can be used in multiple contexts...
* ADDED upgrade class to handle updates
* ADDED better API structure and authentication
* ADDED API tab in settings area
* ADDED Standardized method for updating email marketing preferences
* ADDED Field timer for dynamic delay times.
* FIXED Email block toolbar disappearing after save

= 1.0.15.1 =
* TWEAKED Role Changed benchmark now also supports add_user_role as well.
* TWEAKED Double check max event setting so as not to imply it could be 0
* FIXED wrong setting name for Email Confirmation page

= 1.0.15 =
* ADDED Gutenberg Form Block
* TWEAKED HTML block in email builder support code-mirror now
* TWEAKED minified ALL js files for faster performance
* TWEAKED checkbox field now has visible value option in form builder
* FIXED Email HTML editor view not working when editing emails in the funnel builder
* FIXED serialized meta was not being duplicated properly when duplicating campaigns

= 1.0.14.1 =
* ADDED Email browser page auto created on install
* FIXED Meta data tables not registered during plugin activation causing email preferences funnel to not register correctly.
* FIXED steps being added if dragged in but not dropped.
* TWEAKED notice if max_input_vars to small for funnel.
* TWEAKED removed step_order input arg from funnel editor to conserve max_input_vars since it wasn't being ued.

= 1.0.14 =
* FIXED slashes appearing when saving contact names with the ['] of ["] symbol.
* FIXED quotes escaped in email subject line and preheader.
* TWEAKED better handling of changing email addresses for a contact.
* TWEAKED exclude meta list so that meta data does not appear moe than once

= 1.0.13.4 =
* ADDED support articles to support articles column on welcome page
* TWEAKED double check semaphore locking is enabled
* FIXED get_pages() not working on global multisite

= 1.0.13.3 =
* ADDED Go back button in broadcast report if the broadcast has yet to be sent.
* TWEAKED Export function now exports all the contact meta as well.
* FIXED mixing source_page & page_source, now just page_source

= 1.0.13.2 =
* ADDED New link click benchmark
* ADDED Exclude list for scheduling broadcasts
* FIXED Settings bug where privacy_policy and terms were overwriting each other.

= 1.0.13.1 =
* Forgot some critical files

= 1.0.13 =
* ADDED code to prepare for bounce responses from AWS
* ADDED new graph for form activity.
* ADDED Replacement Codes Popup
* TWEAKED View email in browser now has a shortcode and a specific page to use.
* TWEAKED the settings page is now much more extendable  for extra plugins.
* TWEAKED there is now a check to see if a "robot" is submitting form. All robots will fail.
* FIXED Form builder was using dropdown shortcode but that shortcode wasn't actually registered...

= 10.0.12 =
* ADDED Interactive Broadcast pie chart.
* ADDED Dashboard Reports.
* TWEAKED Form Address Field to include proper placeholder information
* TWEAKED Form Address Field to have better condensed layout
* TWEAKED Report to use the jquery Flot library
* TWEAKED Import contact trimming column headers
* TWEAKED Better error reporting when contact import fails.

= 10.0.11.1 =
* FIXED Line which included the report missing
* TWEAKED added new code for the default form.

= 10.0.11 =
* ADDED New Funnel Reporting Graph at top of funnel when in reporting mode.
* ADDED compatibility for modal outside of funnel editor.
* TWEAKED New form build improvements. Popup UI for adding fields in the form builder.

= 10.0.10.4 =
* ADDED Introduced funnel warnings for date timers with descending dates and dates that are in the past.

= 10.0.10.3 =
* ADDED some mobile compatibility for the funnel view. Mostly just for basic edits and funnel reporting purposes.
* FIXED bug where cron was called a private function.

= 1.0.10.2 =
* TWEAKED Groundhogg email service no sends email along with sender name + sender email
* TWEAKED Event quueue locking using semaphore if it exists as a library.

= 1.0.10.1 =
* Fixed bug where broadcasts were always being schedules for 9:30 AM despite other settings.
* TWEAKED Cleaned up some event queue code
* TWEAKED Switched back to an older method of queue traversal. There is now only a single ajax request that can process the event queue every 30 seconds to avoid queue collisions.

= 1.0.10 =
* ADDED SMS messaging step. Uses the same credit system as emails.
* FIXED Misc bug fixes.

= 1.0.9.2 =
* FIXED contact->update() method not updating properties
* TWEAKED parent methods in WPGH_Funnel_Step no longer call __doing_it_wrong
* TWEAKED Queue now gives a thread ID to check on each while loop if the queue is the only one in action.
* TWEAKED Queue will spawn separately on multisite unless global multisite usage is enabled.

= 1.0.9.1 =
* FIXED Share link not exporting emails or tags.
* TWEAKED Hooks for deleting and saving contacts

= 1.0.9 =
* ADDED System Report
* ADDED Conversion Rate Reporting for the Form Fill Step.
* ADDED Skip Confirmation emails if already confirmed.
* TWEAKED Email step now defaults to most recent email instead of oldest email.
* TWEAKED "Form Fill" step now called "Web Form" step.
* FIXED Email link picker bug replacing line before with link picker text if adding link at the beginning of a text block.

= 1.0.8.2 =
* ADDED Share link to allow the sharing of funnels via links.
* TWEAKED Disabled autocomplete for broadcast date picker.
* FIXED Clicking image or button in email editor cause page reload.
* FIXED PHP warning if a tag is deleted but a broadcast report is trying to access it.

= 1.0.8.1 =
* FIXED queue failsafe bug

= 1.0.8 =
* FIXED could not use function in right context fatal error.
* FIXED multisite funnel capability comparing string to int.
* TWEAKED adding emails is also now done through the admin modal system.
* TWEAKED better handling of edit email link when email changes.
* FIXED activation error "you do not have permissions..."
* TWEAKED DBs now clean themselves whenever stuff is deleted...
* ADDED GH email sender API!
* TWEAKED added failsafe event running to ensure that no event is run TWICE within 5 minutes...
* TWEAKED emails cannot be sent within 30 seconds of each other to a contact.
* TEAKED email sending delayed by 30 seconds

= 1.0.7 =
* FIXED Emails were not updating from the funnel editor when click the "save changes button" in the modal. Click said button will now update the email.
* FIXED Implemented the queue not being able to be started by two different requests.
* FIXED multisite bugs
* FIXED Tracking will force ssl if SSL is present in the blog url option.
* FIXED duplicating steps in funnel literally duplicating the step which helps no one.
* FIXED contact count not updating properly when contacts deleted.
* FIXED longer imports using different import tag.
* TWEAKED The create user step will now ask which blog you want to add the user too if on multisite.
* TWEAKED if the funnel step is not available for the funnel builder than show an unkown question mark.
* TWEAKED an event cannot be added to the queue if there is a similar event within 60 seconds of the time its being added.
* TWEAKED if global multisite setting is enabled you can choose to only run an event if the associated step can be run by the current blog.

= 1.0.6 =
* Added Funnel updates automatically when the modal closes.
* Added Contacts which have unconfirmed emails will show as (Unconfirmed) int he table view
* Added new wpgh_get_contact( $id_or_email ) function instead of new WPGH_Contact
* Fixed removed console.log() calls.
* Added form CSS to auto style some elements for compatibility with the from styling extension.
* Changed the benchmark picker to be order by OPT GROUP and only include active steps.

= 1.0.5 =
* Changed WP_Popup to be a singleton class. Enqueue with wpgh_enqueue_modal(); Calling new WPGH_Popup() will throw an error.
* Added WP_Popup support for source from URLS. Use #source=<?php urlencode( 'https://mysite.com' ) ?>
* Added WP_Popup support for width and height from init href. Use #source=id&width=400&height=500
* Fixed links which trigger the popup now prevent default behaviour
* Changed editing emails in funnels to open up the modal with an iFrame of he editing screen rather than opening up a new tab for better UX.

= 1.0.4 =
* If a label is not present in a custom text field then use the placeholder as that is the next likely place.
* Added Popup modal from Styling extension to core instead.
* Added ability to add contacts to a funnel via the edit funnel UI.
* Added ability to add contact to a funnel via the edit contact UI.
* Added "mailto" option in List-Unsubscribe header.
* Added "Import Y-m-d H:i:s" added to contacts when imported.
* Added spinners next to import/export buttons.
* Added BULK delete by tag to the tools.
* Changed class WPGH_Importer to WPGH_Bulk_Contact_Manager
* Fixed import status showing 100% when actually not done.
* Fixed import/export js loading on every page, no only loads on tools page
* Fixed superlinks not redirecting on multisite.

= 1.0.3 =
* Changed WPGH_Contact_Query can now accept optin_status as an array of status options.
* Added the broadcast reporting view
* Fixed email preferences form not being installed correctly.

= 1.0.2 =
* Added WPGH_Form instance to wpgh_form_code filter
* Added Columns and Rows to the Funnel Form Editor
* Added base class CSS for frontend forms
* Fixed text editor bar is now sticky when scrolling.

= 1.0.1 =
* Changed to a vastly superior layout in the funnel editor.
* Fixed load funnel editor scripts in head rather than in footer.
* Changed editing view for emails to make similar to new Funnel Editor layout.
* Fixed load email editor scripts in head instead of footer.

= 1.0 =
* Stricter checking of whether a contact exists or not.
* Fixed wonky text editing in email editor.
* Allowed for HTML and richtext in the form element.

= 0.9.18 =
* Added round robin functionality to Apply Owner step.
* Changed Sales Managers can only see contacts they are the owner of.
* Fixed the create user step now links a user record to a contact if it exists... may be redundant but it's best to make sure.
* Fixed [attributes] arg in HTML class being escaped when it shouldn't be
* Fixed funnel stats refresh not working as a result of new ajax save.
* Fixed the phone field not saving to primary phone.
* Simplified the email editor text bar.
* Fixed extensions errors when licensing new extensions
* Fixed multisite config by retrieving options with get_blog_option rather than switch_to_blog
* Began Adding API Stuff.

= 0.9.17 =
* Fixed Apply owner step not saving.
* Fixed date timer select box for no reason.
* Added disable toggles to delay and date timers for testing
* Fixed Checkbox required="false" still required
* Fixed {_meta} replacement code showing as Array
* Changed form buttons adding to bottom of text
* Changed funnels save via AJAX instead of from POST

= 0.9.16 =
* Fixed global multisite bugs where restore current was never being called resulting in weird behaviour.

= 0.9.15 =
* Fixed activation fatal error.
* Added multisite functionality. Use the same info across all subsites in the event you have a multisite setup.

= 0.9.14 =
* Fixed text color picker from simple editor not working, again.
* Added filter to email blocks to make it easier to add custom blocks.

= 0.9.13 =
* Added simple editor to the form fill benchmark. Buttons now allow the user to quickly add standard fields to the forms without the need to view complete documentation.
* Fixed bug in form address field that caused a warning
* Fixed {_blah} replacement code not working because of unregistered code

= 0.9.12 / 0.9.11 =
* Updated bounce checker Library. Has a few bugs that were incompatible with PHP 7.0 +
* Fixed function name formlift_ to wpgh_ in locations.php
* Fixed meta not being saved via custom form fields
* Added security and copyright to all file headers
* Added .htaccess to templates/funnels
* Added email preferences funnel template
* Changed the form shortcode to be a text editor in the step itself, needs updated documentation.

= 0.9.10 =
* Fixed email preferences not working AT ALL, oops...
* Added last_optin to submission

= 0.9.9 =
* Finished implementing the Create User Action.
* Fixed bug where add from previous emails gives a warning.
* Tracking now uses the Logged in user as a contact if it exists so that if you're logged in replacement codes
among other functionality will work on the front end.
* Added reporting ranges to the active contacts column in funnel list

= 0.9.8 =
* Added Welcome Page
* Changed menu structure
* Added image assets
* Added address section to contact record.
* Added replacement codes {phone}, {phone_ext} & {address}
* Added locations functions to get lists of known geographic areas
* Fixed bug where clicking the confirmation link in tracking history resulted in a db error.

= 0.9.7 =
* Added send email function to contact record.
* Fixed Email Search Bug when searching in select2 email picker.
* New HTML Helper "dropdown_owners"
* Owner field now extended to include new roles "Marketer" & "Sales Manager"
* Fixed Saving Contact owner not working when adding new contact

= 0.9.6 =
* Fixed bug when deleting contacts with no tags caused a warning.
* Added emails send plaintext version as well as HTML for better spam score.
* Added alt tag to tracking image
* Added List-Unsubscribe header

= 0.9.5 =
* introduction of complex roles and caps
* Minor UI fixes regarding select2 library

= 0.9.4 =
* fixed {meta} not provided meta data
* fixed exporting funnel throwing errors
* added duplicate funnel link to table
* added empty trash link to emails table
* added filter to modify settings array

= 0.9.3 =
* Moved is recaptcha enabled check to functions.php
* fixed returning string instead of array when doing recaptcha check.
* get_tags() will now return an empty array instead of false.

= 0.9.2 =
* Add option to remove ALL data from WP when uninstalling Groundhogg
* Fixed gh_referer not being set when leadsource tracking

= 0.9.1 =
* Pre-release with some bug fixes

= 0.9 =
* Pre-release for testing installation from repo

= 0.1 =
* First Commit