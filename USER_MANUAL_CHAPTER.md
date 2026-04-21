# Chapter X
# User Manual

## 1.0 Introduction
This chapter presents the user manual of the Digital Farming Platform System (DFPS). It serves as a guide for the intended users of the system, namely buyers, farmers, Department of Agriculture (DA) personnel, and DA Super Admin users. The purpose of this manual is to explain the proper use of the system's main features and modules, including account registration, authentication, profile management, product posting, marketplace browsing, messaging, notifications, announcements, and administrative functions.

The DFPS is a web-based platform developed to support interaction among agricultural stakeholders by providing an online environment where farmers can publish produce listings, buyers can browse and express interest in products, and DA personnel can monitor activities, provide announcements, and manage selected administrative operations.

## 2.0 Purpose of the User Manual
The user manual was prepared to achieve the following objectives:

1. To provide users with clear instructions on how to access and use the system.
2. To explain the major features available to each user role.
3. To reduce user confusion during system adoption and actual operation.
4. To provide a reference document for system navigation and troubleshooting.

## 3.0 Scope of the User Manual
This manual covers the actual operational features of the DFPS website, including:

- public user access
- account registration
- login and logout procedures
- password recovery
- buyer functions
- farmer functions
- DA functions
- DA Super Admin functions
- notifications and announcements
- profile management
- basic troubleshooting procedures

## 4.0 Intended Users
The DFPS is designed for the following user categories:

### 4.1 Public User
A public user is any visitor who accesses the website without logging in. Public users may view the landing page and create new accounts as buyers or farmers.

### 4.2 Buyer
A buyer is a registered user who can browse product listings, express interest in produce, communicate with farmers, and view notifications and announcements.

### 4.3 Farmer
A farmer is a registered user who can create and manage produce listings, communicate with buyers, receive notifications, and view relevant announcements.

### 4.4 Department of Agriculture User
A DA user is an authorized administrative user who can access the monitoring dashboard, manage announcements, view reports, and review users, listings, and produce-related information.

### 4.5 Department of Agriculture Super Admin
A DA Super Admin has all DA user privileges and additional authority to perform database backup and restore operations.

## 5.0 System Access
The DFPS is accessed through a web browser. Upon loading the website, the user may navigate to the login page or registration page depending on the purpose of access.

The major public access pages are:

- `index.php`
- `login.php`
- `register.php`
- `forgot_password.php`
- `reset_password.php`

To use the system properly, the user must have:

- a supported web browser
- a stable internet or local network connection
- a registered account for restricted features

## 6.0 Account Registration Procedure
The registration feature is intended for new users who wish to create an account in the system. Public registration is limited to buyer and farmer roles.

### 6.1 Steps in Account Registration
1. Open the registration page.
2. Enter the required personal information, including:
   - first name
   - last name
   - address
   - email address
   - cellphone number
3. Select the appropriate region, province, city or municipality, and barangay.
4. Enter the desired username.
5. Select the account type:
   - Buyer
   - Farmer
6. Enter the password and confirm the password.
7. Agree to the Terms and Conditions.
8. Click the registration button.

### 6.2 Expected Output
If the entered information is valid and complete, the account will be created successfully. The system will then instruct the user to proceed to the login page.

### 6.3 Registration Reminders
- The user must provide complete and valid information.
- Duplicate email addresses or usernames are not accepted.
- Only buyer and farmer accounts may be created through public registration.

## 7.0 Login Procedure
The login function enables registered users to access the appropriate system modules according to their assigned role.

### 7.1 Steps in Logging In
1. Open the login page.
2. Enter the registered email address.
3. Enter the password.
4. Click the login button.

### 7.2 Role-Based Access
After successful authentication, the system automatically redirects the user to the appropriate module:

- Buyer users are redirected to the buyer dashboard.
- Farmer users are redirected to the farmer dashboard.
- DA and DA Super Admin users are redirected to the administrative dashboard.

### 7.3 Invalid Login Handling
If incorrect login credentials are entered, the system displays an error message and does not permit access.

## 8.0 Password Recovery
The system provides a password recovery mechanism for users who forget their password.

### 8.1 Steps in Password Recovery
1. Open the forgot password page.
2. Enter the registered email address.
3. Submit the request.
4. Access the reset link sent through email.
5. Enter a new password.
6. Confirm the new password.
7. Submit the reset form.

