require 'rubygems'
require 'capybara/cucumber'
require 'selenium-webdriver'
require 'capybara-screenshot/cucumber'

# Sizes and names of screenshots to collect in @screenshot scenarios
$screen_resolutions = {
    :mobile => {:width => 360, :height => 640},
    :desktop => {:width => 1024, :height => 768},
    :tablet => {:width => 768, :height => 1024}
  }

# Selenium/Firefox
Capybara.register_driver :selenium do |app|
  profile = Selenium::WebDriver::Firefox::Profile.new
  Capybara::Selenium::Driver.new(app, :profile => profile)
end

# Add delays to actions for demonstrating them
module ::Selenium::WebDriver::Remote
  class Bridge
    def execute(*args)
      result = raw_execute(*args)['value']
      #sleep 0.5
      result
    end
  end
end

Capybara.default_driver = :selenium
