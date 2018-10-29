=== Groundhogg ===
Contributors: trainingbusinesspros, groundhogg, dhrumit
Tags: marketing, email, contacts, contact, CRM, marketing automation, email automation, funnels, marketing funnels, marketing campaigns, campaigns
Donate link: https://groundhogg.io
Requires at least: 4.9
Tested up to: 4.9.8
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
* Whenever a from is filled out.
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
Yes, you can find our Official [Woocomerce](https://groundhogg.io/downloads/woocommerce) extension on our site among other extensions for other popular plugins.

= Is it secure? =
We follow all best WordPress security practices. Plus it's open source, so we have a large community looking out for potential weaknesses when it comes to your data.

= Is it GDPR, CANSPAM, or CASL compliant? =
Yes, we have tools available to meet the criteria of all the worlds ANTI-SPAM & privacy legislation.

== Changelog ==

= 0.9.17 =
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
* Pre-release for testing instalation from repo

= 0.1 =
* First Commit