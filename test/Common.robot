*** Settings ***
Documentation     An example resource file
Library           OperatingSystem
Library           Selenium2Library
Library           RequestsLibrary
Library           String

*** Variables ***
${HOST}           cards.leinsterhockey.ie
${BASE}           https://${HOST}
${LOGIN URL}      ${BASE}/Login
${WELCOME URL}    ${BASE}/welcome.html
#${BROWSER}       headlesschrome
${debug}          yes

*** Keywords ***
Login
    [Arguments]           ${username}     ${password}
    Open Chrome                        
    Go To                           ${LOGIN URL}
    #Click Element                   xpath=//div[@id='cookie-consent']/button        
    Input Text                      name=user         ${username}
    Input Text                      name=pin          ${password}
    Click Element                   xpath=//form[@id='login']/button
    Wait Until Page Contains Element        id:user

Secretary Login
    [Arguments]    				${username}     ${password}
    Login                 ${username}     ${password}
    #Open Chrome                        
    #Go To                 ${LOGIN URL}
    #Click Element         xpath=//div[@id='cookie-consent']/button        
    #Click Element         xpath=//a[@data-site='test']
    #Click Link            Secretary Login
    #Input Text            name=user       ${username}
    #Input Text            name=pin    		${pin}
    #Click Element         xpath=//form[@id='login']/button

User is logged in        
    [Arguments]           ${username}
    Element Should Contain        id:user        ${username}

Select Player
    [Arguments]           ${player}
    ${name}=              Get Element Attribute        xpath=//tr[contains(@data-name,'${player}')]    data-name
    Execute Javascript    window.jQuery("[data-name='${name}']")[0].scrollIntoView(true);
    Execute Javascript    window.scrollBy(0, -150);
    Sleep                 2s
		#Wait Until Element Is Visible		jquery=[data-name='${name}']
    Click Element         jquery=[data-name='${name}']
		Execute Javascript    window.scrollTo(0, 0);

Check Player
    [Arguments]            ${player}        ${class}
    ${attr}                    Get Element Attribute        xpath=//tr[contains(@data-name,'${player}')]    class
    Should Be Equal    ${attr}        ${class}        Player ${player} not ${class}

Go To Matches
    Go To                           http://${HOST}/cards/ui/
    Run Keyword And Ignore Error    Toggle Menu
    Sleep                           1 second
    Click Element                   link=Matches

Toggle Menu
    Click Element        css:.navbar-toggler

Submit Team
    Sleep                                                        2 seconds
    Execute Javascript                            scrollTo(0,0)
    Click Element                                        partial link=Submit Team
    Wait Until Element Is Visible        matchcard-home
    Comment                                                    Selecting Players

Submit Card
    [Arguments]            ${umpire}        ${score}
    Execute Javascript                            scrollTo(0,0)
    #Sleep                        6 seconds
		Wait Until Element Is Visible			css:#submit-matchcard .btn-success
    Input Text            umpire-box        ${umpire}
    Input Text            score-box            ${score}
    Click Element        jquery=#submit-form .btn-success

Reset Card    
    [Arguments]          ${fixtureid}
    ${auth}=             Create List    testadmin       password
    Create Session       cards          ${BASE}    auth=${auth}     verify=true
    DELETE On Session    cards          url=/cardapi?id=${fixtureid}

Open Card
    [Arguments]       ${cardkey}
    Go To Matches
    Sleep             1s
    Click Element     xpath=//tr[@data-key='${cardkey}']/*
    Sleep             1s

Open Chrome
    Register Keyword To Run On Failure        NOTHING
    ${chrome_options}=    Evaluate    sys.modules['selenium.webdriver'].ChromeOptions()    sys, selenium.webdriver
    #Call Method    ${chrome_options}    add_argument    --disable-extensions
    Call Method    ${chrome_options}    add_argument    headless
    #Call Method    ${chrome_options}    add_argument    --disable-gpu
    #Call Method    ${chrome_options}    add_argument    --no-sandbox
    #Run Keyword If  '${debug}' == 'no'   Call Method     ${chrome_options}    add_argument     headless
    Call Method     ${chrome_options}    add_argument     disable-gpu
    Create Webdriver    Chrome    options=${chrome_options}

