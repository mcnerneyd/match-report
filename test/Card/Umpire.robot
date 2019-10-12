*** Settings ***
Resource				../Common.robot
Suite Setup			Login		Andrew Amberman		2222
Test Setup			Create Card With Player		7
Suite Teardown	Close Browser

*** Test Cases ***
Not An Official Umpire

Umpire Can Add Technical Yellow Card To Player
	Player Menu		Jackeline GOSHA
	Click Menu		Technical - Breakdown
	Submit Card
	Verify Card		Yellow Card Jackeline GOSHA Aardvarks Technical - Breakdown
	
Umpire Can Add Physical Yellow Card To Player
	Player Menu		Jackeline GOSHA
	Click Menu		Physical - Tackle
	Submit Card
	Verify Card		Yellow Card Jackeline GOSHA Aardvarks Physical - Tackle
	
Umpire Can Add Red Card To Player
	Player Menu		Jackeline GOSHA
	Click Menu		Red Card
	Submit Card
	Verify Card		Red Card Jackeline GOSHA Aardvarks Red Card
	
Umpire Can Clear Cards From Player
	Player Menu		Jackeline GOSHA
	Click Menu		Red Card
	Wait Until Element Is Not Visible	context-menu
	Player Menu		Jackeline GOSHA
	Click Menu		No Cards
	Submit Card
	Go To						http://cards.leinsterhockey.ie/public/Report/Card/7
	Page Should Not Contain Element		xpath://tr[@data-description='Red Card Jackeline GOSHA Aardvarks Red Card']
	
Umpire Can Add Note To Card

Umpire Can Sign Card

*** Keywords ***
Create Card With Player
	[Arguments]			${fixtureid}
	Reset Card		${fixtureid}
	Open Card			${fixtureid}
	Select Player			Jackeline GOSHA
	Submit Team

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
	Go To						http://cards.leinsterhockey.ie/public/Report/Card/7
	Comment					${description}
	Page Should Contain Element		xpath://tr[@data-description='${description}']

