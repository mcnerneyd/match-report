*** Comments ***
# vim:et:ts=3:sw=3

*** Settings ***
Resource            ../Common.robot

Suite Setup         Login    Aardvarks    1102
Suite Teardown      Close Browser
Test Setup          Create Card With Player    ${card_key}

*** Variables ***
${card_key}     test.testdivision1.aardvarks1.bears1

*** Test Cases ***
User Can Add Goal To Player
    Context Menu    Jackeline GOSHA    Add Goal
    Verify Card    Scored Jackeline GOSHA Aardvarks 1

User Can Clear Goals From Player
    Context Menu    Jackeline GOSHA    Add Goal
    Context Menu    Jackeline GOSHA    Add Goal
    Context Menu    Jackeline GOSHA    Clear Goals
    Verify Card    Scored Jackeline GOSHA Aardvarks 0

User Can Add Technical Yellow Card To Player
    Add Penalty    Jackeline GOSHA    Technical - Breakdown
    Verify Card    Yellow Card Jackeline GOSHA Aardvarks Technical - Breakdown

User Can Add Physical Yellow Card To Player
    Add Penalty    Jackeline GOSHA    Physical - Tackle
    Verify Card    Yellow Card Jackeline GOSHA Aardvarks Physical - Tackle

User Can Add Red Card To Player
    Add Penalty    Jackeline GOSHA    Red Card
    Verify Card    Red Card Jackeline GOSHA Aardvarks Red Card

User Can Clear Cards From Player
    Add Penalty    Jackeline GOSHA    Red Card
    Add Penalty    Jackeline GOSHA    Clear Cards
    Go To    ${BASE}/Report/Card?key=${card_key}
    Page Should Not Contain Element    xpath://tr[@data-description='Red Card Jackeline GOSHA Aardvarks Red Card']

User Can Set Player Number
    Player Menu    Jackeline GOSHA
    Input Text    name:shirt-number    12
    Click Button    css:#set-number button
    Wait Until Element Is Not Visible    css:#context-menu
    Verify Card    home GOSHA, Jackeline 12

User Can Add Specific Player To Card
    Execute Javascript    window.jQuery("#submit-card .add-player")[0].scrollIntoView(true);
    Click Link    css:#submit-card .add-player
    Wait Until Element Is Visible    css:#player-name-selectized
    Click Element    css:#player-name-selectized    # Activate selectize
    Click Element    css:div[data-value='Ai CRIBB']
    Click Button    css:#add-player-modal .btn-success
    Wait Until Element Is Not Visible    css:#add-player-modal
    Verify Card    Played Ai CRIBB Aardvarks

User Can Add Any Name To Card
    Execute Javascript    window.jQuery("#submit-card .add-player")[0].scrollIntoView(true);
    Click Link            css:#submit-card .add-player
    Wait Until Element Is Visible    css:#add-player-modal
    Sleep                 1s
    Click Element         css:#player-name-selectized    # Activate selectize
    Sleep                 1s
    Press Keys            css:#player-name-selectized    BACK_SPACE    Nobody McNobodyFace
    Sleep                 1s
    Click Button          css:#add-player-modal .btn-success
    Wait Until Element Is Not Visible    css:#add-player-modal
    Verify Card           Played Nobody MCNOBODYFACE Aardvarks

User Can Add A Note To Card
    Click Link    partial link:Add Note
    Wait Until Element Is Visible    css:#add-note
    Click Element    css:#add-note textarea
    Input Text    css:#add-note textarea    This is a note, of sorts
    Click Button    css:#add-note .btn-success
    Wait Until Element Is Not Visible    css:#add-note
    Sleep    1s
    Verify Card    Other Aardvarks "This is a note, of sorts"

Opposition Score Must Be Provided
    Click Link    link:Submit Card
    Sleep    1s
    Input Text    jquery:#submit-matchcard [name=umpire]    billy umpire
    Click Link    jquery:#submit-matchcard a.btn-success
    Element Should Not Be Visible    jquery:#submit-matchcard button.btn-success
    Input Text    jquery:#submit-matchcard [name=opposition-score]    2
    Click Link    jquery:#submit-matchcard a.btn-success
    Element Should Be Visible    jquery:#submit-matchcard button.btn-success

User Can Submit Card
    Context Menu    Jackeline GOSHA    Add Goal
    Submit Card
    Verify Card    Scored Jackeline GOSHA Aardvarks 1


*** Keywords ***
Create Card With Player
    [Arguments]    ${fixtureid}
    Reset Card    ${fixtureid}
    Open Card    ${fixtureid}
    Select Player    Jackeline GOSHA
    Submit Team

Player Menu
    [Arguments]    ${player}
    Execute Javascript    $('#user').hide()
    Click Element    xpath=//tr[@data-name='${player}']

Click Menu
    [Arguments]    ${text}
    Click Element    xpath=//*[contains(text(), '${text}')]

Submit Card
    Click Link       link:Submit Card
    Wait Until Element Is Visible    jquery:#submit-matchcard a.btn-success
    Input Text       jquery:#submit-matchcard [name=opposition-score]    2
    Input Text       jquery:#submit-matchcard [name=umpire]    billy umpire
    Sleep            1s
    Click Link       jquery:#submit-matchcard a.btn-success
    Wait Until Element Is Visible    jquery:#submit-matchcard button.btn-success
    Click Button     jquery:#submit-matchcard button.btn-success

Add Penalty
    [Arguments]    ${player}    ${action}
    Player Menu    ${player}
    Wait Until Element Is Visible    css:#context-menu
    Click Menu    ${action}
    Wait Until Element Is Not Visible    css:#context-menu

Context Menu
    [Arguments]    ${player}    ${action}
    Player Menu    ${player}
    Wait Until Element Is Visible    css:#context-menu
    Click Button    ${action}
    Wait Until Element Is Not Visible    css:#context-menu

Verify Card
    [Arguments]    ${description}
    Sleep   1s
    Go To    ${BASE}/Report/Card?key=${card_key}
    Comment    ${description}
    Page Should Contain Element    xpath://tr[@data-description='${description}']

Wait For Reload
    Wait Until Element Is Visible    css:#user
