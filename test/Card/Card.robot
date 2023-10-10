# vim:et:ts=3:sw=3
*** Settings ***
Resource         ../Common.robot
Suite Setup      Login  Aardvarks  1112
Test Setup       Create Card With Player    test.division1.aardvarks1.bears2
Suite Teardown   Close Browser

*** Test Cases ***
User Can Add Goal To Player
  Player Menu    Jackeline GOSHA
  Click Button   Add Goal
  Wait Until Element Is Not Visible    css:#context-menu
  Verify Card    Scored Jackeline GOSHA Aardvarks 1
  
User Can Clear Goals From Player
  Player Menu    Jackeline GOSHA
  Click Button   Add Goal
  Wait Until Element Is Not Visible    css:#context-menu
  Player Menu    Jackeline GOSHA
  Click Button   Add Goal
  Wait Until Element Is Not Visible    css:#context-menu
  Player Menu    Jackeline GOSHA
  Click Button   Clear Goals
  Wait Until Element Is Not Visible    css:#context-menu
  Verify Card    Scored Jackeline GOSHA Aardvarks 0
  
User Can Add Technical Yellow Card To Player
  Player Menu    Jackeline GOSHA
  Click Menu     Technical - Breakdown
  Wait Until Element Is Not Visible    css:#context-menu
  Verify Card    Yellow Card Jackeline GOSHA Aardvarks Technical - Breakdown
  
User Can Add Physical Yellow Card To Player
  Player Menu    Jackeline GOSHA
  Click Menu     Physical - Tackle
  Wait Until Element Is Not Visible    css:#context-menu
  Verify Card    Yellow Card Jackeline GOSHA Aardvarks Physical - Tackle
  
User Can Add Red Card To Player
  Player Menu    Jackeline GOSHA
  Click Menu     Red Card
  Wait Until Element Is Not Visible    css:#context-menu
  Verify Card    Red Card Jackeline GOSHA Aardvarks Red Card
  
User Can Clear Cards From Player
  Player Menu    Jackeline GOSHA
  Click Menu     Red Card
  Wait Until Element Is Not Visible  css:#context-menu
  Player Menu    Jackeline GOSHA
  Click Menu     Clear Cards
  Wait Until Element Is Not Visible  css:#context-menu
  Go To          ${BASE}/Report/Card/test.division1.aardvarks1.bears2
  Page Should Not Contain Element    xpath://tr[@data-description='Red Card Jackeline GOSHA Aardvarks Red Card']
  
User Can Set Player Number
  Player Menu    Jackeline GOSHA
  Input Text     name:shirt-number    12
  Click Button   css:#set-number button
  Wait Until Element Is Not Visible    css:#context-menu
  Verify Card    home GOSHA, Jackeline 12

User Can Add Specific Player To Card 
  Execute Javascript    window.jQuery("#submit-card .add-player")[0].scrollIntoView(true);
  Click Link      css:#submit-card .add-player
  Wait Until Element Is Visible    css:#player-name-selectized
  Click Element   css:#player-name-selectized        # Activate selectize
  Click Element   css:div[data-value='Ai CRIBB']
  Click Button    css:#add-player-modal .btn-success
  Wait Until Element Is Not Visible    css:#add-player-modal
  Sleep           1s
  Verify Card     Played Ai CRIBB Aardvarks 

User Can Add Any Name To Card 
  Execute Javascript    window.jQuery("#submit-card .add-player")[0].scrollIntoView(true);
  Click Link      css:#submit-card .add-player
  Wait Until Element Is Visible    css:#add-player-modal
  Click Element   css:#player-name-selectized        # Activate selectize
  Press Keys      css:#player-name-selectized    Nobody McNobodyFace
  Click Button    css:#add-player-modal .btn-success
  Wait Until Element Is Not Visible    css:#add-player-modal
  Sleep           1s
  Verify Card     Played Nobody MCNOBODYFACE Aardvarks 

User Can Add A Note To Card
  Click Link      partial link:Add Note
  Wait Until Element Is Visible    css:#add-note
  Click Element   css:#add-note textarea
  Input Text      css:#add-note textarea    This is a note, of sorts
  Click Button    css:#add-note .btn-success
  Wait Until Element Is Not Visible    css:#add-note
  Sleep           1s
  Verify Card     Other Aardvarks "This is a note, of sorts"

Opposition Score Must Be Provided
  Sleep          1s
  Click Link     link:Submit Card
  Sleep          1s
  Input Text     jquery:#submit-matchcard [name=umpire]  billy umpire
  Click Link     jquery:#submit-matchcard a.btn-success
  Element Should Not Be Visible		jquery:#submit-matchcard button.btn-success
  Input Text     jquery:#submit-matchcard [name=opposition-score]  2
  Click Link     jquery:#submit-matchcard a.btn-success
  Element Should Be Visible		jquery:#submit-matchcard button.btn-success

User Can Submit Card
  Player Menu    Jackeline GOSHA
  Click Button   Add Goal
  Wait Until Element Is Not Visible    css:#context-menu
  Submit Card
  Verify Card    Scored Jackeline GOSHA Aardvarks 1

*** Keywords ***
Create Card With Player
  [Arguments]    ${fixtureid}
  Reset Card     ${fixtureid}
  Open Card      ${fixtureid}
  Select Player  Jackeline GOSHA
  Submit Team

Player Menu
  [Arguments]    ${player}
  Execute Javascript   $('#user').hide()
  Click Element  xpath=//tr[@data-name='${player}']

Click Menu
  [Arguments]    ${text}
  Click Element  xpath=//*[contains(text(), '${text}')]

Submit Card
  Click Link     link:Submit Card
  Wait Until Element Is Visible   jquery:#submit-matchcard button.btn-success    
  Input Text     jquery:#submit-matchcard [name=opposition-score]  2
  Input Text     jquery:#submit-matchcard [name=umpire]  billy umpire
  Click Link     jquery:#submit-matchcard a.btn-success    
  Click Button   jquery:#submit-matchcard button.btn-success    

Verify Card
  [Arguments]    ${description}
  Go To          ${BASE}/Report/Card?key=test.division1.aardvarks1.bears2
  Comment        ${description}
  Page Should Contain Element    xpath://tr[@data-description='${description}']

Wait For Reload
   Wait Until Element Is Visible    css:#user
