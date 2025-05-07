# vim:et:ts=3:sw=3
*** Settings ***
Resource          ../Common.robot
Test Teardown     Close Browser

*** Test Cases ***
Initialize
   Initialize Test Site

   Login          admin@test     password

   # Create Competitions
   Go To                         ${BASE}/competitions
   Click Link                    link:Add Competition
   Select From List By Label     name:section                  test
   Input Text                    name:competitionname          Test Division 1
   Select Radio Button           option_type                   league
   Input Text                    name:competitioncode          TD1
   Input Text                    name:competition-teamsize     11
   Click Button                  Add

   Click Link                    link:Add Competition
   Select From List By Label     name:section            test
   Input Text                    name:competitionname    Test Cup A
   Select Radio Button           option_type             cup
   Input Text                    name:competitioncode    TCA
   Click Button                  Add

   # Create Clubs
   Go To                     ${BASE}/clubs
   Click Link                link:Add Club
   Input Text                name:clubname           Aardvarks
   Input Text                name:clubcode           AA
   Click Button              Add

   Click Link                link:Add Club
   Input Text                name:clubname           Bears
   Input Text                name:clubcode           BB
   Click Button              Add

   Click Link                link:Add Club
   Input Text                name:clubname           Camels
   Input Text                name:clubcode           CC
   Click Button              Add

   # Create Users
   Go To Until                   ${BASE}/users     id:add-user-button
   Click Button                  id:add-user-button
   Click Link                    id:add-user
   Select From List By Label     name:club            Aardvarks
   Select From List By Label     name:section         test
   Click Button                  id:create-user

   Go To Until                   ${BASE}/users    id:add-user-button
   Click Button                  id:add-user-button
   Click Link                    id:add-user
   Select From List By Label     name:club            Bears
   Select From List By Label     name:section         test
   Click Button                  id:create-user

   Go To Until                   ${BASE}/users    id:add-user-button
   Click Button                  id:add-user-button
   Click Link                    id:add-secretary
   Input Text                    name:email           aardvarks@test
   Select From List By Label     name:club            Aardvarks
   Select From List By Label     name:section         test
   Click Button                  id:create-user

   # Configure Fixtures
   Go To Until                   ${BASE}/Admin/Config    id:section-select
   Select From List By Label     id:section-select            test
   Click Link                    link:Registration
   Select Checkbox               name:allow_registration

   Click Link                    link:Fixtures
   ${fixtures}                   Get File          FullTest/fixtures.txt
   Input Text                    name:fixtures     ${fixtures}
   ${pattcomp}                   Get File          FullTest/pattcomp.txt
   Input Text                    name:fixescompetition     ${pattcomp}
   ${pattteam}                   Get File          FullTest/pattteam.txt
   Input Text                    name:fixesteam    ${pattteam}
   Click Button                  Save

   Go To                         ${BASE}/fixtures?flush=true
   Go To                         ${BASE}/competitions?rebuild=true

Upload Registrations
   Login          aardvarks@test     password

   Go To                         ${BASE}/Registration
   Select From List By Label     name:section               test
   Click Link                    id:upload-button
   Choose File                   name:file                  ${CURDIR}/aardvarks.csv
   Select Checkbox               id:upload-permission-checkbox
   Click Button                  id:registration-save-changes

   Wait Until Element Contains   css:#registration-table tbody tr td:nth-child(1)    aardvarks
   Wait Until Element Contains   css:#registration-table tbody tr td:nth-child(4)    18203e049ffdc6bfacc3beb82b91023e

   Click Link                    partial link:View
   Wait Until Element Contains   css:#registration-table tbody tr:nth-child(1) td.player     Jeffie HOUCK
   Element Should Contain        css:#registration-table tbody tr:nth-child(6) td.player     Rhona CHONG
   Element Should Contain        css:#registration-table tbody tr:nth-child(14) td.player    Chante CLIFFORD
   Element Should Be Visible     css:#registration-table tbody tr:nth-child(14) img 
   Element Should Contain        css:#registration-table tbody tr:nth-child(17) td.player    Alyse FLEWELLING

Upload Registrations Admin
   Login       admin@test           password

   Go To                         ${BASE}/Registration
   Select From List By Label     name:club                  Bears
   Select From List By Label     name:section               test

   Click Link                    id:upload-button
   Choose File                   name:file                  ${CURDIR}/bears.xlsx
   Select Checkbox               id:upload-permission-checkbox
   Click Button                  id:registration-save-changes

