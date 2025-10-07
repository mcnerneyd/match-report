*** Settings ***
Resource				../Common.robot
Suite Setup			Secretary Login		admin   	password
Suite Teardown	Close Browser

*** Test Cases ***
Admin User Can Login
	User is logged in		admin	

