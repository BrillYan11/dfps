# DFPS Pilot Test Case Document

## Project
Digital Farming Platform System (DFPS)

## Purpose
This document provides a tabular pilot testing checklist for the DFPS website. It is intended for actual execution during pilot testing and can be printed or used as a soft copy by testers, observers, and advisers.

## Test Execution Legend
- `Pass` = expected behavior observed
- `Fail` = actual result does not match expected result
- `Blocked` = test cannot continue due to dependency or system issue
- `N/A` = not applicable for the assigned tester

## Tester Information

| Field | Details |
|---|---|
| Tester Name |  |
| Role |  |
| Test Date |  |
| Browser / Device |  |
| Environment |  |

## Test Cases

| Test ID | Module | Role | Test Objective | Pre-Condition | Test Steps | Expected Result | Actual Result | Status | Remarks |
|---|---|---|---|---|---|---|---|---|---|
| PT-01 | Registration | Public User | Register a buyer account | Registration page is accessible | 1. Open `register.php` 2. Fill in valid buyer information 3. Submit the form | Buyer account is created successfully and user is prompted to log in |  |  |  |
| PT-02 | Registration | Public User | Register a farmer account | Registration page is accessible | 1. Open `register.php` 2. Fill in valid farmer information 3. Submit the form | Farmer account is created successfully and user is prompted to log in |  |  |  |
| PT-03 | Registration | Public User | Validate required registration fields | Registration page is accessible | 1. Leave one or more required fields empty 2. Submit the form | Registration is rejected and a validation error is shown |  |  |  |
| PT-04 | Registration | Public User | Reject duplicate account data | Existing account already exists | 1. Register using an existing email or username 2. Submit the form | System rejects duplicate registration and shows an error |  |  |  |
| PT-05 | Login | Buyer | Log in as buyer | Buyer account exists and is active | 1. Open `login.php` 2. Enter valid buyer credentials 3. Submit | User is redirected to `buyer/` |  |  |  |
| PT-06 | Login | Farmer | Log in as farmer | Farmer account exists and is active | 1. Open `login.php` 2. Enter valid farmer credentials 3. Submit | User is redirected to `farmer/` |  |  |  |
| PT-07 | Login | DA | Log in as DA | DA account exists and is active | 1. Open `login.php` 2. Enter valid DA credentials 3. Submit | User is redirected to `da/` |  |  |  |
| PT-08 | Login | Any | Reject invalid credentials | Login page is accessible | 1. Enter invalid email or password 2. Submit | System shows invalid credential error |  |  |  |
| PT-09 | Password Reset | Public User | Request password reset | Mail configuration is working | 1. Open `forgot_password.php` 2. Submit a registered email | Password reset request is accepted and reset process starts |  |  |  |
| PT-10 | Password Reset | Public User | Reset password using token | Valid reset token exists | 1. Open reset link 2. Enter new password 3. Submit | Password is updated and new login succeeds |  |  |  |
| PT-11 | Buyer Marketplace | Buyer | View active listings | Buyer is logged in | 1. Open `buyer/index.php` | Active listings are displayed with title, price, produce, area, and image |  |  |  |
| PT-12 | Buyer Marketplace | Buyer | Search listings | Listings exist | 1. Enter a search term in the buyer search box | Results update to match the search term |  |  |  |
| PT-13 | Buyer Marketplace | Buyer | Filter listings by produce | Listings and produce data exist | 1. Select a produce filter | Only matching listings are shown |  |  |  |
| PT-14 | Buyer Marketplace | Buyer | Filter listings by area | Listings and areas exist | 1. Select an area filter | Only listings from the selected area are shown |  |  |  |
| PT-15 | Buyer Marketplace | Buyer | Filter listings by price range | Listings exist | 1. Set minimum and/or maximum price 2. Apply filter | Only listings within range are shown |  |  |  |
| PT-16 | Product View | Buyer | View listing details | Buyer is logged in and listing exists | 1. Open `buyer/view_post.php?id=...` | Post details show title, description, price, unit, quantity, farmer, location, and images |  |  |  |
| PT-17 | Interest | Buyer | Express interest in a listing | Buyer is logged in and has not yet expressed interest | 1. Open a listing 2. Click `Express Interest` | Interest is saved and success message is shown |  |  |  |
| PT-18 | Interest | Buyer | Prevent duplicate interest | Buyer already expressed interest on same post | 1. Click `Express Interest` again | System prevents duplicate interest and shows error/info message |  |  |  |
| PT-19 | Messaging | Buyer | Start message with farmer | Buyer is logged in and listing belongs to another user | 1. Open listing details 2. Click `Send a Message` 3. Send text | Conversation opens and message is saved |  |  |  |
| PT-20 | Messaging | Farmer | Receive buyer message | Buyer already sent a message | 1. Log in as farmer 2. Open `farmer/message.php` | Farmer can view the conversation and received message |  |  |  |
| PT-21 | Notifications | Farmer | Receive buyer interest notification | Buyer expressed interest | 1. Log in as farmer 2. Open notifications page | New interest notification is visible |  |  |  |
| PT-22 | Notifications | User | Mark notification as read | Unread notification exists | 1. Open notification page 2. Mark notification as read | Notification read state updates and unread count decreases |  |  |  |
| PT-23 | Farmer Posting | Farmer | Open add post page | Farmer is logged in | 1. Open `farmer/add_post.php` | Add post form loads correctly |  |  |  |
| PT-24 | Farmer Posting | Farmer | Create post without image | Farmer is logged in and produce data exists | 1. Fill in required post fields 2. Submit | Post is created successfully and becomes visible in listings |  |  |  |
| PT-25 | Farmer Posting | Farmer | Create post with image | Farmer is logged in and image file is available | 1. Fill in post data 2. Upload image 3. Submit | Post and image are saved successfully |  |  |  |
| PT-26 | Farmer Posting | Farmer | Auto-fill unit based on produce | Produce has unit data | 1. Select a produce type in add post form | Unit field updates automatically based on produce data |  |  |  |
| PT-27 | Farmer Posting | Farmer | Display SRP reference | Produce has SRP data | 1. Select a produce type in add post form | SRP information is displayed for reference |  |  |  |
| PT-28 | Farmer Dashboard | Farmer | Verify posted listing appears | Farmer already submitted a listing | 1. Open `farmer/index.php` | Newly created listing appears in farmer dashboard |  |  |  |
| PT-29 | Buyer Visibility | Buyer | Verify farmer listing appears in marketplace | Farmer already submitted active listing | 1. Log in as buyer 2. Open marketplace | Newly created active listing is visible to buyers |  |  |  |
| PT-30 | Announcements | Buyer/Farmer | View relevant announcements | Announcement exists | 1. Open dashboard or announcements page | User sees relevant global or area-based announcements |  |  |  |
| PT-31 | DA Dashboard | DA | View command center analytics | DA is logged in | 1. Open `da/index.php` | Dashboard cards, price analysis, and activity sections load |  |  |  |
| PT-32 | DA Announcements | DA | Post a global announcement | DA is logged in | 1. Open `da/announcements.php` 2. Enter title/body 3. Leave area blank 4. Submit | Global announcement is posted successfully |  |  |  |
| PT-33 | DA Announcements | DA | Post area-specific announcement | DA is logged in and areas exist | 1. Open announcement form 2. Select area 3. Enter title/body 4. Submit | Area-specific announcement is posted successfully |  |  |  |
| PT-34 | DA Announcements | Buyer/Farmer | Receive announcement notification | DA posted announcement | 1. Log in as target user 2. Open notifications | Relevant announcement notification is visible |  |  |  |
| PT-35 | DA Users | DA | Access user management page | DA is logged in | 1. Open `da/users.php` | User management page loads correctly |  |  |  |
| PT-36 | DA Listings | DA | Access listings review page | DA is logged in | 1. Open `da/listings.php` | Listings review page loads correctly |  |  |  |
| PT-37 | DA Produce | DA | Access produce/SRP page | DA is logged in | 1. Open `da/produce.php` | Produce management page loads correctly |  |  |  |
| PT-38 | DA Reports | DA | Access reports page | DA is logged in | 1. Open `da/reports.php` | Reports page loads correctly |  |  |  |
| PT-39 | Access Control | Non-DA User | Restrict DA pages | Logged in as buyer or farmer | 1. Attempt to open `da/index.php` or other admin page | Unauthorized user is redirected away |  |  |  |
| PT-40 | Access Control | DA | Restrict backup page to super admin | Logged in as regular DA | 1. Attempt to open `da/backup.php` | Access is denied or redirected |  |  |  |
| PT-41 | Backup | DA Super Admin | Open backup page | Logged in as `DA_SUPER_ADMIN` | 1. Open `da/backup.php` | Backup and restore page loads correctly |  |  |  |
| PT-42 | Backup | DA Super Admin | Download SQL backup | Super admin is logged in | 1. Click download backup button | SQL backup file is generated and downloaded |  |  |  |
| PT-43 | Restore | DA Super Admin | Upload restore file | Super admin is logged in and test SQL file exists | 1. Upload SQL file 2. Confirm restore | Restore process starts and system reports success or error clearly |  |  |  |
| PT-44 | Messaging | User | Mark messages as read | Existing unread messages exist | 1. Open message thread | Incoming unread messages are marked as read |  |  |  |
| PT-45 | Notifications | User | Verify unread indicator updates | New messages/notifications exist | 1. Trigger a new event 2. Refresh or poll supported page | Unread indicator/count updates correctly |  |  |  |
| PT-46 | Session | Any Logged-in User | Log out from system | User is logged in | 1. Click logout | Session ends and user is redirected appropriately |  |  |  |

## Execution Summary

| Summary Item | Count |
|---|---|
| Total Test Cases | 46 |
| Passed |  |
| Failed |  |
| Blocked |  |
| Not Executed |  |

## Defect Log

| Defect ID | Related Test ID | Module/Page | Severity | Description | Reproducible | Status | Remarks |
|---|---|---|---|---|---|---|---|
| D-001 |  |  |  |  | Yes/No | Open |  |

## Tester Sign-Off

| Field | Details |
|---|---|
| Prepared By |  |
| Reviewed By |  |
| Approved By |  |
| Date |  |

