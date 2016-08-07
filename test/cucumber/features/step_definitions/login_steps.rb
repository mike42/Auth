Given(/^I open the login page$/) do
  visit $app_base
end

Given(/^I am a logged in administrator$/) do
  visit "#{$app_base}/admin/"
  if first('#inputUname').nil?
    print("AAA")
  end
  fill_in 'inputUname', :with => $creds[:admin][:login]
  fill_in 'inputPassword', :with => $creds[:admin][:pass]
  find('button[type="submit"]').click
end

