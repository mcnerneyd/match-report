# vim:et:ts=3:sw=3
*** Settings ***
Resource         ../Common.robot
Suite Setup      Login  Aardvarks  1111
Test Setup       Create Card With Player    6
Suite Teardown   Close Browser

*** Test Cases ***
User Can Add Goal To Player
  Player Menu    Jackeline GOSHA
  Click Button   Add Goal
  Submit Card
  Verify Card    Scored Jackeline GOSHA Aardvarks 1
  
User Can Clear Goals From Player
  Player Menu    Jackeline GOSHA
  Click Button   Add Goal
  Sleep          1s
  Player Menu    Jackeline GOSHA
  Click Button   Add Goal
  Sleep          1s
  Player Menu    Jackeline GOSHA
  Click Button   Clear Goals
  Submit Card
  Verify Card    Scored Jackeline GOSHA Aardvarks 0
  
User Can Add Technical Yellow Card To Player
  Player Menu    Jackeline GOSHA
  Click Menu     Technical - Breakdown
  Submit Card
  Verify Card    Yellow Card Jackeline GOSHA Aardvarks Technical - Breakdown
  
User Can Add Physical Yellow Card To Player
  Player Menu    Jackeline GOSHA
  Click Menu     Physical - Tackle
  Submit Card
  Verify Card    Yellow Card Jackeline GOSHA Aardvarks Physical - Tackle
  
User Can Add Red Card To Player
  Player Menu    Jackeline GOSHA
  Click Menu     Red Card
  Submit Card
  Verify Card    Red Card Jackeline GOSHA Aardvarks Red Card
  
User Can Clear Cards From Player
  Player Menu    Jackeline GOSHA
  Click Menu     Red Card
  Wait Until Element Is Not Visible  context-menu
  Player Menu    Jackeline GOSHA
  Click Menu     No Cards
  Submit Card
  Go To          http://cards.leinsterhockey.ie/public/Report/Card/6
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
  Sleep           1s
  Click Element   css:#player-name-selectized        # Activate selectize
  Click Element   css:div[data-value='Ai CRIBB']
  Click Button    css:#add-player-modal .btn-success
  Sleep           1s
  Verify Card     Played Ai CRIBB Aardvarks 

User Can Add Any Name To Card 
  Execute Javascript    window.jQuery("#submit-card .add-player")[0].scrollIntoView(true);
  Click Link      css:#submit-card .add-player
  Sleep           1s
  Click Element   css:#player-name-selectized        # Activate selectize
  Press Keys      css:#player-name-selectized    Nobody McNobodyFace
  Click Button    css:#add-player-modal .btn-success
  Sleep           1s
  Verify Card     Played Nobody MCNOBODYFACE Aardvarks 

User Can Add A Note To Card
  Click Link      partial link:Add Note
  Sleep           1s
  Click Element   css:#add-note textarea
  Input Text      css:#add-note textarea    This is a note, of sorts
  Click Button    css:#add-note .btn-success
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

Submit Card
  Sleep          1s
  Click Link     link:Submit Card
  Sleep          1s
  
  Input Text     jquery:#submit-matchcard [name=opposition-score]  2
  Input Text     jquery:#submit-matchcard [name=umpire]  billy umpire
  Click Link     jquery:#submit-matchcard a.btn-success    
  Click Button   jquery:#submit-matchcard button.btn-success    

Verify Card
  [Arguments]    ${description}
  Sleep		      1s
  Go To          http://cards.leinsterhockey.ie/public/Report/Card/6
  Comment        ${description}
  Page Should Contain Element    xpath://tr[@data-description='${description}']

