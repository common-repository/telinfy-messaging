=== Telinfy Messaging ===
Contributors: telinfymessaging
Tags: Telinfy, Telinfy messaging, WhatsApp , Sms, Rcs, abandoned cart messages, Telinfy Sms Rcs WhatsApp Alert
Requires at least: 5.0.0
Tested up to: 6.4
WC tested up to: 8.3
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Stable tag: 1.1.0
Author: GreenAds Global
Author URI: https://www.greenadsglobal.com/
This is a plugin WooCommerce plugin that helps you to integrate messaging services like WhatsApp, SMS, and RCS with Telinfy Messaging services 


== Description ==

What is WooCommerce Telinfy Messaging Plugin? The WooCommerce Telinfy Messaging Plugin is a WooCommerce plugin that helps you to integrate messaging services like WhatsApp, SMS, and RCS with Telinfy Messaging services. It will send messages to the customers on placing an order, shipping an order, cancelling an order, refunding an order, changing the status of the order, adding customer notes for the orders and abandoned cart notifications.

This is a woocommerce plugin for the Telinfy Messaging services. Please click the below links to purchase the plans or contact us at **info@greenadsglobal.com**

**WhatsApp**: https://www.greenadsglobal.com/whatsapp-business-api-pricing/
**SMS**: https://www.greenadsglobal.com/sms-pricing/
**RCS**: https://www.greenadsglobal.com/rcs-messaging/

This plugin relies on a third-party cloud service to send WhatsApp messages and SMS messages to the customers. We are using the API services of the provider "https://www.greenadsglobal.com/" to send messages.

To send WhatsApp messages we are sending required data to "https://api.telinfy.net/" and to send sms messages we are sending data to "http://sapteleservices.com/SMS_API/sendsms.php". These are the services of the provider "https://www.greenadsglobal.com/"

Please find the privacy policy of the provider https://www.greenadsglobal.com/privacy-policy/?swcfpc=1

### IMPORTANT NOTICE:

- The plugin works based on WooCommerce plugin.

### FEATURES:

 The customers will be notified by different methods such as WhatsApp, SMS, and RCS for all the order events such as, 
	- Order Confirmation
	- Order Shipment
	- Order Cancellation
	- Order Refund
	- Order Status Change
	- Adding Customer Notes 
	- Abandoned Cart Notifications

We can restrict and allow messages send at each event for all methods.

-**Order Confirmation** : This event will trigger when we place an order in the WooCommerce store.

-**Order Shipment** : This event will trigger when the order is shipped. In other words, the status is changed from “processing” to “completed”.

-**Order Cancellation** : This event will trigger when the order is cancelled. In other words, the status of the order is changed to “cancelled”

-**Order Refund** :This event will trigger when the admin will refund the order partially or fully

-**Order Status Change** :This event will trigger when the admin changes the status of the order to any other status except completed, cancelled, refunded etc. To send messages in this event we have to tick "Notify Customer" check box

-**Adding Customer Notes** : This event will trigger when the admin user adds a “Note to customer” from the order details page in the admin

-**Abandoned Cart Notifications** : This will be sent notification messages to the customer who left the cart without completing the order


### How to Configure?

To configure the plugin on the admin page go to **WooCommerce -> Settings -> Telinfy Messaging**. see the screenshot below


##WhatsApp Messaging

The WooCommerce Telinfy Messaging Plugin allows you to connect with customers on the WooCommerce platform. You can send WhatsApp messages for all the events that are mentioned above.  The WhatsApp message consists of a header image, a body part and a button to navigate to the wooCommerce account

To configure WhatsApp messaging go to **WooCommerce -> Settings -> Telinfy Messaging -> WhatsApp integration settings** section. 

In the first part, we have to enter **API Base URL and API key**. We can validate the credentials using the “Validate Credentials” button. Please use the credentials of the account **https://one.telinfy.com/dashboard/**  here. If the credentials are correct then we can configure the remaining part.

Feilds needs to be filled,

	- API Base URL 

	- API key

	- Country Code


In the Second part, We have options to select templates for each event. The templates will be fetched from the Telinfy account you have added above. We have to select the correct templates for each event. You can view details here **https://one.telinfy.com/** 
The “File Upload” field in this part of the configuration is for selecting a default image if the order has multiple products.

Feilds needs to be filled,

-Template for order confirmation

-Template for order cancellation

-Template for order refund

-Template for order notes

-Template for order shipment

-Template for other order status

-Template for abandoned cart


Choose correct templates for the above feild from the select box

-**Language code** : Language of the WhatsApp template. we can see it in the "telinfy account". (Example: en,en_US,..)

