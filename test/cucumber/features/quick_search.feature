Feature: Quick-search on main admin panel
  Allows you to navigate to a group or user by name

  Background: The user is logged in
    Given I am a logged in administrator

  Scenario: Quick-search for a user that doesn't exist
    When I search for user "jake"
    Then I should see "No accounts found"

  Scenario: Quick-search for a user that exists
    When I search for user "jack"
    Then I should see "Example Jack"

  Scenario: Quick-search for a group that doesn't exist
    When I search for group "logicians"
    Then I should see "No such user group"

  Scenario: Quick-search for a group that exists
    When I search for group "logistics"
    Then I should see "Logistics Team"

  @screenshots
  Scenario: Load the quick search page
    Then I am on the "quick-search" screen