Create Card Aardvarks
   Login          Aardvarks       0000

   Wait Until Element Is Visible    css:tr[data-key='test.testdivision1.aardvarks1.bears1']
   Click Element                    css:tr[data-key='test.testdivision1.aardvarks1.bears1']

   Wait Until Element Is Visible    css:tr.player
   Select Player                    Ai CRIBB
   Select Player                    Cathi FORAKER
   Select Player                    Emmy HOLYFIELD
   Select Player                    Isabella STRINGFELLOW
   Select Player                    Susy ANDREPONT
   Select Player                    Doris KIEFFER
   Select Player                    Hortense HELMUTH
   Select Player                    Tamie ADDISON
   Select Player                    Isabella STRINGFELLOW

   Click Link                       link:Submit Team

   # Add two goals
   Click Element                    css:tr.player[data-name='Isabella STRINGFELLOW']
   Click Button                     id:add-goal
   Context Gone
   Click Element                    css:tr.player[data-name='Isabella STRINGFELLOW']
   Click Button                     id:add-goal
   Context Gone

   # Add a red card
   Click Element                    css:tr.player[data-name='Tamie ADDISON']
   Wait Until Element Is Visible    id:card-add
   Select From List By Label        id:card-add               Red Card

   # Add a yellow card
   Click Element                    css:tr.player[data-name='Tamie ADDISON']
   Wait Until Element Is Visible    id:card-add
   Select From List By Label        id:card-add               Technical - Breakdown

   # Add a green card
   Click Element                    css:tr.player[data-name='Tamie ADDISON']
   Wait Until Element Is Visible    id:card-add
   Select From List By Label        id:card-add               Green Card

   # Set Roles
   Click Element                    css:tr.player[data-name='Doris KIEFFER']
   Select Checkbox                  css:[data-role='C']
   Sleep    1s

   Click Element                    css:tr.player[data-name='Doris KIEFFER']
   Select Checkbox                  css:[data-role='G']
   Sleep    1s

   Click Element                    css:tr.player[data-name='Doris KIEFFER']
   Select Checkbox                  css:[data-role='M']
   Sleep    1s

   Click Element                    css:tr.player[data-name='Doris KIEFFER']
   Select Checkbox                  css:[data-role='P']
   Sleep    1s

   # Check

   # Remove goals
   Click Element                    css:tr.player[data-name='Isabella STRINGFELLOW']
   Click Button                     id:clear-goal

   Click Link                       id:submit-button
   Sleep    1s

   Input Text                       name:opposition-score      2
   Input Text                       name:umpire                Joe Umpire
   Click Link                       partial link:Sign
   Click Button                     css:.btn-success

Create Card Bears
   Login          Bears           0000

   Wait Until Element Is Visible    css:tr[data-key='test.testdivision1.aardvarks1.bears1']
   Click Element                    css:tr[data-key='test.testdivision1.aardvarks1.bears1']

   Wait Until Element Is Visible    css:tr.player
   Select Player                    Austin DOW
   Select Player                    Budd GOODERE
   Select Player                    Blaine RISEBROW
   Select Player                    Joceline ALFLAT
   Select Player                    Marietta GRESSER
   Select Player                    Sharyl FILIPYCHEV
   Select Player                    Drake SEAKING
   Select Player                    Catherin SEFTON
   Select Player                    Quillan CURNOCK
   Select Player                    Zitella WHYMARK
   Select Player                    Aila LIPPITT

   Click Link                       link:Submit Team

   Wait Until Element Is Visible    css:tr.player

   # Add two goals
   Click Element                    css:tr.player[data-name='Blaine RISEBROW']
   Click Button                     id:add-goal
   Context Gone
   Click Element                    css:tr.player[data-name='Drake SEAKING']
   Click Button                     id:add-goal
   Context Gone

   # Add a red card

   #Input Text                       id:player-name-selectized        Benjamin NEWPLAYER
   #Click Button                     css:.btn                       
   Click Link                       id:submit-button
   Sleep    1s

   Input Text                       name:opposition-score      1
   Input Text                       name:umpire                Gerry Umpire
   Click Link                       partial link:Sign
   Click Button                     css:.btn-success


*** Keywords ***
Close Context
   Click Button        css:button.close
   Wait Until Element Is Not Visible      id:context-menu
   
Context Gone
   Wait Until Element Is Not Visible      id:context-menu

Go To Until
  [Arguments]    ${target}   ${element}
  Sleep    1s
  Go To          ${target}
  Sleep    3s
  Wait Until Element Is Visible     ${element}

Select Player
  [Arguments]    ${name}
  Execute Javascript    window.jQuery("[data-name='${name}']")[0].scrollIntoView(true);
  Execute Javascript    window.scrollBy(0, -150);
  Sleep           1s
  Click Element                        css:tr[data-name='${name}']

  Execute Javascript    window.scrollTo(0, 0);

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

Initialize Test Site
    Create Session       cards     ${BASE}
    DELETE On Session    cards     url=/api/1.0/admin/test