-**Default header image**  : Default image for WhatsApp header


####Template structure for each templates

These are sample templates the content may vary but the parameters should be the same.

-Template for order confirmation

	Header: Image(will pass image URL)

	Dear {{1}},
	Thank you for Shopping with us. We have received your order {{2}} worth {{3}}
	We request you to reconfirm the order
	Please click the button to view the order details

	{{1}} :- Name
	{{2}} :- Order Id
	{{3}} :- Order Amount

	Call to action Button
	View Order: http://<base_url>/{{1}}

	{{1}}:- page id

-Template for order cancellation

	Header: Image(will pass image URL)

	Your order is cancelled
	Dear {{1}},
	Your order {{2}} worth {{3}} is now cancelled.
	Let us know in case any support is required

	{{1}} :- Name
	{{2}} :- Order Id
	{{3}} :- Order Amount

	Call to action Button
	View Order: http://<base_url>/{{1}}

	{{1}}:- page id

-Template for order refund

	Header: Image(will pass image URL)

	Hi {{1}} ,
	We've processed a refund of {{2}} for the order {{3}}, and you should expect to see the amount appear in your bank account in the next couple of business days.

	{{1}} :- Name
	{{2}} :- Order Amount
	{{3}} :- Order Id

	Call to action Button
	View Order: http://<base_url>/{{1}}

	{{1}}:- page id

-Template for order notes

	Header: Image(will pass image URL)

	Hi {{1}},
	An order note has been added for order {{2}}
	Order Note: {{3}}

	{{1}} :- Name
	{{2}} :- Order Id
	{{3}} :- Order Note

	Call to action Button
	View Order: http://<base_url>/{{1}}

	{{1}}:- page id

-Template for order shipment

	Header: Image(will pass image URL)

	Hi {{1}},
	The order {{2}} worth {{3}} has been shipped

	{{1}} :- Name
	{{2}} :- Order Id
	{{3}} :- Order Amount

	Call to action Button
	View Order: http://<base_url>/{{1}}

	{{1}}:- page id

-Template for other order status

	Header: Image(will pass image URL)

	Dear {{1}}, The status of the order {{2}} changed to {{3}}
	Happy Shopping

	{{1}} :- Name
	{{2}} :- Order Id
	{{3}} :- Order Status

	Call to action Button
	View Order: http://<base_url>/{{1}}

	{{1}}:- page id

-Template for abandoned cart

	Header: Image(will pass image URL)

	Hi {{1}},
	We see you were trying to make a purchase but did not complete your payment. You can continue to click the below button.

	{{1}}:- Name

	Call to action Button
	View Order: http://<base_url>/{{1}}

	{{1}}:- page id



In the third part, we have checkboxes for all the events. Here we can enable or disable each event for the WhatsApp messaging service

Following are the events,

- Order confirmation

- Order cancellation

- Order refund

- Order notes

- Order shipment

- Order status

- Abandoned cart


##SMS Messaging

The WooCommerce Telinfy Messaging Plugin allows you to reach your customers instantly with Telinfy's efficient sms service. You can send SMS for all the events that are mentioned above.  The sms message consists of the content part and a link to navigate to the wooCommerce account

To configure SMS messaging go to **WooCommerce -> Settings -> Telinfy Messaging -> SMS integration settings** section.




In the first part, we have to enter **Username and Password**. Use credentials for the account **http://bulksms.greenadsglobal.com/index.php/homepage** here. Once the username and password fields are filled then we can configure the remaining part.

Feilds needs to be filled,

- Username

- Password

- sender name : We will get  **sender name** from the account **https://sapteleservices.com/**

In the Second part, We have options to add templates for each event. Please enter the template ids in the textbox correctly. You can view details here **https://sapteleservices.com/** 

Feilds needs to be filled,

-Template id for order confirmation

-Template id for order cancellation

-Template id for order refund

-Template id for order notes

-Template id for order shipment

-Template id for other order status

-Template id for abandoned cart

Following are the template for the sms messages. This templates should be as in the approved templates in **https://sapteleservices.com/** 

-Template for order confirmation : Template structure for the order confirmation sms.

	Allowed parameters :

		{$customer_name},{$order_id},{$redirect_url},{$order_total}

	Example : 

		Dear {$customer_name} ,
		Thank you for Shopping with us. We have received your order {$order_id} worth {$order_total}
		View Order: {$redirect_url}.
		GreenAds Global Pvt Ltd


