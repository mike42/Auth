# Deployment-specific variables
$app_base = "https://{{ inventory_hostname }}/"

$creds = {
    :admin => {:login => '{{ test_admin_name }}', :pass => '{{ test_admin_password }}'},
    :user => {:login => '{{ test_user_name }}', :pass => '{{ test_user_password }}'},
    :assistant => {:login => '{{ test_assistant_name }}', :pass => '{{ test_assistant_password }}'}
}

