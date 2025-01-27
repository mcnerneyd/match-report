*** Variables ***
${card_key}     test.testdivision1.aardvarks1.bears1

*** Settings ***
Resource			../Common.robot
Suite Setup         Login    Aardvarks    1102
Test Setup			Open Card For View		${card_key}
Suite Teardown	    Close Browser

*** Test Cases ***
Guest Can Add Note To Card
    Click Link      partial link:Add Note
    Wait Until Element Is Visible    css:#add-note
    Click Element   css:#add-note textarea
    Input Text      css:#add-note textarea    This is a note, of sorts, from guest
    Sleep   2s
    Click Button    css:#add-note .btn-success
    Wait Until Element Is Not Visible    css:#add-note
    Sleep    1s
    Verify Card     Other "This is a note, of sorts, from guest"

Guest Can Add Signature To Card
    Fail            msg=Card needs to exist

*** Keywords ***
Open Card For View
	[Arguments]			${fixtureid}
	Reset Card		    ${fixtureid}
	Open Card			${fixtureid}
    Click Link          css:#logout
	Open Card			${fixtureid}

Player Menu
	[Arguments]			${player}
	Click Element		xpath=//tr[@data-name='${player}']

Click Menu
	[Arguments]		${text}
	Click Element		xpath=//*[contains(text(), '${text}')]

Submit Card
	Click Link			link:Submit Card
	Sleep						1s
	Click Link			jquery:#submit-matchcard a.btn-success		
	Click Button		jquery:#submit-matchcard button.btn-success		

Verify Card
	[Arguments]			${description}
	Go To						${BASE}/Report/Card?key=${card_key}
	Comment					${description}
	Page Should Contain Element		xpath://tr[@data-description='${description}']

