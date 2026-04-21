# DFPS User Manual

## 1. Introduction
The Digital Farming Platform System (DFPS) is a web-based platform that connects farmers, buyers, and Department of Agriculture (DA) personnel in one system. It allows users to register, log in, post produce listings, browse available products, communicate through direct messages, receive notifications, and monitor agricultural activity.

This manual explains how to use the system based on the available modules in this project.

## 2. System Users
DFPS supports the following user roles:

- Public User
- Buyer
- Farmer
- DA User
- DA Super Admin

## 3. Main Features
The system includes the following functions:

- User registration and login
- Password recovery and reset
- Buyer marketplace browsing and filtering
- Farmer produce posting and editing
- Buyer-farmer messaging
- Notifications and announcements
- Profile management
- DA dashboard and monitoring tools
- Produce and SRP monitoring
- Reports and listings review
- Database backup and restore for DA Super Admin
- Optional SMS broadcast integration for DA operations

## 4. System Access
To use the system:

1. Open the DFPS website in a web browser.
2. Use the landing page or go directly to the login page.
3. Log in using your registered email and password.

Public entry pages:

- `index.php`
- `login.php`
- `register.php`
- `forgot_password.php`
- `reset_password.php`

## 5. Account Registration
Only public users registering as Buyer or Farmer can create accounts from the registration page.

### Steps to Register
1. Open `register.php`.
2. Enter the following information:
   - First name
   - Last name
   - Street address / house number
   - Email address
   - Cellphone number
   - Region
   - Province
   - City / Municipality
   - Barangay
   - Username
   - User type
   - Password
   - Confirm password
3. Agree to the Terms and Conditions.
4. Click `Complete Registration`.

### Expected Result
- The system creates the account successfully.
- The user is asked to proceed to login.

### Notes
- Public registration supports only `BUYER` and `FARMER`.
- Duplicate email addresses or usernames are rejected.

## 6. Logging In

### Steps to Log In
1. Open `login.php`.
2. Enter your email address.
3. Enter your password.
4. Click `Login`.

### Role-Based Redirection
After successful login, the system redirects users to the correct area:

- Buyer -> `buyer/`
- Farmer -> `farmer/`
- DA / DA Super Admin -> `da/`

### Common Login Issues
- If credentials are incorrect, the system displays an error.
- If the account is inactive, login will not proceed.

## 7. Forgot Password and Reset Password

### Steps to Reset Password
1. Open `forgot_password.php`.
2. Enter your registered email address.
3. Submit the form.
4. Check your email for the reset link.
5. Open the link.
6. Enter a new password in `reset_password.php`.
7. Log in using the updated password.

### Important Notes
- Password reset requires working email configuration.
- Expired or invalid reset links will be rejected.

## 8. User Profile Management
All logged-in users can update their profile through `profile/index.php`.

### Available Profile Actions
- Update first name and last name
- Update email and phone number
- Update address and barangay
- Add a bio
- Add additional details
- Upload a profile picture
- Remove a profile picture
- Change password

### Steps to Update Profile
1. Open the profile settings page.
2. Edit the desired fields.
3. Click `Save Profile Changes`.

### Steps to Change Password
1. Open the profile page.
2. Enter your current password.
3. Enter your new password.
4. Confirm the new password.
5. Submit the password form.

## 9. Buyer User Guide

### 9.1 Buyer Dashboard
After login, buyers are redirected to `buyer/index.php`.

The buyer dashboard allows users to:

- Browse active product listings
- View announcements
- Search produce listings
- Filter products by:
  - produce type
  - area
  - minimum price
  - maximum price

### 9.2 Viewing Listings
Each listing may display:

- Product title
- Produce type
- Price
- Unit
- Farmer name
- Area or location
- Product image

To open a listing in detail:

1. Select a product from the marketplace.
2. Open the product details page `buyer/view_post.php?id=...`.

### 9.3 Expressing Interest
Buyers can formally express interest in a product listing.

#### Steps
1. Open the listing details page.
2. Click `Express Interest`.

#### Result
- The interest is recorded in the system.
- The farmer receives a notification.
- Duplicate interest submissions for the same post are prevented.

### 9.4 Sending Messages to Farmers
Buyers can contact farmers from the listing page or through the message module.

#### Steps
1. Open the product details page.
2. Click `Send a Message`.
3. Type the message.
4. Submit the message.

