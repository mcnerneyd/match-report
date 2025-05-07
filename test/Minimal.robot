# vim:et:ts=3:sw=3
*** Settings ***
Resource         Common.robot
Suite Setup      Login  mcnerneyd@gmail.com  badbadger
Test Setup       Create Card With Player    test.division1.aardvarks1.bears2
Suite Teardown   Close Browser

*** Test Cases ***
Just Start
