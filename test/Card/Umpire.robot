*** Settings ***
Resource			../Common.robot
Test Setup       Create Card With Player    test.division1.aardvarks1.bears2
Suite Teardown		Close Browser

*** Test Cases ***
Not An Official Umpire

Umpire Can Add Technical Yellow Card To Player
	Player Menu		Jackeline GOSHA
	Click Menu		Technical - Breakdown
	Wait Until Element Is Not Visible	context-menu
	Submit Card
	Verify Card		Yellow Card Jackeline GOSHA Aardvarks Technical - Breakdown
	
Umpire Can Add Physical Yellow Card To Player
	Player Menu		Jackeline GOSHA
	Click Menu		Physical - Tackle
	Wait Until Element Is Not Visible	context-menu
	Submit Card
	Verify Card		Yellow Card Jackeline GOSHA Aardvarks Physical - Tackle
	
Umpire Can Add Red Card To Player
	Player Menu		Jackeline GOSHA
	Click Menu		Red Card
	Wait Until Element Is Not Visible	context-menu
	Submit Card
	Verify Card		Red Card Jackeline GOSHA Aardvarks Red Card
	
Umpire Can Clear Cards From Player
	Player Menu		Jackeline GOSHA
	Click Menu		Red Card
	Wait Until Element Is Not Visible	context-menu
	Player Menu		Jackeline GOSHA
	Click Menu		No Cards
	Submit Card
	Sleep			2s
	Go To			${BASE}/Report/Card?key=test.division1.aardvarks1.bears2
	Page Should Not Contain Element		xpath://tr[@data-description='Red Card Jackeline GOSHA Aardvarks Red Card']
	
Umpire Can Add Note To Card
    Click Link        partial link:Add Note
    Sleep              1s
    Click Element      css:#add-note textarea
    Input Text        css:#add-note textarea    This is a note, of sorts
    Click Button      css:#add-note .btn-success
    Sleep              1s
    Verify Card        Other "This is a note, of sorts"


Umpire Can Sign Card

*** Keywords ***
Create Card With Player
	[Arguments]		${fixtureid}
	Login			Aardvarks		1111
	Reset Card		${fixtureid}
	Open Card		${fixtureid}
	Select Player	Jackeline GOSHA
	Submit Team
	Close Browser
	Login			Andrew Amberman		2222
	Go To			${BASE}/cards/index.php?site=test&controller=card&action=get&fid=${fixtureid}
	Click Link			Yes

Player Menu
	[Arguments]			${player}
	Click Element		xpath=//tr[@data-name='${player}']

Click Menu
	[Arguments]			${text}
	Click Element		xpath=//*[contains(text(), '${text}')]

Submit Card
	Click Link			link:Submit Card
	Sleep				1s
    Input Text          jquery:#submit-matchcard [name=opposition-score]  2
    Input Text          jquery:#submit-matchcard [name=umpire]  billy umpire
	Click Link			jquery:#submit-matchcard a.btn-success
	Click Button		jquery:#submit-matchcard button.btn-success

Verify Card
	[Arguments]			${description}
	Sleep				2s
	Go To				${BASE}/Report/Card/?key=test.division1.aardvarks1.bears2
	Comment				${description}
	Page Should Contain Element		xpath://tr[@data-description='${description}']

