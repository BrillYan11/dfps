# DFPS Pilot Testing Plan

## 1. Project Title
Pilot Testing Plan for the Digital Farming Platform System (DFPS)

## 2. Purpose
This pilot testing activity is intended to validate the usability, reliability, and functional readiness of the DFPS website before wider deployment. The test focuses on the main workflows used by the three system roles:

- Buyer
- Farmer
- Department of Agriculture (DA) user

The pilot test will confirm whether the website supports account registration, login, produce posting, browsing, buyer interest submission, messaging, announcements, notifications, and DA monitoring tasks under realistic usage.

## 3. System Background
DFPS is a PHP and MySQL-based web application with the following major modules:

- Public pages: landing page, login, registration, forgot/reset password
- Buyer module: marketplace browsing, product view, messaging, notifications, announcements
- Farmer module: dashboard, add/edit post, view buyer interests, messaging, notifications
- DA module: dashboard analytics, user monitoring, listings, produce/SRP monitoring, reports, announcements, backup/restore
- SMS side service: local Node.js SMS gateway for DA broadcast support

## 4. Pilot Testing Objectives
The pilot testing aims to:

1. Verify that critical website features work as expected.
2. Identify usability issues before full rollout.
3. Measure whether users can complete common tasks without assistance.
4. Detect defects in role-based access, data handling, and navigation.
5. Gather feedback from representative users for improvement.

## 5. Scope of Pilot Testing
The pilot test will cover the following functional areas.

### In Scope
- Account registration for buyer and farmer
- Login and role-based redirection
- Password recovery flow
- Buyer marketplace browsing and filtering
- Product detail viewing
- Buyer express interest action
- Buyer-farmer messaging
- Farmer product posting with optional image upload
- Farmer notification and interest monitoring
- DA dashboard viewing
- DA announcements
- DA reports and listings review
- DA backup and restore page access
- Notification updates and unread counts

### Out of Scope
- Full production load testing
- Security penetration testing
- GSM modem hardware validation beyond basic SMS workflow confirmation
- Large-scale database recovery drills on production data

## 6. Participants
The recommended pilot group is small but representative.

- 2 to 3 Buyers
- 2 to 3 Farmers
- 1 to 2 DA users
- 1 Technical observer or recorder

Suggested total: 6 to 9 participants

## 7. Test Environment
Pilot testing should be performed in a controlled staging or local deployment environment.

### Software Environment
- Web server: Apache or equivalent local PHP stack
- PHP version: 8.2 or compatible
- Database: MySQL / MariaDB with DFPS schema
- Browser: Google Chrome, Microsoft Edge, and mobile browser where possible
- Optional Node service for SMS:
  - `sms/sms_server.js`
  - local endpoint expected at `http://localhost:3001/send-sms`

### Test Data Requirements
- At least 1 DA account
- At least 2 farmer accounts
- At least 2 buyer accounts
- Seeded produce records
- Seeded areas / city records
- Sample listings with and without images

## 8. Entry Criteria
Pilot testing may start only if the following are ready:

- The website is accessible in the target environment.
- Database connection is working.
- Required user roles exist.
- Produce and area reference data are available.
- Messaging, notifications, and announcement features are enabled.
- Changed PHP files pass syntax checking.

Recommended pre-check command:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

## 9. Exit Criteria
Pilot testing is considered complete when:

- All planned scenarios have been executed.
- All critical and major issues are logged.
- Re-test results are recorded for issues fixed during the pilot.
- User feedback forms are completed.
- A short pilot summary report is produced.

## 10. Roles and Responsibilities
### Test Facilitator
- Brief participants
- Assign test accounts
- Monitor execution
- Collect issue reports

### Participants
- Perform assigned tasks independently
- Report confusion, errors, and delays
- Complete the post-test feedback form

### Technical Support
- Reset accounts or data if needed
- Review logs and database results
- Fix blocking issues found during pilot testing

## 11. Pilot Test Scenarios

### Scenario A. Registration and Login
Objective: Confirm that public users can register and log in successfully.

Steps:
1. Open `register.php`.
2. Register a new buyer account.
3. Register a new farmer account.
4. Log in using both accounts separately through `login.php`.
5. Verify role-based redirection:
   - Buyer -> `buyer/`
   - Farmer -> `farmer/`
   - DA -> `da/`

Expected Result:
- Registration succeeds with valid data.
- Duplicate email/username is rejected.
- Invalid credentials show an error.
- Successful login redirects to the correct dashboard.

### Scenario B. Buyer Marketplace Flow
Objective: Confirm that buyers can browse and engage with listings.

Steps:
1. Log in as buyer.
2. Open `buyer/index.php`.
3. Search for a produce item.
4. Apply area, produce, and price filters.
5. Open a listing using `buyer/view_post.php?id=...`.
6. Click `Express Interest`.
7. Open the messaging page and send a message to the farmer.

Expected Result:
- Listings load correctly.
- Filters update results properly.
- Product details display correct title, price, unit, quantity, farmer, and location.
- Express interest is saved once only.
- Farmer receives a notification.
- Message is delivered and visible in the conversation.

### Scenario C. Farmer Listing Management Flow
Objective: Confirm that farmers can publish and manage posts.

Steps:
1. Log in as farmer.
2. Open `farmer/add_post.php`.
3. Create a listing with produce type, price, quantity, unit, description, and image.
4. Verify redirection after successful submission.
5. Confirm the listing appears in farmer and buyer views.
6. Open farmer notifications.
7. Check if buyer interest and messages are visible.

Expected Result:
- New post is saved successfully.
- Uploaded image is stored and displayed correctly.
- Listing becomes visible in the marketplace.
- Buyer interest creates a notification.
- Farmer can identify interested buyers and continue messaging.

