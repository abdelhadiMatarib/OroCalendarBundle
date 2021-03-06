@regression
@ticket-BAP-17336
@fixture-OroUserBundle:users.yml
@fixture-OroUserBundle:UserLocalizations.yml

Feature: Localized email notification for calendar event
  As a user
  I need to receive calendar events emails in predefined language

  Scenario: Prepare configuration with different languages
    Given sessions active:
      | Admin | first_session |
    When I proceed as the Admin
    And I login as administrator
    And I go to System / Configuration
    And I follow "System Configuration/General Setup/Language Settings" on configuration sidebar
    And I fill form with:
      | Supported Languages | [English, German, French] |
      | Use Default         | false                     |
      | Default Language    | English                   |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Prepare email templates for calendar invitation for different languages
    Given I go to System / Emails / Templates
    When I filter Template Name as is equal to "calendar_invitation_invite"
    And I click "edit" on first row in grid
    And fill "Email Template Form" with:
      | Subject | English Calendar Invitation Invite Subject |
      | Content | English Calendar Invitation Invite Body    |
    And I click "French"
    And fill "Email Template Form" with:
      | Subject | French Calendar Invitation Invite Subject |
      | Content | French Calendar Invitation Invite Body    |
    And I click "German"
    And fill "Email Template Form" with:
      | Subject | German Calendar Invitation Invite Subject |
      | Content | German Calendar Invitation Invite Body    |
    And I submit form
    Then I should see "Template saved" flash message

  Scenario: Set appropriate language setting for users
    Given I click My Configuration in user menu
    When I follow "System Configuration/General Setup/Language Settings" on configuration sidebar
    And I fill form with:
      | Use Organization | false  |
      | Default Language | German |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I go to System / User Management / Users
    And I click configuration "Charlie" in grid
    And I follow "System Configuration/General Setup/Language Settings" on configuration sidebar
    And I fill form with:
      | Use Organization | false  |
      | Default Language | French |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Every User-guest of calendar event should get an invitation email in a lang of his config
    Given go to Activities/ Calendar Events
    When click "Create Calendar event"
    And I fill "Event Form" with:
      | Title  | Some Calendar event |
      | Start  | 2018-09-01 12:00 AM |
      | End    | 2020-02-26 12:00 AM |
      | Guests | [Charlie Sheen, Megan Fox|
    And I save and close form
    And click "Notify"
    Then I should see "Calendar event saved" flash message
    And Email should contains the following:
      | To      | charlie@example.com                       |
      | Subject | French Calendar Invitation Invite Subject |
      | Body    | French Calendar Invitation Invite Body    |
    And Email should contains the following:
      | To      | megan@example.com                          |
      | Subject | English Calendar Invitation Invite Subject |
      | Body    | English Calendar Invitation Invite Body    |
