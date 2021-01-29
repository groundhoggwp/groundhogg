import { getChartType, registerReportsPanel } from "data/reports-registry";
import Grid from "@material-ui/core/Grid";
import { Box } from "@material-ui/core";
import { LineChart } from "components/layout/pages/reporting/charts/line-chart";
import { QuickStat } from "components/layout/pages/reporting/charts/quick-stat";
import { DonutChart } from "components/layout/pages/reporting/charts/donut-chart";
import { ReportTable } from "components/layout/pages/reporting/charts/report-table";
import ContactMailIcon from "@material-ui/icons/ContactMail";

registerReportsPanel("overview", {
  name: "Overview",
  reports: [
    "total_new_contacts",
    "total_confirmed_contacts",
    "total_engaged_contacts",
    "total_unsubscribed_contacts",
    "chart_new_contacts",
    "chart_contacts_by_optin_status",
    "table_top_performing_emails",
  ],
  layout: ({ reports, isLoading }) => {
    const {
      total_new_contacts,
      total_confirmed_contacts,
      total_engaged_contacts,
      total_unsubscribed_contacts,
      chart_new_contacts,
      chart_contacts_by_optin_status,
      table_top_performing_emails,
    } = reports;

    let chart_contacts_by_optin_status_dummy = {
      type: "chart",
      title: "TODO IF REQUIRED PLEASE LET ME KNOW",
      chart: {
        type: "doughnut",
        data: {
          labels: [
            "Unconfirmed",
            "Unconfirmed",
            "Unconfirmed",
            "Unconfirmed",
            "Unconfirmed",
          ],
          datasets: [
            {
              data: [366, 1888, 86, 19, 1],
              backgroundColor: [
                "#F18F01",
                "#006E90",
                "#99C24D",
                "#F46036",
                "#41BBD9",
              ],
            },
          ],
        },
        options: {
          maintainAspectRatio: false,
          legend: {
            display: false,
          },
          responsive: false,
          tooltips: {
            backgroundColor: "#FFF",
            bodyFontColor: "#000",
            borderColor: "#727272",
            borderWidth: 2,
            titleFontColor: "#000",
          },
        },
        no_data: "No information available.",
      },
    };
    let table_dummy = {
      type: "table",
      chart: {
        type: "table",
        label: ["Emails", "Sent", "Open Rate", "Click Thru Rate"],
        data: [
          {
            label: "Abandon Cart - First Reminder",
            sent: "53",
            opened: "45.28%",
            clicked: "12.5%",
            email: {
              ID: 184,
              data: {
                content:
                  'Hey {first},\r\n\r\nWe just noticed that you left your cart behind?\r\n\r\nYour cart:\r\n{edd_cart_contents}\r\n\r\nWe know that can happen for a variety of reasons. Perhaps...\r\n<ul>\r\n \t<li>you got distracted...</li>\r\n \t<li>you had second thoughts...</li>\r\n \t<li>your mom called...</li>\r\n</ul>\r\nWe get it.\r\n\r\nSo, consider this a gentle nudge to let you know we still have your cart ready to go!\r\n\r\nAnd, if you checkout within the <em>next 48 hours</em> we\'ll even give you <strong>15% OFF!</strong>\r\n\r\n<a href="http://localhost/wp/secure/checkout/?discount=ONETIMESAVE15"><img class="alignnone wp-image-26645" src="http://localhost/wp/wp-content/uploads/2019/11/email-checkout-15-off.png" alt="" width="373" height="207" /></a>\r\n\r\nUse code\u00a0<strong>ONETIMESAVE15\u00a0</strong>to receive <strong>15% OFF</strong>\r\n\r\nCheckout Now &gt;&gt; {edd_restore_cart_url}\r\n\r\nIf you need any help feel free to reply to this email.\r\n\r\nCheers,\r\n\r\n@ The Groundhogg Team',
                subject: "Did you leave your cart behind?",
                pre_header: "We saved it for you.",
                from_user: "2",
                author: "1",
                last_updated: "2019-11-19 14:00:57",
                date_created: "2018-11-08 09:49:03",
                status: "ready",
                is_template: "0",
                title: "Abandon Cart - First Reminder",
              },
              meta: {
                alignment: "left",
                browser_view: "",
                test_email: "3",
                reply_to_override: "",
              },
              url: "http://localhost/wp/gh/emails/184/",
            },
          },
          {
            label: "[Onboarding] Improve your email deliverability!",
            sent: "29",
            opened: "34.48%",
            clicked: "40%",
            email: {
              ID: 324,
              data: {
                content:
                  '<div class="row"> <div class="content-wrapper image_block"> <div class="content-inside inner-content text-content" style="padding: 5px;"> <div class="image-wrapper" style="text-align: center"><a href=""><img src="http://localhost/wp/wp-content/uploads/2019/05/Groundhogg_logox300-with-tm.png" style="max-width: 100%;width: 50%;" title="Groundhogg_logox300-with-tm" alt=""></a></div> </div> </div></div><div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" spellcheck="false"> <p style="text-align: left;">Hey {first}!</p> <p style="text-align: left;">Are you sending email directly from your server? If you are, you should stop <b>RIGHT NOW.</b></p><p style="text-align: left;">Sending email from your server can cause all sorts of problems and email clients like <i>Google, Yahoo&nbsp;</i>and <i>Outlook </i>might leave your emails <b>out in the cold! </b>(or the spam folder <i>:O</i>)</p> </div> </div></div><div class="row" data-block="image"> <div class="content-wrapper image_block"> <div class="content-inside inner-content text-content" style="padding: 5px;"> <div class="image-wrapper" style="text-align: center"><a href=""><img width="543" src="https://media.giphy.com/media/g2YvIlpgTMlck/giphy.gif" style="max-width: 100%; width: 96%;" title="" alt=""></a></div> <p style="text-align:center;">Scene from the movie Groundhog Day (1993) starring Bill Murray</p> </div> </div></div><div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" spellcheck="false"> <p style="text-align: left;">You <b>should</b>&nbsp;be sending your emails from an external email sending service! But don\'t worry, we made it super easy.</p><p style="text-align: left;">You can use the <b>Groundhogg Sending Service, </b>our managed email service designed to get your critical emails to the inbox.</p> </div> </div></div><div class="row" data-block="button" style=""> <div class="content-wrapper button_block"> <div class="content-inside inner-content text-content" style="padding: 5px;"> <table width="100%" border="0" cellspacing="0" cellpadding="0"> <tbody> <tr> <td height="10"></td> </tr> <tr> <td align="center"> <table border="0" cellspacing="0" cellpadding="0" style="margin-right: auto;margin-left: auto;"> <tbody> <tr> <td class="email-button" bgcolor="#EB7035" style="padding: 12px 18px 12px 18px; border-radius:3px" align="center"><b><a href="http://localhost/wp/downloads/credits/" target="_blank" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; font-weight: bold; color: #ffffff; text-decoration: none !important; display: inline-block;">Send My Emails the Right Way!</a></b></td> </tr> </tbody> </table> </td> </tr> <tr> <td height="10"></td> </tr> </tbody> </table> </div> </div></div><div class="row" data-block="text"><div class="content-wrapper text_block"><div class="content-inside inner-content text-content" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;"><p>Oh, and it\'s not limited to just email, you can use our sending service to send <b>TEXT MESSAGES</b>&nbsp;as well.</p></div></div></div><div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;"> <p style="text-align: left;"><b style="font-family: inherit; font-size: inherit;">That\'s all for today!</b><br></p> <p style="text-align: left;"><i>@ The Groundhogg Team</i></p> </div> </div></div><div class="row" data-block="divider" style=""> <div class="content-wrapper divider_block"> <div class="content-inside inner-content text-content" style="padding: 5px;"> <table width="100%" cellpadding="0" cellspacing="0"> <tbody> <tr> <td class="divider"> <div style="margin: 5px 0 5px 0"> <hr style="width: 71%; border-top-color: rgb(145, 145, 145); border-top-width: 2px;"> </div> </td> </tr> </tbody> </table> </div> </div></div><div class="row" data-block="text"> <div class="content-wrapper text_block"> <div class="content-inside inner-content text-content" style="padding: 5px; font-family: Georgia, Times, &quot;Times New Roman&quot;, serif; font-size: 16px;"> <p style="text-align: center;"> <font color="#999999"><i>"{groundhogg_day_quote}" </i>- Groundhog Day (1993)</font> </p> </div> </div></div>',
                subject: "[Onboarding] Improve your email deliverability!",
                pre_header: "",
                from_user: "2",
                author: "1",
                last_updated: "2019-06-22 13:45:07",
                date_created: "2019-06-22 13:36:10",
                status: "ready",
                is_template: "0",
                title: "",
              },
              meta: {
                alignment: "left",
                browser_view: "",
              },
              url: "http://localhost/wp/gh/emails/324/",
            },
          },
          {
            label: "How about a review?",
            sent: "80",
            opened: "51.25%",
            clicked: "12.2%",
            email: {
              ID: 316,
              data: {
                content:
                  'Hey {first},\r\n\r\nWe just wanted to followup with you and make sure that you were successful with your most recent purchase...\r\n\r\n{edd_recent_order_items}\r\n\r\nIf you\'re enjoying the freedom of marketing from WordPress, would you mind taking the next two minutes to give us a review?\r\n\r\n<a href="http://localhost/wp/gh/link/click/1141/">Leave a review on WordPress.org</a>\r\n\r\n<a href="http://localhost/wp/gh/link/click/1142/">Leave a review on Facebook</a>\r\n\r\nReviews allow use to grow our customer base, which allows us to build more awesome products, which we then can give to you to help you grow\u00a0<strong>your\u00a0</strong>business.\r\n\r\nIf you have any questions, please feel free to give us a shout!\r\n\r\nThanks in advance!\r\n\r\n@ the {business_name} team',
                subject: "How about a review?",
                pre_header: "",
                from_user: "2",
                author: "1",
                last_updated: "2019-11-21 14:14:12",
                date_created: "2019-06-21 10:38:50",
                status: "ready",
                is_template: "0",
                title: "How about a review?",
              },
              meta: {
                alignment: "left",
                browser_view: "",
                test_email: "3",
                reply_to_override: "",
                is_plain: "1",
              },
              url: "http://localhost/wp/gh/emails/316/",
            },
          },
          {
            label: "[Onboarding] Your account has been created!",
            sent: "33",
            opened: "66.67%",
            clicked: "22.73%",
            email: {
              ID: 320,
              data: {
                content:
                  '\r\n                                <div class="row"> <div class="content-wrapper image_block"> <div class="content-inside inner-content text-content" style="padding: 5px"> <div class="image-wrapper" style="text-align: center"><a href=""><img src="http://localhost/wp/wp-content/uploads/2019/05/Groundhogg_logox300-with-tm.png" style="max-width: 100%;width: 50%" title="Groundhogg_logox300-with-tm" alt=""></a></div> </div> </div></div><div class="row"> <div class="content-wrapper text_block"> <div class="" style="padding: 5px;font-family: Arial, sans-serif;font-size: 16px"><p style="text-align: left">Congratulations {first}!</p><p style="text-align: left">You are officially part of the Groundhogg family!</p><p>To login and set your password look for an email with subject line, <em><strong>[Groundhogg\u2122] Login Details.</strong></em></p></div> </div></div><div class="row" data-block="image"> <div class="content-wrapper image_block"> <div class="content-inside inner-content text-content" style="padding: 5px"> <div class="image-wrapper" style="text-align: center"><a href=""><img width="543" src="https://media.giphy.com/media/xUOwGdD7RGT4CTnUaY/giphy.gif" style="max-width: 100%;width: 96%" title="" alt=""></a></div> <p style="text-align:center">Scene from the movie Groundhog Day (1993) starring Bill Murray</p> </div> </div></div><div class="row"> <div class="content-wrapper text_block"> <div class="" style="padding: 5px;font-family: Arial, sans-serif;font-size: 16px"> <p style="text-align: left"><span style="font-family: inherit;font-size: inherit">We look forward to serving you and helping you grow your business so that you can focus on improving the lives of your customers.</span><br></p> <p style="text-align: left">If you ever need help from us feel free to reach out to us by email and we\'ll point you in the right direction to get your answers.</p> <p style="text-align: left">Over the next week or so we\'ll be dripping you some tips and content that will show you around our community and products so you don\'t get lost.</p> <p style="text-align: left">But for today, it\'s more than enough that you just get started digging in and download our plugin!</p> </div> </div></div><div class="row" data-block="button"> <div class="content-wrapper button_block"> <div class="content-inside inner-content text-content" style="padding: 5px"> <table width="100%" border="0" cellspacing="0" cellpadding="0"> <tbody> <tr> <td height="10"></td> </tr> <tr> <td align="center"> <table border="0" cellspacing="0" cellpadding="0" style="margin-right: auto;margin-left: auto"> <tbody> <tr> <td class="email-button" bgcolor="#EB7035" style="padding: 12px 18px 12px 18px" align="center"><b><a href="http://localhost/wp/thank-you/" target="_blank" style="font-size: 16px;font-family: Helvetica, Arial, sans-serif;font-weight: bold;color: #ffffff;text-decoration: none !important;display: inline-block">Download &amp; Install Groundhogg</a></b></td> </tr> </tbody> </table> </td> </tr> <tr> <td height="10"></td> </tr> </tbody> </table> </div> </div></div><div class="row"> <div class="content-wrapper text_block"> <div class="" style="padding: 5px;font-family: Arial, sans-serif;font-size: 16px"> <p style="text-align: left">We\'ll reach out again tomorrow with some more amazing stuff for you to get started on!</p> <p style="text-align: left"><b>That\'s all for today!</b></p> <p style="text-align: left"><i>@ The Groundhogg Team</i></p> </div> </div></div><div class="row" data-block="divider"> <div class="content-wrapper divider_block"> <div class="content-inside inner-content text-content" style="padding: 5px"> <table width="100%" cellpadding="0" cellspacing="0"> <tbody> <tr> <td class="divider"> <div style="margin: 5px 0 5px 0"> <hr style="width: 71%;border-top-color: #919191;border-top-width: 2px"> </div> </td> </tr> </tbody> </table> </div> </div></div><div class="row" data-block="text"> <div class="content-wrapper text_block"> <div class="content-inside inner-content text-content" style="padding: 5px;, serif;font-size: 16px"> <p style="text-align: center"> <font color="#999999"><i>"{groundhogg_day_quote}" </i>- Groundhog Day (1993)</font> </p> </div> </div></div>                            ',
                subject: "[Onboarding] Your account has been created!",
                pre_header: "",
                from_user: "2",
                author: "1",
                last_updated: "2019-08-08 09:56:16",
                date_created: "2019-06-22 12:36:47",
                status: "ready",
                is_template: "0",
                title: "[Onboarding] Your account has been created!",
              },
              meta: {
                alignment: "left",
                browser_view: "",
                test_email: "3",
                reply_to_override: "",
              },
              url: "http://localhost/wp/gh/emails/320/",
            },
          },
          {
            label: "[Onboarding] Please confirm your email!",
            sent: "84",
            opened: "46.43%",
            clicked: "94.87%",
            email: {
              ID: 318,
              data: {
                content:
                  '<div class="row"> <div class="content-wrapper image_block"> <div class="content-inside inner-content text-content" style="padding: 5px;"> <div class="image-wrapper" style="text-align: center"><a href=""><img src="http://localhost/wp/wp-content/uploads/2019/05/Groundhogg_logox300-with-tm.png" style="max-width: 100%;width: 50%;" title="Groundhogg_logox300-with-tm" alt=""></a></div> </div> </div></div><div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;"> <p style="text-align: left;">Hey {first},</p> <p style="text-align: left;">Thanks for signing up for Groundhogg! We\'ll be sure to keep you in the loop about product updates, new extensions, special promotions and the like.</p> <p style="text-align: left;">But first, we need you to confirm your email address by clicking the link below.</p> <p style="text-align: left;">{confirmation_link}</p> <p style="text-align: left;">Once you confirm your email you\'ll receive a notification to setup your Groundhogg account password.</p> <p style="text-align: left;"><b style="font-family: inherit; font-size: inherit;">Talk Soon!</b><br></p> <p style="text-align: left;"><i>@ The Groundhogg Team</i></p> </div> </div></div>',
                subject: "[Onboarding] Please confirm your email!",
                pre_header: "Then you'll be ready to get started!",
                from_user: "2",
                author: "1",
                last_updated: "2019-06-22 12:30:41",
                date_created: "2019-06-22 12:29:26",
                status: "ready",
                is_template: "0",
                title: "",
              },
              meta: {
                alignment: "left",
                browser_view: "",
              },
              url: "http://localhost/wp/gh/emails/318/",
            },
          },
          {
            label: "[Onboarding] Documentation",
            sent: "307",
            opened: "31.6%",
            clicked: "17.53%",
            email: {
              ID: 450,
              data: {
                content:
                  'Hey again {first}!\r\n\r\nDid you know that we have extensive documentation on everything from "getting started"\u00a0to "Advanced funnel building"?\r\n\r\nIf not, you should really check it out because it\'ll help you avoid serious misteps...\r\n\r\nThe documentation is your friend! It has literally hundreds of articles, how to\'s, and feature instructions to ensure you have an easy breezy day!\r\n\r\n<a href="https://help.groundhogg.io">Learn advanced Groundhogg now!</a>\r\n\r\nIf there is documentation missing, just let us know and we\'ll write it up for you and notify you when it\'s live!\r\n\r\nThat\'s all for today!\r\n\r\nBest,\r\n\r\nAdrian',
                subject: "[Onboarding] The documentation is your friend!",
                pre_header: "",
                from_user: "2",
                author: "1",
                last_updated: "2020-01-15 10:41:09",
                date_created: "2020-01-14 15:38:11",
                status: "ready",
                is_template: "0",
                title: "[Onboarding] Documentation",
              },
              meta: {
                alignment: "left",
                browser_view: "",
                reply_to_override: "",
              },
              url: "http://localhost/wp/gh/emails/450/",
            },
          },
          {
            label: "[Onboarding] Community",
            sent: "314",
            opened: "35.35%",
            clicked: "10.81%",
            email: {
              ID: 447,
              data: {
                content:
                  'Hi {first},\r\n\r\nI\'m personally inviting you to join our thriving community of hundreds of business owners from around the world!\r\n\r\nOur community will support you on your quest for success, and always be there for you if you have questions, doubts, fears or whenever you need any kind of help.\r\n\r\nYou can be part of the community by:\r\n<ul>\r\n \t<li><a href="https://www.facebook.com/groups/groundhoggwp/">Joining the Facebook Group</a></li>\r\n \t<li><a href="https://www.facebook.com/groundhoggwp/">Liking the Facebook page</a></li>\r\n \t<li><a href="https://twitter.com/Groundhoggwp">Following us on Twitter</a></li>\r\n \t<li><a href="https://www.youtube.com/channel/UChHW8I3wPv-KUhQYX-eUp6g">Subscribing to our channel on YouTube</a></li>\r\n</ul>\r\nWelcome to the community!\r\n\r\nBest,\r\n\r\nAdrian',
                subject: "[Onboarding] Join our thriving community!",
                pre_header: "",
                from_user: "2",
                author: "1",
                last_updated: "2020-01-14 16:04:16",
                date_created: "2020-01-14 15:38:11",
                status: "ready",
                is_template: "0",
                title: "[Onboarding] Community",
              },
              meta: {
                alignment: "left",
                browser_view: "",
                reply_to_override: "",
              },
              url: "http://localhost/wp/gh/emails/447/",
            },
          },
          {
            label: "Cart Abandonment - Second Reminder",
            sent: "42",
            opened: "42.86%",
            clicked: "11.11%",
            email: {
              ID: 185,
              data: {
                content:
                  'Hey {first},\r\n\r\nDo you want to make use of your <strong>15% off discount</strong>?\r\n\r\nWe\'ve held on to your cart for you. But you have a limited window left to use your discount!\r\n\r\n<a href="http://localhost/wp/secure/checkout/?discount=ONETIMESAVE15"><img class="alignnone wp-image-26645" src="http://localhost/wp/wp-content/uploads/2019/11/email-checkout-15-off.png" alt="" width="373" height="207" /></a>\r\n\r\nUse code\u00a0<strong>ONETIMESAVE15</strong>\u00a0to receive <strong>15% OFF!</strong>\r\n\r\nCheckout Now &gt;&gt; {edd_restore_cart_url}\r\n\r\nCheers,\r\n\r\n@ The Groundhogg Team',
                subject: "Want to use your 15% off?",
                pre_header: "We've saved your cart!",
                from_user: "2",
                author: "1",
                last_updated: "2019-11-19 14:01:44",
                date_created: "2018-11-08 09:49:03",
                status: "ready",
                is_template: "0",
                title: "Cart Abandonment - Second Reminder",
              },
              meta: {
                alignment: "left",
                browser_view: "",
                test_email: "1",
                reply_to_override: "",
              },
              url: "http://localhost/wp/gh/emails/185/",
            },
          },
          {
            label: "Fall Back - Confirm Email",
            sent: "60",
            opened: "86.67%",
            clicked: "80.77%",
            email: {
              ID: 197,
              data: {
                content:
                  '<div class="row" data-block="image">\r\n<div class="content-wrapper image_block">\r\n<div class="content-inside inner-content text-content" style="padding: 5px">\r\n<div class="image-wrapper" style="text-align: center">\r\n<p style="text-align: left">Hey {first},</p>\r\n<p style="text-align: left">Looks like we have yet to confirm your email address.</p>\r\n<p style="text-align: left">Confirming you email address ensures that you\'ll receive important information about products you may have purchased, programs you may have signed up for and new company information.</p>\r\n<p style="text-align: left">{confirmation_link}</p>\r\n<p style="text-align: left">Once you click the link above you\'ll be all set!</p>\r\n<p style="text-align: left">Thanks!</p>\r\n<p style="text-align: left">@ the {business_name} team</p>\r\n\r\n</div>\r\n</div>\r\n</div>\r\n</div>',
                subject: "Please confirm your email!",
                pre_header: "",
                from_user: "2",
                author: "1",
                last_updated: "2019-11-03 15:55:07",
                date_created: "2018-11-15 12:48:47",
                status: "ready",
                is_template: "0",
                title: "Fall Back - Confirm Email",
              },
              meta: {
                alignment: "left",
                browser_view: "",
                reply_to_override: "",
              },
              url: "http://localhost/wp/gh/emails/197/",
            },
          },
          {
            label: "Upgrade your plan - first upgrade offer",
            sent: "24",
            opened: "75%",
            clicked: "11.11%",
            email: {
              ID: 410,
              data: {
                content:
                  "Hi {first}!\r\n\r\nWe just saw you purchased the {edd_last_purchase} plan which is awesome!\r\n\r\nBut, if you want to upgrade to one of our higher tier plans now is your chance.\r\n\r\nIf you upgrade to a higher tier plan in the next 2 hours, you can recieve an <strong>additional 10% off</strong> using the discount code <strong>UPGRADEONETIMEOFFER.</strong>\r\n\r\nYou can upgrade to:\r\n\r\n{edd_license_upgrade_options}\r\n\r\nIf you have any questions please reach out!\r\n\r\nSincerely,\r\n\r\nThe Groundhogg Team",
                subject:
                  "Upgrade in the next 2 hours and get an additional 10% off.",
                pre_header: "",
                from_user: "2",
                author: "1",
                last_updated: "2019-11-21 16:57:18",
                date_created: "2019-11-21 16:46:27",
                status: "ready",
                is_template: "0",
                title: "Upgrade your plan - first upgrade offer",
              },
              meta: {
                alignment: "left",
                browser_view: "",
                reply_to_override: "",
              },
              url: "http://localhost/wp/gh/emails/410/",
            },
          },
          {
            label: "Trial - Day 4",
            sent: "11",
            opened: "54.55%",
            clicked: "33.33%",
            email: {
              ID: 423,
              data: {
                content:
                  '<span style="font-weight: 400">{first},</span>\r\n\r\n<span style="font-weight: 400">Funnels make the world go round. (Well, technically it\u2019s gravity, but funnels are the most important feature of Groundhogg)</span>\r\n\r\n<span style="font-weight: 400">A funnel is what you will use to turn prospects into leads, and leads into more qualified leads, and finally those qualified leads into customers.</span>\r\n\r\n<span style="font-weight: 400">In fact, you are going through our </span><b>onboarding funnel</b><span style="font-weight: 400"> right now, further qualifying you till you make a positive purchasing decision.</span>\r\n\r\n<span style="font-weight: 400">You can use funnels for all sorts of different use cases. Like...</span>\r\n<ul>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">Requesting a quote</span></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">Cart abandonment</span></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">Delivering a lead magnet</span></li>\r\n</ul>\r\n<span style="font-weight: 400">I\u2019m going to walk you through how to create a </span><b>lead magnet download </b><span style="font-weight: 400">funnel. This funnel will allow you to\u2026</span>\r\n<ol>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">Collect first, last and email address.</span></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">Double optin the contact.</span></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">Deliver a lead magnet and track if they download it.</span></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">Push them to a product or consultation.</span></li>\r\n</ol>\r\n<span style="font-weight: 400">This will only take </span><b>15 minutes.</b>\r\n\r\n<span style="font-weight: 400">Login to your demo site</span>\r\n\r\n<span style="font-weight: 400">{trial_login}</span>\r\n\r\n<span style="font-weight: 400">And watch this tutorial on </span><a href="https://youtu.be/W1dwQrqEPVw"><b>\u201cHow to setup your first lead magnet download funnel.\u201d</b></a>\r\n\r\n<span style="font-weight: 400">Got questions? Just ask!</span>\r\n\r\n<span style="font-weight: 400">Adrian</span>',
                subject:
                  "\u2692\ufe0f {first}, It\u2019s time to build your first funnel.",
                pre_header: "",
                from_user: "2",
                author: "0",
                last_updated: "2019-12-02 15:34:33",
                date_created: "2019-12-02 12:02:23",
                status: "ready",
                is_template: "0",
                title: "Trial - Day 4",
              },
              meta: {
                alignment: "left",
                browser_view: "",
                reply_to_override: "",
              },
              url: "http://localhost/wp/gh/emails/423/",
            },
          },
          {
            label: "Trial - Day 1",
            sent: "11",
            opened: "100%",
            clicked: "90.91%",
            email: {
              ID: 420,
              data: {
                content:
                  '<span style="font-weight: 400">Hi {first}! This is Adrian,</span>\r\n\r\n<span style="font-weight: 400">I\'m the CEO &amp; Founder of Groundhogg. I wanted to "officially" welcome you to the Groundhogg community!</span>\r\n\r\nTo log into your demo site...\r\n\r\n<span style="font-weight: 400">{trial_login}</span>\r\n\r\n<span style="font-weight: 400">Is it okay if I tell you a bit about myself? Thanks :)</span>\r\n\r\n<span style="font-weight: 400">I used to work in a digital marketing agency, I did implementation for 100s of clients in several high profile CRMs and Marketing Automation tools.</span>\r\n\r\n<span style="font-weight: 400">Being an agency was tough. Working in an agency, you are under constant pressure to deliver value, and sometimes the tools we use can make it super difficult to do that in a timely and cost effective fashion.</span>\r\n\r\n<span style="font-weight: 400">Maybe you\u2019ve got the system figured out, but I did not.</span>\r\n\r\n<span style="font-weight: 400">We were contract to contract, working long hours and even longer weeks.</span>\r\n\r\n<span style="font-weight: 400">The biggest pain point was that for every client we essentially had to rebuild everything from scratch because the tools we were using did not make it easy to re-use the same strategies over and over again.</span>\r\n\r\n<span style="font-weight: 400">I resolved to make it easier. To allow not only our agency, but all agencies to use a tool that supported them in the creation of repeatable processes that resulted in client wins.</span>\r\n\r\n<span style="font-weight: 400">Groundhogg eases the pressure of delivering value by making it easier to implement and re-use your proven strategies in a cost effective and time sensitive way.</span>\r\n\r\n<span style="font-weight: 400">But you don\u2019t need to take my word for it, see for yourself!</span>\r\n\r\n<span style="font-weight: 400">We have just created your demo site. You can use this site to test the ins-and-outs of Groundhogg.</span>\r\n\r\n<span style="font-weight: 400">{trial_login}</span>\r\n\r\n<span style="font-weight: 400">This site is </span><b>100% yours</b><span style="font-weight: 400"> to play in and explore.</span>\r\n\r\n<span style="font-weight: 400">You can use this demo site to...</span>\r\n<ul>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">Create funnels</span></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">Create emails</span></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">Send email (to yourself)</span></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">Test the automation</span></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">Test our extensions</span></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">Import some contacts</span></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">And more!</span></li>\r\n</ul>\r\n<span style="font-weight: 400">You have 14 days to use this demo site and see if Groundhogg is a fit for your agency.</span>\r\n\r\n<span style="font-weight: 400">If within 14 days you have not upgraded to a premium plan, your demo site will be locked and you will no longer be able to access it.</span>\r\n\r\n<span style="font-weight: 400">If you do purchase, you\u00a0 will be able to migrate any content you created on the demo site to your new site.</span>\r\n\r\n<span style="font-weight: 400">If you ever have any questions, feel free to reply to this email.</span>\r\n\r\n<span style="font-weight: 400">Have fun!</span>\r\n\r\n<span style="font-weight: 400">Adrian, CEO</span>\r\n\r\n<i><span style="font-weight: 400">P.S. I will do my best to be your guide over the next 30 days and make sure you get the most out of your trial. If you feel like you are missing a crucial piece of information, let me know and I\'ll make sure you get the answer you need.</span></i>',
                subject:
                  "\ud83d\udea6 Your trial has begun {first}! Here\u2019s how you get started with Groundhogg...",
                pre_header: "",
                from_user: "2",
                author: "0",
                last_updated: "2019-12-04 10:34:37",
                date_created: "2019-11-28 09:13:05",
                status: "ready",
                is_template: "0",
                title: "Trial - Day 1",
              },
              meta: {
                alignment: "left",
                browser_view: "",
                reply_to_override: "",
              },
              url: "http://localhost/wp/gh/emails/420/",
            },
          },
          {
            label: "Partner Program - Still Interested",
            sent: "23",
            opened: "52.17%",
            clicked: "25%",
            email: {
              ID: 336,
              data: {
                content:
                  'Hi {first},\r\n\r\nYou have yet to register for the partner certification, are you still interested in joining?\r\n\r\nIf you are having second thoughts, we can set up a meeting to discuss whether or not this program is right for you.\r\n\r\n<a href="https://calendly.com/groundhoggcp/15min">Book a 15 minute meeting with our Partner Success manager, Nancy.</a>\r\n\r\nIf you\'ve simply lost track of time and want to register, then go ahead and take care of this today.\r\n\r\n<strong><a href="http://localhost/wp/downloads/partner-certification-program/">Register For Certification Now!</a></strong>\r\n\r\nWe look forward to having you as part of our partner community.\r\n\r\nBest,\r\n\r\nAdrian, CEO',
                subject:
                  "Re: Partner certification - Are you still interested?",
                pre_header: "",
                from_user: "2",
                author: "1",
                last_updated: "2020-02-07 11:28:49",
                date_created: "2019-07-12 08:21:20",
                status: "ready",
                is_template: "0",
                title: "Partner Program - Still Interested",
              },
              meta: {
                alignment: "left",
                browser_view: "",
                reply_to_override: "",
              },
              url: "http://localhost/wp/gh/emails/336/",
            },
          },
          {
            label: "We received your question.",
            sent: "29",
            opened: "65.52%",
            clicked: "15.79%",
            email: {
              ID: 65,
              data: {
                content:
                  'Hey {first},\r\n<p style="text-align: left">Just letting you know we received your question.</p>\r\n<p style="text-align: left">Please note that if your question was support related it will be ignored. However, if you post your question in one of these places we\'re more than happy to help.</p>\r\n\r\n<ul>\r\n \t<li><a href="https://www.facebook.com/groups/274900800010203/">Facebook</a></li>\r\n \t<li><a href="https://github.com/tobeyadr/Groundhogg">Github</a></li>\r\n \t<li><a href="https://wordpress.org/plugins/groundhogg/">WordPress.org</a></li>\r\n \t<li><a href="http://localhost/wp/account/">Your Account</a></li>\r\n</ul>\r\nOtherwise you should hear from us very soon!\r\n<p style="text-align: left">Is your question urgent? Give us a call @ {business_phone} and speed up the process!</p>\r\n<p style="text-align: left">If you want, you can add yourself to our mailing list by confirming your email address!</p>\r\n<p style="text-align: left">{confirmation_link}</p>\r\n<p style="text-align: left"><b>Talk soon!</b></p>\r\n<p style="text-align: left"><i>@ the {business_name} team</i></p>',
                subject: "We received your question.",
                pre_header: "",
                from_user: "2",
                author: "1",
                last_updated: "2019-10-18 13:20:35",
                date_created: "2018-09-05 16:45:23",
                status: "ready",
                is_template: "0",
                title: "We received your question.",
              },
              meta: {
                alignment: "left",
                test_email: "3",
                browser_view: "",
                reply_to_override: "help@localhost",
                is_plain: "1",
              },
              url: "http://localhost/wp/gh/emails/65/",
            },
          },
          {
            label: "Partner Program - Application Approved",
            sent: "26",
            opened: "65.38%",
            clicked: "82.35%",
            email: {
              ID: 335,
              data: {
                content:
                  'Hi {first},\r\n\r\nWe\'re happy to announce that you have been approved to join our partner certification program. Congratulations!\r\n\r\n<a href="http://localhost/wp/downloads/partner-certification-program/">Register For Certification Now!</a>\r\n\r\nRegistration is limited, and we have over 50 people on the wait list at any given time, so act fast if you want to reserve your status.\r\n\r\nWe look forward to having you as part of our partner community.\r\n\r\nBest,\r\n\r\nAdrian, CEO',
                subject: "Congratulations, your application is approved!",
                pre_header: "Welcome to the partner certification program.",
                from_user: "2",
                author: "1",
                last_updated: "2020-01-28 12:10:26",
                date_created: "2019-07-11 16:50:21",
                status: "ready",
                is_template: "0",
                title: "Partner Program - Application Approved",
              },
              meta: {
                alignment: "left",
                browser_view: "",
                test_email: "3",
                reply_to_override: "",
              },
              url: "http://localhost/wp/gh/emails/335/",
            },
          },
          {
            label: "Partner Program - Book with Nancy",
            sent: "26",
            opened: "88.46%",
            clicked: "60.87%",
            email: {
              ID: 311,
              data: {
                content:
                  "Hey {first},\r\n\r\nThank you for your interest in the Certified Partner Program! We would be overjoyed to have you be part of our tight-knit partner community.\r\n\r\nBefore you register, we'd love the opportunity to chat with you and review your application! you can also use this chat as an opportunity to ask any questions you have about the program.\r\n\r\nYou'll be speaking with our partner manager Nancy, she is in charge of ensuring the success of our partners.\r\n\r\n<a href=\"https://calendly.com/groundhoggcp/15min\">Book your on-boarding call now!</a>\r\n\r\nTalk soon,\r\n\r\nAdrian, CEO",
                subject: "Re: Your partner certification application...",
                pre_header: "Thanks for your interest",
                from_user: "2",
                author: "1",
                last_updated: "2020-02-07 11:27:28",
                date_created: "2019-06-07 16:02:47",
                status: "ready",
                is_template: "0",
                title: "Partner Program - Book with Nancy",
              },
              meta: {
                alignment: "left",
                browser_view: "",
                test_email: "3",
                reply_to_override: "",
              },
              url: "http://localhost/wp/gh/emails/311/",
            },
          },
          {
            label: "5 Ways to Sell - 4. Tutorial",
            sent: "17",
            opened: "41.18%",
            clicked: "14.29%",
            email: {
              ID: 438,
              data: {
                content:
                  '<span style="font-weight: 400">Hey {first}!</span>\r\n\r\n<span style="font-weight: 400">Is video your thing? Video is a super powerful marketing to that you can use to sell not only Groundhogg, but any other products you are an affiliate for.</span>\r\n\r\n<span style="font-weight: 400">Here are some easy tutorial ideas.</span>\r\n<ul>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">\u201cHow to create a lead magnet funnel\u201d</span></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">\u201cHow to create a cart abandonment funnel\u201d</span></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">\u201cHow to create a webinar funnel\u201d</span></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">\u201cHow to create a ___________ funnel\u201d</span></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">\u201cHow to integrate Groundhogg with ________ plugin\u201d</span></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">\u201cHow to use Groundhogg to _______\u201d</span></li>\r\n</ul>\r\n<span style="font-weight: 400">Here\u2019s how you can generate referrals by recording a video tutorial.</span>\r\n<ol>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">Choose one (or more) tutorials to record. Sit down at your computer and spend anywhere from 30 minutes to 1 hour getting a good take.</span></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">Upload your tutorial to YouTube with your affiliate link in the description.</span></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">Share your tutorial on social media!</span></li>\r\n</ol>\r\n<span style="font-weight: 400">If you need some inspiration, why not have a look at these WordPress YouTube content creators and how they promote different products through tutorials.</span>\r\n<ul>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">Adam Preiser: </span><a href="https://www.youtube.com/user/adampreiser"><span style="font-weight: 400">https://www.youtube.com/user/adampreiser</span></a></li>\r\n \t<li style="font-weight: 400"><span style="font-weight: 400">John Whitford: </span><a href="https://www.youtube.com/channel/UCpNocIfIr4sLualjlFv-XRQ"><span style="font-weight: 400">https://www.youtube.com/channel/UCpNocIfIr4sLualjlFv-XRQ</span></a></li>\r\n</ul>\r\n<span style="font-weight: 400">Good luck!</span>\r\n\r\n<span style="font-weight: 400">Adrian, CEO</span>',
                subject: "[5 Ways to Sell Groundhogg] 4. Record a tutorial.",
                pre_header: "",
                from_user: "2",
                author: "1",
                last_updated: "2019-12-10 13:12:43",
                date_created: "2019-12-10 13:11:28",
                status: "ready",
                is_template: "0",
                title: "5 Ways to Sell - 4. Tutorial",
              },
              meta: {
                alignment: "left",
                browser_view: "",
                reply_to_override: "",
              },
              url: "http://localhost/wp/gh/emails/438/",
            },
          },
          {
            label: "5 Ways to Sell - 3. review",
            sent: "19",
            opened: "36.84%",
            clicked: "14.29%",
            email: {
              ID: 437,
              data: {
                content:
                  '<span style="font-weight: 400">Hey {first}!</span>\r\n\r\n<span style="font-weight: 400">We got the </span><b>easy</b><span style="font-weight: 400"> methods of generating referrals out of the way. Now let\u2019s talk about some more </span><b>involved </b><span style="font-weight: 400">(but more lucrative) methods.</span>\r\n\r\n<span style="font-weight: 400">Writing a long form review is an evergreen way to generate referrals. Sending an email and sharing to social media is a 1-time thing. But writing a long form review only gets better with time.</span>\r\n\r\n<span style="font-weight: 400">If you have no idea what a long form review is, don\u2019t worry!</span>\r\n\r\n<a href="http://localhost/wp/wp-content/uploads/2019/12/5-Ways-to-Sell_-Write-a-Long-Form-Review.pdf"><span style="font-weight: 400">Here\'s how to write a long form review.</span></a>\r\n\r\n<span style="font-weight: 400">If you have any questions or need anything, just ask!</span>\r\n\r\n<span style="font-weight: 400">Good luck,</span>\r\n\r\n<span style="font-weight: 400">Adrian, CEO</span>',
                subject:
                  "[5 Ways to Sell Groundhogg] 3. Write a long form review.",
                pre_header: "",
                from_user: "2",
                author: "1",
                last_updated: "2019-12-10 13:11:20",
                date_created: "2019-12-10 12:55:56",
                status: "ready",
                is_template: "0",
                title: "5 Ways to Sell - 3. review",
              },
              meta: {
                alignment: "left",
                browser_view: "",
                reply_to_override: "",
              },
              url: "http://localhost/wp/gh/emails/437/",
            },
          },
          {
            label: "5 Ways to Sell - 2. Social",
            sent: "19",
            opened: "52.63%",
            clicked: "20%",
            email: {
              ID: 436,
              data: {
                content:
                  '<span style="font-weight: 400">Hey {first},</span>\r\n\r\n<span style="font-weight: 400">The next lowest barrier tactic to earn some easy referrals is to share your link on your social media channels. Facebook, Twitter, LinkedIn etc\u2026</span>\r\n\r\n<span style="font-weight: 400">Click a social channel below to share your affiliate link!</span>\r\n<ul>\r\n \t<li style="font-weight: 400"><a href="https://www.facebook.com/sharer/sharer.php?u={affiliate_url}"><span style="font-weight: 400">Facebook</span></a></li>\r\n \t<li style="font-weight: 400"><a href="https://twitter.com/home?status={affiliate_url}%20I%20recently%20discovered%20Groundhogg%20and%20it\'s%20pretty%20amazing.%20I%20used%20it%20to%20automate%20my%20customer%20onboarding%20and%20the%20improvement%20has%20been%20incredible."><span style="font-weight: 400">Twitter</span></a></li>\r\n \t<li style="font-weight: 400"><a href="https://pinterest.com/pin/create/button/?url={affiliate_url}&amp;media=&amp;description=I%20recently%20discovered%20Groundhogg%20and%20it\'s%20pretty%20amazing.%20I%20used%20it%20to%20automate%20my%20customer%20onboarding%20and%20the%20improvement%20has%20been%20incredible."><span style="font-weight: 400">Pinterest</span></a></li>\r\n \t<li style="font-weight: 400"><a href="https://www.linkedin.com/shareArticle?mini=true&amp;url={affiliate_url}&amp;title=&amp;summary=I%20recently%20discovered%20Groundhogg%20and%20it\'s%20pretty%20amazing.%20I%20used%20it%20to%20automate%20my%20customer%20onboarding%20and%20the%20improvement%20has%20been%20incredible.&amp;source="><span style="font-weight: 400">LinkedIn</span></a></li>\r\n</ul>\r\n<span style="font-weight: 400">Good luck!</span>\r\n\r\n<span style="font-weight: 400">Adrian, CEO</span>',
                subject:
                  "[5 Ways to Sell Groundhogg] 2. Share your link on your social channels (Facebook, Twitter, LinkedIn)!",
                pre_header: "",
                from_user: "2",
                author: "1",
                last_updated: "2019-12-10 12:47:34",
                date_created: "2019-12-10 12:41:16",
                status: "ready",
                is_template: "0",
                title: "5 Ways to Sell - 2. Social",
              },
              meta: {
                alignment: "left",
                browser_view: "",
                reply_to_override: "",
              },
              url: "http://localhost/wp/gh/emails/436/",
            },
          },
          {
            label: "5 Ways to Sell - 1. Email",
            sent: "19",
            opened: "52.63%",
            clicked: "60%",
            email: {
              ID: 435,
              data: {
                content:
                  'Hi {first}!\r\n\r\nOne of the easiest ways to start generating referrals is to let your audience know about Groundhogg via email.\r\n\r\nHowever, there is a formula for sending the email.\r\n\r\n<a href="http://localhost/wp/wp-content/uploads/2019/12/5-Ways-to-Sell_-Email-your-list.pdf"><strong>What\u2019s the formula?</strong></a>\r\n\r\nSticking to the formula will yield much better results than randomly writing content and sending it to your whole list.\r\n\r\nIf you have any questions about this guide do not hesitate to reach out!\r\n\r\nGood luck!\r\n\r\nAdrian, CEO',
                subject:
                  "[5 Ways to Sell Groundhogg] 1. Send an email to your list!",
                pre_header: "",
                from_user: "2",
                author: "1",
                last_updated: "2019-12-10 12:40:35",
                date_created: "2019-12-10 12:40:35",
                status: "ready",
                is_template: "0",
                title: "5 Ways to Sell - 1. Email",
              },
              meta: {
                alignment: "left",
                browser_view: "",
                reply_to_override: "",
              },
              url: "http://localhost/wp/gh/emails/435/",
            },
          },
          {
            label: "Affiliates - Welcome email",
            sent: "17",
            opened: "88.24%",
            clicked: "40%",
            email: {
              ID: 433,
              data: {
                content:
                  '<span style="font-weight: 400">Hi {first}!</span>\r\n\r\n<span style="font-weight: 400">Adrian (CEO) here, I wanted to personally welcome you to the Affiliate Partner Program! I really hope that we\u2019re able to help your audience succeed and I promise that we\u2019ll take good care of any referrals you send us.</span>\r\n\r\n[gh_does_not_have_tags tags="137,136,135,134,139,97"]\r\n\r\nNot all affiliates who sign up are super familiar with our product. So if you have yet to actually use Groundhogg you should before you start recommending it to your audience.\r\n\r\n<span style="font-weight: 400">Sign up for the demo here &gt;&gt; </span><a href="https://groundho.gg/try/"><span style="font-weight: 400">https://groundho.gg/try/</span></a>\r\n\r\n<span style="font-weight: 400">It\u2019s important to us that you understand the value of our products if you\u2019re going to be selling them.</span>\r\n\r\n[/gh_does_not_have_tags]\r\n\r\n<span style="font-weight: 400">To help you get started with selling Groundhogg we\u2019ve come up with 5 easy to implement strategies to help you start engaging your audience and generating referrals..</span>\r\n\r\n<a href="http://localhost/wp/affiliate-area/sell/"><span style="font-weight: 400">Get the 5 easy ways to sell Groundhogg</span></a>\r\n\r\n<span style="font-weight: 400">If you have any questions about the guides please reach out and we\u2019ll be happy to help!</span>\r\n\r\n<span style="font-weight: 400">Best of luck,</span>\r\n\r\n<span style="font-weight: 400">Adrian, CEO</span>',
                subject: "ACTION REQUIRED: Welcome to the Affiliate Program!",
                pre_header: "",
                from_user: "2",
                author: "1",
                last_updated: "2019-12-10 12:36:50",
                date_created: "2019-12-10 09:07:06",
                status: "ready",
                is_template: "0",
                title: "Affiliates - Welcome email",
              },
              meta: {
                alignment: "left",
                browser_view: "",
                reply_to_override: "",
              },
              url: "http://localhost/wp/gh/emails/433/",
            },
          },
        ],
      },
      no_data: "No information available.",
    };

    return (
      <Box flexGrow={1}>
        <Grid container spacing={3}>
          <Grid item xs={3}>
            <QuickStat
              title={"Total New Contacts"}
              i
              id={"total_new_contacts"}
              data={!isLoading ? total_new_contacts : {}}
              loading={isLoading}
              icon={<ContactMailIcon />}
            />
          </Grid>
          <Grid item xs={3}>
            <QuickStat
              title={"Total New Contacts"}
              i
              id={"total_confirmed_contacts"}
              data={!isLoading ? total_confirmed_contacts : {}}
              loading={isLoading}
              icon={<ContactMailIcon />}
            />
          </Grid>
          <Grid item xs={3}>
            <QuickStat
              title={"Total New Contacts"}
              id={"total_engaged_contacts"}
              data={!isLoading ? total_engaged_contacts : {}}
              loading={isLoading}
              icon={<ContactMailIcon />}
            />
          </Grid>
          <Grid item xs={3}>
            <QuickStat
              title={"Total New Contacts"}
              id={"total_unsubscribed_contacts"}
              data={!isLoading ? total_unsubscribed_contacts : {}}
              loading={isLoading}
              icon={<ContactMailIcon />}
            />
          </Grid>
          {/*<Grid item xs={12}>*/}
          {/*  <LineChart*/}
          {/*    title={'New Contacts'}*/}
          {/*    id={'chart_new_contacts'}*/}
          {/*    data={!isLoading ? chart_new_contacts : {}}*/}
          {/*    loading={isLoading}*/}
          {/*  />*/}
          {/*</Grid>*/}

          <Grid item xs={12}>
            <DonutChart
              title={"Donut Chart"}
              id={"chart_contacts_by_optin_status"}
              data={chart_contacts_by_optin_status_dummy}
              loading={isLoading}
            />
          </Grid>
          <Grid item xs={12}>
            <ReportTable
              title={"Table chart"}
              id={"table_top_performing_emails"}
              data={table_dummy}
              loading={isLoading}
            />
          </Grid>
        </Grid>
      </Box>
    );
  },
});
