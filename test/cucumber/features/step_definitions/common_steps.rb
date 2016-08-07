Then(/^I should see "([^"]*)"$/) do |text|
  expect(page).to have_content(text)
end

Then(/^I am on the "([^"]*)" screen$/) do |name|
  $screen_resolutions.each do |key, options| 
    Capybara.page.current_window.resize_to(options[:width], options[:height])
    page.driver.save_screenshot("#{name}-#{key}.png")
  end
end

