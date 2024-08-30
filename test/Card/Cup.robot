# vim:et:ts=3:sw=3
*** Settings ***
Resource         ../Common.robot
Suite Setup      Login  Aardvarks  1102
Test Setup       Create Card With Player    test.testcup.aardvarks1.bears1
Suite Teardown   Close Browser

*** Test Cases ***
Cup Matches Cannot Be Drawn
  Player Menu    Jackeline GOSHA
  Click Button   Add Goal
  Sleep          1s
  Player Menu    Jackeline GOSHA
  Click Button   Add Goal
  Sleep          1s
  Click Link     link:Submit Card
  Sleep          1s
  Input Text     jquery:#submit-matchcard [name=opposition-score]  2
  Input Text     jquery:#submit-matchcard [name=umpire]  billy umpire
  Click Link     jquery:#submit-matchcard a.btn-success
  Element Should Not Be Visible		jquery:#submit-matchcard button.btn-success
  Input Text     jquery:#submit-matchcard [name=opposition-score]  3
  Click Link     jquery:#submit-matchcard a.btn-success
  Element Should Be Visible		jquery:#submit-matchcard button.btn-success

*** Keywords ***
Create Card With Player
  [Arguments]    ${fixtureid}
  Reset Card     ${fixtureid}
  Open Card      ${fixtureid}
  Select Player  Jackeline GOSHA
  Submit Team

Player Menu
  [Arguments]    ${player}
  Click Element  xpath=//tr[@data-name='${player}']

Click Menu
  [Arguments]    ${text}
  Click Element  xpath=//*[contains(text(), '${text}')]

