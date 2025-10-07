*** Settings ***
Documentation     An example resource file (updated for SeleniumLibrary)
Library           OperatingSystem
Library           SeleniumLibrary
Library           RequestsLibrary
Library           String

*** Variables ***
${HOST}           cards.leinsterhockey.ie
${BASE}           https://${HOST}
${LOGIN URL}      ${BASE}/Login
${WELCOME URL}    ${BASE}/welcome.html
${debug}          yes

*** Keywords ***
Login
    [Arguments]           ${username}     ${password}
    Open Chrome
    Go To                 ${LOGIN URL}
    Input Text            name=user         ${username}
    Input Text            name=pin          ${password}
    Click Element         xpath=//form[@id='login']/button
    Wait Until Page Contains Element    id:user

Logout
    Go To                 http://${HOST}/Login

Secretary Login
    [Arguments]           ${username}     ${password}
    Login                 ${username}     ${password}

User is logged in
    [Arguments]           ${username}
    Element Should Contain    id:user        ${username}

Select Player
    [Arguments]           ${player}
    ${name}=              Get Element Attribute    xpath=//tr[contains(@data-name,'${player}')]    data-name
    Execute Javascript    window.jQuery("[data-name='${name}']")[0].scrollIntoView(true);
    Execute Javascript    window.scrollBy(0, -150);
    Sleep                 2s
    Click Element         jquery=[data-name='${name}']
    Execute Javascript    window.scrollTo(0, 0);

Check Player
    [Arguments]           ${player}    ${class}
    ${attr}=              Get Element Attribute    xpath=//tr[contains(@data-name,'${player}')]    class
    Should Be Equal       ${attr}    ${class}    Player ${player} not ${class}

Go To Matches
    Go To                 http://${HOST}/cards/ui/
    Run Keyword And Ignore Error    Toggle Menu
    Sleep                1 second
    Click Element        link=Matches

Toggle Menu
    Click Element        css:.navbar-toggler

Submit Team
    Sleep                2 seconds
    Execute Javascript   scrollTo(0,0)
    Click Element        partial link=Submit Team
    Wait Until Element Is Visible    matchcard-home

Submit Card
    [Arguments]          ${umpire}    ${score}
    Execute Javascript   scrollTo(0,0)
    Wait Until Element Is Visible    css:#submit-matchcard .btn-success
    Input Text           umpire-box     ${umpire}
    Input Text           score-box      ${score}
    Click Element        jquery=#submit-form .btn-success

Reset Card
    [Arguments]          ${fixtureid}
    ${auth}=             Create List    testadmin    password
    Create Session       cards    ${BASE}    auth=${auth}    verify=true
    DELETE On Session    cards    url=/cardapi?id=${fixtureid}

Open Card
    [Arguments]          ${cardkey}
    Go To Matches
    Sleep               1s
    Click Element       xpath=//tr[@data-key='${cardkey}']/*
    Sleep               1s

Open Chrome
    Register Keyword To Run On Failure    NONE
    ${options}=    Evaluate    sys.modules['selenium.webdriver'].ChromeOptions()    sys, selenium.webdriver
    Call Method           ${options}    add_argument    --disable-gpu
    Run Keyword If        '${debug}' == 'no'    Call Method    ${options}    add_argument    headless
    ${headless_arg}       Set Variable      headless=new
    Call Method    ${options}    add_argument    --${headless_arg}
    Open Browser          about:blank    headlesschrome    ${options}
    #Open Browser          about:blank    chrome    ${options}
    Set Selenium Implicit Wait    5