### 8.2 Notes
- The password reset process depends on valid email configuration.
- Expired or invalid reset tokens are rejected by the system.

## 9.0 Profile Management
All logged-in users may update their profile information through the profile page.

### 9.1 Available Profile Functions
The profile management module allows the user to:

- update personal information
- change email address
- update phone number
- update address and barangay
- upload a profile picture
- remove a profile picture
- add a short bio
- add additional personal details
- change password

### 9.2 Steps in Updating a Profile
1. Open the profile settings page.
2. Edit the desired fields.
3. Upload a profile picture if necessary.
4. Click the save button.

### 9.3 Steps in Changing the Password
1. Open the profile settings page.
2. Enter the current password.
3. Enter the new password.
4. Confirm the new password.
5. Submit the password update form.

## 10.0 Buyer Module
The buyer module is intended for users who want to search for agricultural products and communicate with farmers.

### 10.1 Buyer Dashboard
The buyer dashboard displays active product listings and relevant announcements. It also provides filtering and search functions to help buyers find suitable products.

### 10.2 Browsing Product Listings
The buyer may browse available listings and view information such as:

- product title
- produce category
- price and unit
- farmer name
- area or location
- product image

### 10.3 Filtering Listings
The buyer may filter product listings by:

- produce type
- area
- minimum price
- maximum price

The buyer may also use the search field to locate products by title, produce name, farmer name, or location.

### 10.4 Viewing Product Details
When a listing is selected, the buyer may open the product details page to view:

- product description
- quantity available
- complete pricing information
- farmer details
- product images

### 10.5 Expressing Interest
The system allows buyers to indicate formal interest in a product post.

#### Procedure
1. Open the selected product details page.
2. Click the `Express Interest` button.

#### Result
The interest is recorded in the database and the farmer is notified. The system also prevents the buyer from expressing interest multiple times for the same post.

### 10.6 Sending Messages
The buyer may communicate directly with the farmer through the messaging feature.

#### Procedure
1. Open the product details page.
2. Click the message button.
3. Enter the message content.
4. Send the message.

#### Result
The system creates or opens the existing conversation, stores the message, and notifies the receiving user.

### 10.7 Notifications and Announcements
The buyer may open the notification page to view:

- message alerts
- announcement alerts
- system notifications

The buyer may also read announcements from the dedicated announcements page and from the dashboard side panel.

## 11.0 Farmer Module
The farmer module is intended for users who want to post produce listings and manage communication with buyers.

### 11.1 Farmer Dashboard
The farmer dashboard shows:

- recent announcements
- the farmer's product listings
- search and filtering tools for existing posts
- access to create a new listing

### 11.2 Creating a New Product Listing
The farmer may create a new produce post using the add post page.

#### Required Information
- product title
- description
- produce type
- price
- quantity
- unit
- product image (optional)

#### Procedure
1. Open the add post page.
2. Enter the required product details.
3. Upload an image if available.
4. Submit the form.

#### Result
The product post is stored successfully and becomes available in the marketplace, subject to the listing status.

### 11.3 Editing Product Listings
The farmer may modify listing information through the edit post page. This enables the farmer to update details such as price, quantity, title, and description when necessary.

### 11.4 Buyer Interest Monitoring
The farmer may review buyers who expressed interest in a post through the view interest page. This helps the farmer identify potential customers and continue communication.

### 11.5 Farmer Messaging
The farmer may use the messaging module to:

- receive messages from buyers
- reply to inquiries
- view active and archived conversations
- mark messages as read

### 11.6 Farmer Notifications
The farmer notification page may contain:

- new buyer interest alerts
- new direct message alerts
- announcements and other updates

### 11.7 Farmer Announcements
The farmer may read system announcements from the farmer dashboard and the announcements page. These announcements may be global or area-specific depending on the intended audience.

## 12.0 DA Module
The DA module supports monitoring, reporting, and announcement activities for authorized administrative users.

### 12.1 DA Dashboard
The DA dashboard serves as the command center of the system. It provides a summary of important operational data, including:

- number of farmers
- number of buyers
- active listings
- sold products
- market price comparison against SRP
- user distribution by area
- top produce listings
- recent marketplace activity

### 12.2 Posting Announcements
DA users may publish announcements for all users or for a selected area.