-Template for order cancellation : Template structure for the order cancellation sms.

	Allowed parameters :

		{$customer_name},{$order_id},{$order_total},{$redirect_url}

	Example :

		Dear {$customer_name}
		Your order {$order_id} worth {$order_total} is now cancelled.
		Let us know in case any support is required
		View Order: {$redirect_url}.
		GreenAds Global Pvt Ltd

-Template for order refund: Template structure for the order refund sms.

	Allowed parameters : 

		{$customer_name},{$order_id},{$refund_amount},{$redirect_url}

	Example :

		Hi {$customer_name} ,
		We've processed a refund of {$refund_amount} for the order {$order_id}, and you should expect to see the amount appear in your bank account in the next couple of business days.
		View Order: {$redirect_url}.
		GreenAds Global Pvt Ltd

-Template for order notes : Template structure for the order note sms.

	Allowed parameters :

		{$customer_name},{$order_id},{$redirect_url}

	Example :

		Hi {$customer_name} ,
		An order note has been added for order {$order_id}
		Please see the order note here {$redirect_url}
		GreenAds Global Pvt Ltd

-Template for order shipment: Template structure for the order shipment sms.

	Allowed parameters :

		{$customer_name},{$order_id},{$order_total},{$redirect_url}

	Example :

		Hi {$customer_name}
		The order {$order_id} worth {$order_total} has been shipped
		View Order: {$redirect_url}.
		GreenAds Global Pvt Ltd

-Template for other order status : Template structure for the order status change sms.

	Allowed parameters :

		{$customer_name},{$order_id},{$new_status},{$redirect_url}

	Example :

		Dear {$customer_name},
		The status of the order {$order_id} changed to {$new_status}
		Happy Shopping
		View Order: {$redirect_url}.
		GreenAds Global Pvt Ltd

-Template for abandoned cart: Template structure for the abandoned cart sms.

	Allowed parameters : 

		{$customer_name}

	Example :

		Hi {$customer_name}
		We see you were trying to make a purchase but did not complete your payment. Please visit our store
		GreenAds Global Pvt Ltd



In the third part, we have the checkbox for all the events as in the WhatsApp integration. Here we can enable or disable each event for the SMS messaging service.


Following are the events,

- Order confirmation

- Order cancellation

- Order refund

- Order notes

- Order shipment

- Order status

- Abandoned cart

##Abandoned Cart

This feature is only applicable to Logged in customers. A customer logs in as a customer and add products to the cart and go to the checkout page and fill in all the field, especially the phone number field and leaves the cart without placing the order. After a specified time the cart will be marked as abandoned. After the cart is marked as abandoned the message to the customer will be trigged within the specified time in the configuration section.

-**Abandoned cart cron interval** : The frequency of the cron to check for the abandoned cart. This will send the abandoned cart notification message to the customers who have a leaved cart which will satisfy all the conditions of an abandoned cart

-**Abandoned cart time** : The time after the last updation of the cart to mark the cart as abandoned

-**Abandoned cart send message time** : The time to send the abandoned cart notification messages to the customer after the cart is marked as abandoned. You can enter multiple times separated by a comma so that you can send the messages multiple times to the customers.
Eg: 2,4,6, This configuration will notification messages to the customer 3 times after the cart is marked as abandoned. ie, After 2 hours, 4 hours and 6 hours. If the abandoned cart time is set to 2 hours, In this scenario, the message will be sent only after 4 (2 + 2) hours, 6 (2 + 4) hours, and 8 (2 + 6) hours after the cart becomes inactive.

-**Abandoned cart remove cron interval** : The frequency of the cron to remove the abandoned cart records

-**Delete abandoned records** : The time to delete the abandoned cart record after it is marked as abandoned

-**Message queue cron interval** : A message queue will be added when an order is placed. This is the time interval to execute the queue. Please add time in minutes

-**Message cron item count** : Number of items processed in an execution of queue. Please add the item count

== Upgrade Notice == 

/**1.0.0 **/
- Initial release

/**1.1.0 **/
- API upgrade


== Frequently Asked Questions ==

1.How to purchase the plans for each services?

Please visit the folloeing links to purchase plans

WhatsApp: https://www.greenadsglobal.com/whatsapp-business-api-pricing/
SMS: https://www.greenadsglobal.com/sms-pricing/
RCS: https://www.greenadsglobal.com/rcs-messaging/


== Screenshots == 

1. Settings. screenshot_new-1.png 
2. WhatsApp settings. screenshot-2.png
3. SMS settings. screenshot-3.png
4. Abandoned cart and cron settings. screenshot-4.png
5. Customer notify. screenshot-5.png

 == Changelog ==

/**1.0.0 **/
- Initial release

/**1.1.0 **/
- Upgraded the APIs