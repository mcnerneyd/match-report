*** Settings ***
Resource				../Common.robot
Suite Setup			Secretary Login		admin		password
Suite Teardown	Close Browser

*** Test Cases ***
Admin User Can Add Any CSV Registration

Admin User Can Add CSV Registration

Admin User Can Add XLS Registration

Admin user Can Delete A Registration

Admin User Can Add Captain

Admin User Can Add Secretary

Admin User Can Add Umpire

Admin User Can Add Admin User

Admin User Can Add New Competition Regex

Admin User Can Add New Club Regex

*** Keywords ***