#### Procedure
1. Open the announcements page.
2. Enter the announcement title.
3. Select the target area or leave it blank for a global announcement.
4. Enter the message body.
5. Submit the announcement.

#### Result
The announcement is stored in the system and in-app notifications are sent to relevant users.

### 12.3 User Management
The DA user management page allows authorized personnel to:

- view registered users
- search by name, email, or username
- filter users by role
- filter users by area
- filter users by account status

This page helps DA personnel oversee marketplace participation and account activity.

### 12.4 Listings Monitoring
The listings page enables DA users to inspect the available product listings in the marketplace. This supports monitoring of produce activity and general market participation.

### 12.5 Produce and SRP Management
The produce management page provides access to produce reference entries and SRP values. This assists in tracking price behavior in relation to official or desired pricing references.

### 12.6 Reports
The reports page provides aggregated information relevant to the operation of the system. These reports may include user statistics, produce activity, and area-based summaries.

## 13.0 DA Super Admin Module
The DA Super Admin has all regular DA privileges in addition to higher-level administrative functions.

### 13.1 Database Backup
The backup feature allows the DA Super Admin to generate a downloadable SQL copy of the current database.

#### Procedure
1. Open the backup page.
2. Click the download backup option.
3. Save the generated SQL file.

### 13.2 Database Restore
The restore feature allows the DA Super Admin to upload a SQL file and restore the database contents.

#### Procedure
1. Open the backup page.
2. Select the SQL file to upload.
3. Confirm the restore action.
4. Wait for the result message.

### 13.3 Warning
The restore procedure affects the database contents directly and should only be performed by authorized personnel using verified backup files.

## 14.0 Notifications
Notifications are used throughout the system to inform users about important activities. These include:

- new buyer interest
- new messages
- new announcements
- system-related alerts

Users may read, mark, or clear notifications depending on the functions available in their notification page.

## 15.0 Messaging
The messaging system supports direct communication between users. Its major behaviors include:

- automatic conversation creation
- grouping of messages by conversation
- unread message tracking
- read status updates when conversations are opened
- archived and active conversation views

This module helps buyers and farmers communicate directly about listings and transactions.

## 16.0 Access Control
The system enforces role-based access. Each user can only access the pages and functions assigned to the account role.

### 16.1 Access Summary

| User Role | Main Access Rights |
|---|---|
| Public User | landing page, registration, login, forgot/reset password |
| Buyer | marketplace, messaging, notifications, announcements, profile |
| Farmer | listing management, messaging, notifications, announcements, profile |
| DA User | dashboard, announcements, reports, users, produce, listings |
| DA Super Admin | all DA features plus backup and restore |

## 17.0 Basic Troubleshooting Guide
This section provides solutions to common user concerns.

### 17.1 Unable to Log In
Possible causes:

- incorrect email or password
- inactive account
- server or database issue

Recommended action:

- verify login credentials
- reset the password if forgotten
- contact the system administrator if the issue continues

### 17.2 No Password Reset Email Received
Possible causes:

- incorrect registered email
- email delivery or configuration problem

Recommended action:

- verify the email address
- check spam or junk folders
- consult the administrator

### 17.3 Listing Not Visible
Possible causes:

- incomplete or failed submission
- missing required fields
- database processing issue

Recommended action:

- review the submitted information
- check the dashboard again
- repeat the submission if necessary

### 17.4 Messaging Problem
Possible causes:

- wrong recipient or conversation
- interrupted request during sending

Recommended action:

- reopen the conversation
- resend the message if needed
- refresh the page

### 17.5 Backup or Restore Failure
Possible causes:

- invalid SQL file
- corrupted backup file
- database execution error

Recommended action:

- verify the source of the backup file
- review the error message
- request assistance from technical personnel

## 18.0 Security Reminders
To ensure safe use of the system, users should observe the following:

- do not share passwords with other users
- log out after each session, especially on shared devices
- use valid and accurate profile information
- restrict administrative credentials to authorized personnel only
- perform database restore operations only when necessary

## 19.0 Summary
The DFPS user manual provides formal guidance on the proper use of the Digital Farming Platform System. Through this manual, users can understand how to register, authenticate, manage profiles, interact with listings, exchange messages, receive notifications, and use role-specific system features. The manual is intended to support the successful adoption and operation of the system by all target user groups.

