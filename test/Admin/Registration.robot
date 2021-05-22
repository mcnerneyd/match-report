*** Settings ***
Resource				../Common.robot
Suite Setup			Secretary Login		administrator@nomail.com		password
Test Setup			Go To Registration Page		
Suite Teardown	Close Browser

*** Test Cases ***
User Must Click Acceptance Checkbox
	Click Element									id:upload-button
	Element Should Be Disabled		css:#upload-registration button[type=submit]
	Click Element									id:upload-permission-checkbox
	Element Should Be Enabled			css:#upload-registration button[type=submit]
	Click Element									id:upload-permission-checkbox
	Element Should Be Disabled		css:#upload-registration button[type=submit]

*** Keywords ***
Go To Registration Page
	Go To							http://${HOST}/registration

