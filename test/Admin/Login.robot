*** Settings ***
Resource				../Common.robot
Suite Setup			Secretary Login		administrator@nomail.com		password
Suite Teardown	Close Browser

*** Test Cases ***
Admin User Can Login
	User is logged in		admin	