### Scenario D. DA Monitoring and Announcement Flow
Objective: Confirm that DA users can monitor the system and publish announcements.

Steps:
1. Log in as DA.
2. Open `da/index.php`.
3. Review analytics cards and recent market activity.
4. Open `da/announcements.php`.
5. Post one global announcement.
6. Post one area-specific announcement.
7. Confirm that buyer/farmer users in scope receive the announcement as notifications.

Expected Result:
- DA dashboard loads summary metrics.
- Announcement creation succeeds.
- Global announcements reach all non-DA users.
- Area-specific announcements reach only the correct users.

### Scenario E. Reports and Admin Access
Objective: Confirm that administrative pages are reachable by authorized roles only.

Steps:
1. Log in as DA and access reports, listings, produce, and users pages.
2. Log in as DA Super Admin and access `da/backup.php`.
3. Attempt to access `da/backup.php` using a non-super-admin role.

Expected Result:
- DA users can access authorized admin pages.
- Only `DA_SUPER_ADMIN` can access backup and restore.
- Unauthorized users are redirected away from restricted pages.

### Scenario F. Password Recovery
Objective: Confirm that password reset workflow behaves correctly.

Steps:
1. Open `forgot_password.php`.
2. Submit a registered email.
3. Verify reset token creation and email dispatch behavior.
4. Open the reset link.
5. Change the password in `reset_password.php`.
6. Log in using the new password.

Expected Result:
- Valid email initiates password reset.
- Invalid or expired tokens are rejected.
- New password works after reset.

### Scenario G. Notification and Messaging Behavior
Objective: Confirm that event-driven notifications remain consistent.

Steps:
1. Trigger a buyer interest event.
2. Trigger a new direct message.
3. Open notification pages as the receiving user.
4. Mark notifications as read.
5. Return to the page and verify updated unread counts.

Expected Result:
- Notifications are created for supported events.
- Unread counts update correctly.
- Mark-read behavior persists.

## 12. Test Case Summary Table

| Test ID | Module | Test Description | Expected Outcome | Priority |
|---|---|---|---|---|
| PT-01 | Registration | Register buyer and farmer accounts | Account created successfully | High |
| PT-02 | Login | Log in with valid credentials | Redirect to correct dashboard | High |
| PT-03 | Login | Log in with invalid credentials | Error message displayed | High |
| PT-04 | Buyer | Browse and filter listings | Matching listings displayed | High |
| PT-05 | Buyer | Express interest on a post | Interest saved; farmer notified | High |
| PT-06 | Messaging | Send buyer-farmer message | Message delivered and visible | High |
| PT-07 | Farmer | Create new product post | Post saved and visible | High |
| PT-08 | Farmer | Upload listing image | Image saved and rendered | Medium |
| PT-09 | DA | View dashboard analytics | Cards and tables load | Medium |
| PT-10 | DA | Post global announcement | Non-DA users notified | High |
| PT-11 | DA | Post area announcement | Correct area users notified | High |
| PT-12 | Access Control | Restrict backup page | Only super admin allowed | High |
| PT-13 | Password Reset | Reset password with token | Password updated successfully | Medium |
| PT-14 | Notifications | Mark notifications as read | Unread count decreases | Medium |

## 13. Success Metrics
The pilot will be considered acceptable if the following minimum targets are met:

- 95% completion rate for critical tasks
- 0 unresolved critical defects
- No role-access violation in protected pages
- No data loss in listing, messaging, or notification flows
- At least 80% of participants rate usability as satisfactory or better

## 14. Defect Severity Guide
Use the following classification during pilot testing:

- Critical: system crash, blocked login, corrupted data, unauthorized access
- Major: important feature fails but system still runs
- Minor: small UI issue, wording problem, or non-blocking bug
- Suggestion: improvement request with no immediate defect

## 15. Issue Log Template
Use this table during execution.

| Issue ID | Date | Page/Module | Role | Description | Severity | Status | Remarks |
|---|---|---|---|---|---|---|---|
| PT-ISS-001 |  |  |  |  |  | Open |  |

## 16. Pilot Execution Procedure
1. Prepare the environment and test accounts.
2. Brief participants on the purpose of the pilot.
3. Assign scenarios per role.
4. Observe task completion without giving immediate help unless blocked.
5. Record completion time, errors, and participant comments.
6. Consolidate defects and feedback after each session.
7. Re-test major fixes if time permits.

## 17. Post-Test Questionnaire
Ask each participant to rate the following from 1 to 5.

1. The website was easy to navigate.
2. The labels, buttons, and menus were clear.
3. I was able to complete my assigned tasks without confusion.
4. The pages responded fast enough for normal use.
5. I would be comfortable using this system in actual operations.

Open-ended questions:

- Which part of the website was most useful?
- Which part was confusing or difficult?
- What improvement would you suggest first?

## 18. Risks to Watch During Pilot Testing
- Schema auto-updates in `includes/db.php` may change the database during normal page loads.
- Some admin/report queries may still need closer validation for edge cases.
- Password reset depends on mail configuration.
- SMS broadcast depends on the local Node server and GSM modem availability.
- Backup restore is powerful and should be tested only with disposable data.

## 19. Recommended Pilot Result Summary Format
After the pilot, prepare a one-page summary containing:

- Number of participants
- Number of scenarios executed
- Number of passed, failed, and blocked tests
- Top 5 defects found
- User satisfaction summary
- Recommendation:
  - Ready for wider deployment
  - Ready with minor fixes
  - Needs major revision before deployment

## 20. Conclusion
This pilot testing plan is designed to evaluate whether DFPS is functionally ready and usable for its intended stakeholders. By testing the real workflows of buyers, farmers, and DA users, the team can identify defects early, improve the user experience, and reduce deployment risk before full implementation.