#### Result
- A conversation is created automatically if it does not yet exist.
- The message is stored and sent to the farmer.
- The receiver gets a new message notification.

### 9.5 Buyer Messages
Buyer messaging is handled through `buyer/message.php`.

Available actions:

- View active chats
- View archived chats
- Search conversations
- Open a conversation
- Read and send messages
- Mark all messages as read

### 9.6 Buyer Notifications
Buyers can open `buyer/notification.php` to:

- View unread notifications
- View announcement notifications
- View message notifications
- Mark notifications as read
- Clear or dismiss notifications depending on available actions

### 9.7 Buyer Announcements
Buyers can view public and area-related announcements through:

- `buyer/index.php`
- `buyer/announcements.php`

## 10. Farmer User Guide

### 10.1 Farmer Dashboard
After login, farmers are redirected to `farmer/index.php`.

The farmer dashboard allows users to:

- View recent announcements
- Review personal product listings
- Search and filter own posts
- Create new posts
- Edit existing posts

### 10.2 Creating a New Product Post
Farmers create listings through `farmer/add_post.php`.

#### Steps
1. Click `Create New Post`.
2. Enter the following:
   - Post title
   - Description
   - Produce type
   - Price
   - Quantity
   - Unit
   - Product image (optional)
3. Submit the form.

#### Result
- The post is saved in the system.
- The listing becomes visible in the marketplace if active.

### 10.3 Notes on Produce Posting
- The system can automatically populate the unit based on selected produce.
- The system may display the SRP reference for guidance.
- The farmer's registered area is used for the listing.

### 10.4 Editing a Product Post
Farmers can edit listing details through `farmer/edit_post.php?id=...`.

Typical editable information includes:

- Title
- Description
- Price
- Quantity
- Unit
- Post image or related details depending on the page behavior

### 10.5 Viewing Buyer Interest
Farmers can monitor interest through the relevant page in the farmer area, including:

- `farmer/view_interests.php`

This allows the farmer to review buyers who expressed interest in a specific listing.

### 10.6 Farmer Messages
Farmer messaging uses the same interface behavior as the buyer side and is accessed through:

- `farmer/message.php`

Available actions:

- Open active or archived chats
- Read incoming buyer messages
- Reply to buyers
- Search conversations
- Mark messages as read

### 10.7 Farmer Notifications
Farmers can open:

- `farmer/notification.php`

This page may include:

- buyer interest notifications
- new message notifications
- announcements and related updates

### 10.8 Farmer Announcements
Farmers can view:

- dashboard announcements on `farmer/index.php`
- full announcements on `farmer/announcements.php`

## 11. DA User Guide

### 11.1 DA Dashboard
DA users log in to the administrative area at `da/index.php`.

The dashboard provides:

- Total farmers
- Total buyers
- Active listings
- Sold products
- Market price analysis versus SRP
- User distribution by area
- Top produce by number of listings
- Recent market activity

### 11.2 DA Announcements
DA users can create announcements through `da/announcements.php`.

#### Steps
1. Open the announcements page.
2. Enter the announcement title.
3. Select target area:
   - leave blank for global announcement
   - choose an area for area-based announcement
4. Enter the message body.
5. Click `Post Announcement`.

#### Result
- The announcement is saved.
- Relevant users receive in-app notifications.

### 11.3 Managing Users
DA users can manage marketplace participants through `da/users.php`.

Available functions include:

- View all users
- Filter by role
- Filter by area
- Filter by account status
- Search by name, username, or email
- Review summary counts of farmers, buyers, and active accounts

### 11.4 Reviewing Listings
DA users can review marketplace listings through:

- `da/listings.php`

This is used to monitor available listings and related marketplace activity.

### 11.5 Produce and SRP Monitoring
DA users can manage produce reference data through:

- `da/produce.php`

This module is used to review and maintain produce entries and SRP values for monitoring market prices.

### 11.6 Reports
DA users can access system reporting through:

- `da/reports.php`

Reports may include:

- user counts
- area-based data
- listing activity
- produce price comparisons

### 11.7 DA Messaging and Notifications
DA users may also access:

- `da/message.php`
- `da/notification.php`

These pages support internal or operational communication and alert monitoring where configured in the system.

