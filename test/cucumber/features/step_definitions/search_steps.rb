When(/^I search for user "([^"]*)"$/) do |user_name|
  fill_in 'uname', :with => user_name
  find('#accountselect button[type="submit"]').click
end

When(/^I search for group "([^"]*)"$/) do |group_name|
  find('#accountselect a[data-toggle="dropdown"]').click
  click_link('Group')
  fill_in 'gname', :with => group_name
  find('#groupselect button[type="submit"]').click
end

