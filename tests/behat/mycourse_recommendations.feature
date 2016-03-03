@block @block_mycourse_recommendations
Feature: Not personalizable course

  Scenario: Adding block mycourse_recommendations to a not personalizable course
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | c1        |
    And the following "course enrolments" exist:
      | user  | course | role           |
      | admin | c1     | editingteacher |
    When I log in as "admin"
    And I follow "Course 1"
    And I turn editing mode on
    And I add the "MYCOURSE Recommendations" block
    Then I should see "This course won't receive recommendations because it is not personalizable."