## 12. DA Super Admin Guide
Users with the `DA_SUPER_ADMIN` role have all DA permissions plus additional administrative access.

### 12.1 Backup and Restore
The database backup page is:

- `da/backup.php`

#### Backup Steps
1. Open the backup page.
2. Click the backup download button.
3. Save the generated `.sql` file.

#### Restore Steps
1. Open the backup page.
2. Upload a valid `.sql` backup file.
3. Confirm the restore operation.

### Important Warning
- Restore overwrites current database content.
- Backup and restore should be performed carefully and only by authorized personnel.

## 13. Notifications
Notifications are used across the system to keep users informed.

Examples of notification sources:

- buyer interest in a listing
- new direct messages
- DA announcements
- system alerts

Typical notification actions:

- view notification details
- mark one notification as read
- mark all notifications as read
- dismiss or clear notifications where supported

## 14. Messaging
DFPS contains a direct messaging feature between participants.

### Messaging Behavior
- Conversations are created automatically when a message is started.
- Messages are grouped by conversation.
- Conversations may have active and archived views.
- Unread messages are marked as read when the conversation is opened.

### Best Practices
- Keep messages related to actual listings or marketplace transactions.
- Review unread counts regularly.
- Use archived chats to organize old conversations if needed.

## 15. Announcements
Announcements are created by DA users and shown to buyer and farmer users.

Announcement types:

- Global announcements
- Area-specific announcements

Announcements appear in:

- dashboard side panels
- announcements pages
- notification pages

## 16. Access Control Summary

| Role | Main Access |
|---|---|
| Public User | Landing page, register, login, forgot/reset password |
| Buyer | Buyer marketplace, product view, messages, notifications, announcements, profile |
| Farmer | Farmer listings, add/edit post, messages, notifications, announcements, profile |
| DA | Dashboard, announcements, reports, produce, listings, users, message/notification modules |
| DA Super Admin | All DA features plus backup and restore |

## 17. Common Problems and Troubleshooting

### Problem: Cannot Log In
Possible causes:

- wrong email or password
- inactive account
- database or server issue

Action:

- verify credentials
- reset password if needed
- contact system administrator if the account remains inaccessible

### Problem: No Password Reset Email Received
Possible causes:

- incorrect email
- mail server configuration problem

Action:

- recheck the registered email
- inspect spam or junk folder
- contact administrator for assistance

### Problem: Listing Does Not Appear
Possible causes:

- submission failed
- required fields were incomplete
- database write failed

Action:

- reopen the farmer dashboard
- check for confirmation or error messages
- confirm produce and price information

### Problem: Message Not Visible
Possible causes:

- wrong conversation selected
- sending failed during request

Action:

- refresh the message page
- reopen the conversation
- verify recipient selection

### Problem: Announcement Not Seen by User
Possible causes:

- user is outside the targeted area
- notification was already marked as read

Action:

- verify whether the post was global or area-specific
- check notifications and announcements pages

### Problem: Backup Restore Fails
Possible causes:

- invalid SQL file
- file content error
- database execution error

Action:

- use a valid backup file
- inspect the error message shown on the page
- consult the technical administrator before retrying

## 18. Security Guidelines
To use DFPS safely:

- keep your password private
- change your password regularly
- log out after using shared devices
- do not share DA or Super Admin accounts
- do not upload unnecessary or unrelated files
- restrict backup and restore access to authorized staff only

## 19. Recommended Usage Workflow

### For Buyers
1. Register an account.
2. Log in.
3. Browse listings.
4. Apply filters as needed.
5. View product details.
6. Express interest.
7. Message the farmer.
8. Monitor notifications.

### For Farmers
1. Register an account.
2. Log in.
3. Complete or update profile.
4. Create a product listing.
5. Monitor interest and messages.
6. Update listing details when needed.
7. Review announcements from DA.

### For DA Users
1. Log in.
2. Review dashboard indicators.
3. Monitor users, produce, and listings.
4. Publish announcements when necessary.
5. Review reports and market trends.

### For DA Super Admin
1. Perform all DA tasks.
2. Manage database backups.
3. Restore the system only when necessary and authorized.

## 20. Conclusion
DFPS is designed to support coordination among buyers, farmers, and government agricultural staff through one web system. By following this manual, users can register, manage their profiles, post and browse listings, communicate effectively, and perform role-specific tasks within the platform.

