*** Settings ***
Resource				../Common.robot
Suite Setup			Secretary Login	admin	1234
Suite Teardown	Close Browser

*** Test Cases ***
Admin User Can Login
	User is logged in		admin	

